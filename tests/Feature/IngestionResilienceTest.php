<?php

namespace Tests\Feature;

use App\Exceptions\DgcpApiException;
use App\Models\Bid;
use App\Services\DgcpApiClient;
use App\Services\PortalScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestionResilienceTest extends TestCase
{
    use RefreshDatabase;

    // ── 1. A DGCP timeout must not escape as ConnectionException ──────

    public function test_a_connection_timeout_becomes_a_dgcp_api_exception(): void
    {
        Http::fake(['datosabiertos.dgcp.gob.do/*' => fn () => throw new ConnectionException(
            'cURL error 28: Operation timed out after 30002 milliseconds'
        )]);

        // Callers all catch DgcpApiException; before this, a read timeout
        // surfaced as ConnectionException and killed the whole poll.
        $this->expectException(DgcpApiException::class);
        $this->expectExceptionMessageMatches('/unreachable/');

        app(DgcpApiClient::class)->fetchArticlesSince('92120000', 'familia', new \DateTime('-1 day'));
    }

    public function test_a_transient_timeout_is_retried_before_giving_up(): void
    {
        $attempts = 0;

        Http::fake(['datosabiertos.dgcp.gob.do/*' => function () use (&$attempts) {
            $attempts++;

            if ($attempts < 2) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return Http::response(['payload' => ['content' => []], 'pages' => 1]);
        }]);

        app(DgcpApiClient::class)->fetchArticlesSince('92120000', 'familia', new \DateTime('-1 day'));

        $this->assertSame(2, $attempts, 'The first failure should have been retried, not surfaced.');
    }

    public function test_a_failed_status_still_raises_a_dgcp_api_exception(): void
    {
        // retry(throw: false) must not swallow HTTP errors into a success.
        Http::fake(['datosabiertos.dgcp.gob.do/*' => Http::response('boom', 500)]);

        $this->expectException(DgcpApiException::class);

        app(DgcpApiClient::class)->fetchArticlesSince('92120000', 'familia', new \DateTime('-1 day'));
    }

    // ── 2. Portal TLS + honest reporting ──────────────────────────────

    public function test_the_portal_ca_bundle_is_present_and_complete(): void
    {
        $path = resource_path('certs/comprasdominicana-ca.pem');

        $this->assertFileExists($path);

        $pem = (string) file_get_contents($path);

        preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $pem, $m);
        $certs = array_map(fn ($c) => openssl_x509_parse($c), $m[0]);

        $this->assertCount(3, $certs,
            'Expected the YR2 intermediate, the cross-signed Root YR, and ISRG Root X1.');

        foreach ($certs as $cert) {
            $this->assertIsArray($cert, 'Every entry must parse as a certificate.');
            $this->assertGreaterThan(time(), $cert['validTo_time_t'],
                'Bundled certificate has expired: '.$cert['name']);
        }

        // Each certificate must be issued by the next one, ending at a root
        // that is already in the OS trust store — we add no new anchor.
        $this->assertSame($certs[0]['issuer']['CN'], $certs[1]['subject']['CN']);
        $this->assertSame($certs[1]['issuer']['CN'], $certs[2]['subject']['CN']);
        $this->assertSame('ISRG Root X1', $certs[2]['subject']['CN']);
    }

    public function test_a_total_portal_failure_is_reported_not_called_empty(): void
    {
        Http::fake(['comunidad.comprasdominicana.gob.do/*' => Http::response('', 500)]);

        $this->artisan('secp:scrape')
            ->expectsOutputToContain('Portal unreachable on every procedure type')
            ->assertFailed();
    }

    public function test_a_genuinely_empty_portal_is_still_a_success(): void
    {
        // Reachable, parses, lists nothing — the quiet-day case.
        Http::fake(['comunidad.comprasdominicana.gob.do/*' => Http::response(
            '<html>AdvancedSearchAjax?mkey=abc123_0 <table></table></html>'
        )]);

        $this->artisan('secp:scrape')->assertSuccessful();
    }

    public function test_scraper_tracks_which_procedure_types_failed(): void
    {
        Http::fake(['comunidad.comprasdominicana.gob.do/*' => Http::response('', 503)]);

        $scraper = app(PortalScraperService::class);
        $scraper->scrapeAll();

        $this->assertCount($scraper->procedureCount(), $scraper->failedProcedures());
    }

    // ── 3. The duplicate-key race ─────────────────────────────────────

    public function test_storing_an_existing_process_code_returns_it_instead_of_throwing(): void
    {
        $first = Bid::create([
            'process_code' => 'INFOTEP-DAF-CD-2026-0123',
            'title' => 'Original',
            'status' => 'Publicado',
        ]);

        // What the concurrent poll/scrape pair does to the same code.
        $second = Bid::createOrFirst(
            ['process_code' => 'INFOTEP-DAF-CD-2026-0123'],
            ['title' => 'Duplicado', 'status' => 'Publicado']
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame('Original', $second->title);
        $this->assertDatabaseCount('bids', 1);
    }

    // ── 4. Log pruning ────────────────────────────────────────────────

    public function test_prune_truncates_an_oversized_log_and_keeps_the_tail(): void
    {
        $path = storage_path('logs/test-oversized.log');
        file_put_contents($path, str_repeat("filler line to pad the file out\n", 40000));

        try {
            $this->assertGreaterThan(1024 * 1024, filesize($path));

            $this->artisan('logs:prune', ['--max-mb' => 1, '--keep-kb' => 8])->assertSuccessful();

            clearstatcache(true, $path);
            $this->assertLessThan(1024 * 1024, filesize($path));

            $contents = (string) file_get_contents($path);
            $this->assertStringContainsString('truncated by logs:prune', $contents);
            $this->assertStringContainsString('filler line', $contents, 'The tail should survive.');
        } finally {
            @unlink($path);
        }
    }

    public function test_prune_leaves_a_small_log_alone(): void
    {
        $path = storage_path('logs/test-small.log');
        file_put_contents($path, "just a few lines\n");

        try {
            $this->artisan('logs:prune', ['--max-mb' => 1])->assertSuccessful();

            $this->assertSame("just a few lines\n", file_get_contents($path));
        } finally {
            @unlink($path);
        }
    }

    public function test_prune_dry_run_changes_nothing(): void
    {
        $path = storage_path('logs/test-dry.log');
        file_put_contents($path, str_repeat("x\n", 700000));
        $before = filesize($path);

        try {
            $this->artisan('logs:prune', ['--max-mb' => 1, '--dry-run' => true])->assertSuccessful();

            clearstatcache(true, $path);
            $this->assertSame($before, filesize($path));
        } finally {
            @unlink($path);
        }
    }
}

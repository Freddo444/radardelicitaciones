<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Services\PortalScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * secp:backfill-portal-docs is the only thing that keeps a notice's documents
 * and articles current — the drawer serves whatever is cached and never
 * re-fetches on its own. It spent its life only filling blanks, so a notice
 * that gained an amendment after we first saw it kept showing the old set, and
 * cache_refreshed_at was never bumped, so the same newest rows were re-fetched
 * every hour while the rest of the backlog was never reached.
 */
class PortalDocumentRefreshTest extends TestCase
{
    use RefreshDatabase;

    private function bid(array $attributes = []): Bid
    {
        return Bid::create(array_merge([
            'process_code' => 'TEST-DAF-CM-2026-'.fake()->unique()->numberBetween(1000, 9999),
            'title' => 'Prueba',
            'raw_data' => ['notice_uid' => 'DO1.NTC.'.fake()->unique()->numberBetween(100000, 999999)],
            'tender_deadline' => now()->addDays(5),
            'published_at' => now()->subDay(),
        ], $attributes));
    }

    private function fakeDetail(array $detailByUid): void
    {
        $scraper = Mockery::mock(PortalScraperService::class);
        $scraper->shouldReceive('fetchDetail')->andReturnUsing(
            fn ($uid) => $detailByUid[$uid] ?? ['unspsc' => [], 'documents' => [], 'articles' => []]
        );
        $this->app->instance(PortalScraperService::class, $scraper);
    }

    private function detail(int $docs, int $articles): array
    {
        return [
            'unspsc' => [],
            'documents' => array_map(fn ($i) => [
                'nombre_documento' => "doc{$i}.pdf", 'tipo_documento' => 'Pliego', 'portal_file_id' => (string) (1000 + $i),
            ], range(1, max(0, $docs)) ?: []),
            'articles' => array_map(fn ($i) => [
                'subclase' => '12345678', 'descripcion_articulo' => "art{$i}",
                'cantidad' => 1.0, 'precio_unitario_estimado' => 1.0, 'precio_total_estimado' => 1.0,
            ], range(1, max(0, $articles)) ?: []),
        ];
    }

    public function test_it_replaces_documents_when_the_portal_has_gained_some(): void
    {
        $bid = $this->bid([
            'cached_documents' => $this->detail(17, 0)['documents'],
            'cached_articles' => $this->detail(0, 228)['articles'],
            'cache_refreshed_at' => now()->subDays(40),
        ]);

        $this->fakeDetail([$bid->raw_data['notice_uid'] => $this->detail(22, 228)]);

        $this->artisan('secp:backfill-portal-docs')->assertExitCode(0);

        $bid->refresh();
        $this->assertCount(22, $bid->cached_documents);
        $this->assertCount(228, $bid->cached_articles);
        $this->assertTrue($bid->cache_refreshed_at->isAfter(now()->subMinute()));
    }

    public function test_it_fills_a_bid_that_has_nothing_cached(): void
    {
        $bid = $this->bid(['cached_documents' => null, 'cached_articles' => null]);

        $this->fakeDetail([$bid->raw_data['notice_uid'] => $this->detail(3, 2)]);

        $this->artisan('secp:backfill-portal-docs')->assertExitCode(0);

        $bid->refresh();
        $this->assertCount(3, $bid->cached_documents);
        $this->assertCount(2, $bid->cached_articles);
        $this->assertNotNull($bid->cache_refreshed_at);
    }

    public function test_a_refreshed_bid_stops_being_eligible(): void
    {
        $bid = $this->bid([
            'cached_documents' => $this->detail(2, 0)['documents'],
            'cached_articles' => $this->detail(0, 1)['articles'],
            'cache_refreshed_at' => now()->subDays(3),
        ]);

        $this->fakeDetail([$bid->raw_data['notice_uid'] => $this->detail(2, 1)]);

        $this->artisan('secp:backfill-portal-docs')->assertExitCode(0);
        $this->artisan('secp:backfill-portal-docs')
            ->expectsOutputToContain('No bids due for a document or article refresh.')
            ->assertExitCode(0);
    }

    public function test_an_empty_portal_response_does_not_wipe_what_is_stored(): void
    {
        $bid = $this->bid([
            'cached_documents' => $this->detail(5, 0)['documents'],
            'cached_articles' => $this->detail(0, 3)['articles'],
            'cache_refreshed_at' => $was = now()->subDays(10),
        ]);

        // fetchDetail() returns empty lists for a failed request as well as for
        // a genuinely empty notice, so an all-empty answer must not overwrite.
        $this->fakeDetail([]);

        // Every attempt failing is reported as a failure, not a quiet success.
        $this->artisan('secp:backfill-portal-docs')->assertExitCode(1);

        $bid->refresh();
        $this->assertCount(5, $bid->cached_documents);
        $this->assertCount(3, $bid->cached_articles);
        $this->assertSame($was->toDateTimeString(), $bid->cache_refreshed_at->toDateTimeString());
    }

    public function test_it_takes_the_stalest_bids_first(): void
    {
        $old = $this->bid(['cached_documents' => $this->detail(1, 0)['documents'],
            'cached_articles' => $this->detail(0, 1)['articles'],
            'cache_refreshed_at' => now()->subDays(30), 'published_at' => now()->subDays(30)]);
        $fresh = $this->bid(['cached_documents' => $this->detail(1, 0)['documents'],
            'cached_articles' => $this->detail(0, 1)['articles'],
            'cache_refreshed_at' => now()->subDays(1), 'published_at' => now()]);

        $this->fakeDetail([
            $old->raw_data['notice_uid'] => $this->detail(9, 1),
            $fresh->raw_data['notice_uid'] => $this->detail(9, 1),
        ]);

        $this->artisan('secp:backfill-portal-docs', ['--limit' => 1])->assertExitCode(0);

        $this->assertCount(9, $old->refresh()->cached_documents);
        $this->assertCount(1, $fresh->refresh()->cached_documents);
    }

    public function test_it_ignores_an_undated_notice_published_long_ago(): void
    {
        $ancient = $this->bid([
            'tender_deadline' => null,
            'published_at' => now()->subYears(5),
            'cached_documents' => $this->detail(1, 0)['documents'],
            'cached_articles' => $this->detail(0, 1)['articles'],
            'cache_refreshed_at' => now()->subYears(2),
        ]);

        $this->fakeDetail([$ancient->raw_data['notice_uid'] => $this->detail(7, 1)]);

        $this->artisan('secp:backfill-portal-docs')
            ->expectsOutputToContain('No bids due for a document or article refresh.')
            ->assertExitCode(0);

        $this->assertCount(1, $ancient->refresh()->cached_documents);
    }

    public function test_it_still_refreshes_an_undated_notice_published_recently(): void
    {
        $bid = $this->bid([
            'tender_deadline' => null,
            'published_at' => now()->subDays(3),
            'cached_documents' => $this->detail(1, 0)['documents'],
            'cached_articles' => $this->detail(0, 1)['articles'],
            'cache_refreshed_at' => now()->subDays(2),
        ]);

        $this->fakeDetail([$bid->raw_data['notice_uid'] => $this->detail(7, 1)]);

        $this->artisan('secp:backfill-portal-docs')->assertExitCode(0);

        $this->assertCount(7, $bid->refresh()->cached_documents);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $bid = $this->bid([
            'cached_documents' => $this->detail(2, 0)['documents'],
            'cached_articles' => $this->detail(0, 1)['articles'],
            'cache_refreshed_at' => $was = now()->subDays(9),
        ]);

        $this->fakeDetail([$bid->raw_data['notice_uid'] => $this->detail(8, 4)]);

        $this->artisan('secp:backfill-portal-docs', ['--dry-run' => true])->assertExitCode(0);

        $bid->refresh();
        $this->assertCount(2, $bid->cached_documents);
        $this->assertSame($was->toDateTimeString(), $bid->cache_refreshed_at->toDateTimeString());
    }
}

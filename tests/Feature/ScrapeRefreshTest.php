<?php

namespace Tests\Feature;

use App\Jobs\SendWatchedBidChangeNotification;
use App\Models\Bid;
use App\Models\BidWatch;
use App\Models\InAppNotification;
use App\Models\Rubro;
use App\Services\PortalScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class ScrapeRefreshTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private $user;

    private $company;

    /** @var array<string, array> sweep rows by process code */
    private array $open = [];

    /** @var array<string, array> */
    private array $byReference = [];

    /** @var array<string, int> */
    private array $documents = [];

    private array $referenceLookups = [];

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        [$this->user, , $this->company] = $this->makeOwnerWithCompany(['status' => 'active']);
        Rubro::withoutGlobalScopes()->create([
            'company_id' => $this->company->id, 'code' => '43210000', 'name' => 'Computadores',
            'level' => 'familia', 'active' => true, 'first_polled_at' => now()->subDay(),
        ]);
    }

    public function test_a_cancellation_on_the_portal_reaches_the_bid_and_its_watchers(): void
    {
        $bid = $this->bid('MINERD-CCC-CP-2026-0001', watched: true);
        $this->onPortal('MINERD-CCC-CP-2026-0001', status: 'Canceled');

        $this->runScrape();

        $this->assertSame('Cancelado', $bid->fresh()->status);
        $this->assertDatabaseHas('in_app_notifications', ['bid_id' => $bid->id, 'user_id' => $this->user->id]);
        $this->assertStringContainsString('Proceso publicado → Cancelado', InAppNotification::first()->body);
        Queue::assertPushed(SendWatchedBidChangeNotification::class);
    }

    public function test_an_unwatched_bid_is_kept_current_without_alerting_anyone(): void
    {
        $bid = $this->bid('INFOTEP-CCC-CP-2026-0002');
        $this->onPortal('INFOTEP-CCC-CP-2026-0002', deadline: now()->addDays(9)->setTime(10, 0), amount: 2500.0);

        $this->runScrape();

        $fresh = $bid->fresh();
        $this->assertSame(now()->addDays(9)->setTime(10, 0)->format('Y-m-d H:i'), $fresh->tender_deadline->format('Y-m-d H:i'));
        $this->assertEquals(2500.0, $fresh->amount_estimated);
        $this->assertDatabaseCount('in_app_notifications', 0);
        Queue::assertNotPushed(SendWatchedBidChangeNotification::class);
    }

    public function test_an_extended_deadline_alerts_watchers(): void
    {
        $bid = $this->bid('SNS-CCC-LPN-2026-0028', watched: true);
        $this->onPortal('SNS-CCC-LPN-2026-0028', deadline: now()->addDays(20)->setTime(10, 0));

        $this->runScrape();

        $this->assertStringContainsString('Plazo:', InAppNotification::first()->body);
    }

    public function test_new_documents_on_a_watched_bid_alert_its_watchers(): void
    {
        $bid = $this->bid('ETED-MAE-PEEN-2026-0012', watched: true, docs: 3);
        $this->onPortal('ETED-MAE-PEEN-2026-0012');
        $this->documents['DO1.NTC.ETED-MAE-PEEN-2026-0012'] = 5;

        $this->runScrape();

        $this->assertStringContainsString('2 documento(s) nuevo(s)', InAppNotification::first()->body);
        $this->assertSame(5, $bid->fresh()->last_known_doc_count);
        $this->assertCount(5, $bid->fresh()->cached_documents);
    }

    public function test_nothing_changed_means_no_alert(): void
    {
        $this->bid('MINERD-CCC-CP-2026-0001', watched: true, docs: 3);
        $this->onPortal('MINERD-CCC-CP-2026-0001');
        $this->documents['DO1.NTC.MINERD-CCC-CP-2026-0001'] = 3;

        $this->runScrape();

        $this->assertDatabaseCount('in_app_notifications', 0);
    }

    public function test_a_closed_watched_bid_is_rechecked_every_few_hours_not_every_run(): void
    {
        $bid = $this->bid('HDMTD-DAF-CM-2026-0071', watched: true, docs: 2);
        $bid->forceFill([
            'status' => 'Proceso adjudicado y celebrado',
            'tender_deadline' => now()->subMonths(6),
            'cache_refreshed_at' => now()->subHour(),
        ])->save();

        $this->runScrape();
        $this->assertSame([], $this->referenceLookups);

        $this->travel(7)->hours();
        $this->runScrape();
        $this->assertSame(['HDMTD-DAF-CM-2026-0071'], $this->referenceLookups);
    }

    public function test_a_tracked_bid_missing_from_the_sweep_is_looked_up_by_reference(): void
    {
        $bid = $this->bid('MOPC-CCC-LPN-2026-0040');
        $this->byReference['MOPC-CCC-LPN-2026-0040'] = $this->row('MOPC-CCC-LPN-2026-0040', status: 'Suspended');

        $this->runScrape();

        $this->assertSame(['MOPC-CCC-LPN-2026-0040'], $this->referenceLookups);
        $this->assertSame('Suspendido', $bid->fresh()->status);
    }

    public function test_a_cancelled_notice_is_not_imported_as_a_new_bid(): void
    {
        $this->onPortal('NEW-CCC-CP-2026-0099', status: 'Canceled');
        $this->onPortal('NEW-CCC-CP-2026-0100');

        $this->runScrape();

        $this->assertDatabaseMissing('bids', ['process_code' => 'NEW-CCC-CP-2026-0099']);
        $this->assertDatabaseHas('bids', ['process_code' => 'NEW-CCC-CP-2026-0100', 'status' => 'Proceso publicado']);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function runScrape(): void
    {
        $scraper = Mockery::mock(PortalScraperService::class);
        $scraper->shouldReceive('scrapeAll')->andReturn(collect());
        $scraper->shouldReceive('failedProcedures')->andReturn([]);
        $scraper->shouldReceive('procedureCount')->andReturn(10);
        $scraper->shouldReceive('listingRequests')->andReturn(0);
        $scraper->shouldReceive('sweepOpenNotices')->andReturn([
            'notices' => collect($this->open), 'hints' => [], 'failed' => [], 'capped' => 0,
        ]);
        $scraper->shouldReceive('findByReference')->andReturnUsing(function ($code) {
            $this->referenceLookups[] = $code;

            return $this->byReference[$code] ?? null;
        });
        $scraper->shouldReceive('fetchDetail')->andReturnUsing(fn ($uid) => [
            'unspsc' => ['43211500'],
            'documents' => array_map(fn ($i) => ['nombre_documento' => "Doc {$i}", 'tipo_documento' => 'pliego', 'portal_file_id' => "F{$i}"],
                range(1, $this->documents[$uid] ?? 1)),
            'articles' => [],
        ]);

        $this->app->instance(PortalScraperService::class, $scraper);

        $this->artisan('secp:scrape')->assertSuccessful();
    }

    private function bid(string $code, bool $watched = false, int $docs = 0): Bid
    {
        $bid = Bid::create([
            'process_code' => $code,
            'title' => "Proceso {$code}",
            'status' => 'Proceso publicado',
            'tender_deadline' => now()->addDays(5)->setTime(10, 0),
            'amount_estimated' => 1000,
            'raw_data' => ['source' => 'portal_scrape', 'notice_uid' => "DO1.NTC.{$code}"],
            'cached_documents' => $docs ? array_fill(0, $docs, ['nombre_documento' => 'x']) : null,
            'last_known_status' => 'Proceso publicado',
            'last_known_doc_count' => $watched ? $docs : null,
        ]);

        if ($watched) {
            BidWatch::withoutGlobalScopes()->create([
                'company_id' => $this->company->id, 'bid_id' => $bid->id, 'user_id' => $this->user->id,
            ]);
        }

        return $bid;
    }

    private function onPortal(string $code, string $status = 'Published', $deadline = null, float $amount = 1000.0): void
    {
        $this->open[$code] = $this->row($code, $status, $deadline, $amount);
    }

    private function row(string $code, string $status = 'Published', $deadline = null, float $amount = 1000.0): array
    {
        return [
            'process_code' => $code,
            'title' => "Proceso {$code}",
            'buyer_name' => 'Institución',
            'published_at' => now()->subDay()->toDateTimeString(),
            'tender_deadline' => ($deadline ?? now()->addDays(5)->setTime(10, 0))->format('Y-m-d H:i:s'),
            'amount_estimated' => $amount,
            'currency' => 'DOP',
            'notice_uid' => "DO1.NTC.{$code}",
            'portal_url' => "https://portal.test/{$code}",
            'portal_status' => $status,
        ];
    }
}

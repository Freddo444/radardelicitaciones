<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Rubro;
use App\Services\PortalScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Concerns\CreatesTenant;
use Tests\TestCase;

class ScrapeNoticeCheckTest extends TestCase
{
    use CreatesTenant, RefreshDatabase;

    private int $companyId;

    /** @var array<string, int> */
    private array $fetches = [];

    /** @var array<string, list<string>> */
    private array $unspsc = [];

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        [, , $company] = $this->makeOwnerWithCompany(['status' => 'active']);
        $this->companyId = $company->id;

        $this->rubro('43210000');
    }

    public function test_an_unmatched_notice_is_read_once_not_every_run(): void
    {
        $this->portalShows(['NTC.1' => ['80111600']]);

        $this->artisan('secp:scrape')->assertSuccessful();
        $this->artisan('secp:scrape')->assertSuccessful();
        $this->artisan('secp:scrape')->assertSuccessful();

        $this->assertSame(1, $this->fetches['NTC.1'],
            'An unmatched notice\'s codes do not change; its page should be read once.');
        $this->assertDatabaseCount('bids', 0);
    }

    public function test_a_rubro_added_later_matches_a_notice_already_checked(): void
    {
        $this->portalShows(['NTC.1' => ['80111600']]);

        $this->artisan('secp:scrape')->assertSuccessful();
        $this->assertDatabaseCount('bids', 0);

        $this->rubro('80110000');
        $this->artisan('secp:scrape')->assertSuccessful();

        $this->assertDatabaseHas('bids', ['process_code' => 'CODE-NTC.1']);
        // Once to check, once more to take the documents for the saved bid.
        $this->assertSame(2, $this->fetches['NTC.1']);
    }

    public function test_a_page_without_codes_is_looked_at_again_but_not_every_run(): void
    {
        $this->portalShows(['NTC.1' => []]);

        $this->artisan('secp:scrape')->assertSuccessful();
        $this->artisan('secp:scrape')->assertSuccessful();
        $this->assertSame(1, $this->fetches['NTC.1']);

        $this->travel(7)->hours();
        $this->unspsc['NTC.1'] = ['43211500'];
        $this->artisan('secp:scrape')->assertSuccessful();

        $this->assertSame(2, $this->fetches['NTC.1']);
        $this->assertDatabaseHas('bids', ['process_code' => 'CODE-NTC.1']);
    }

    public function test_a_backlog_is_read_newest_first_and_spread_over_runs(): void
    {
        $notices = [];
        for ($i = 0; $i < 50; $i++) {
            $notices["NTC.{$i}"] = ['80111600'];
        }
        $this->portalShows($notices);

        $this->artisan('secp:scrape')->assertSuccessful();
        $this->assertCount(40, $this->fetches, 'One run reads at most 40 new pages.');

        $this->artisan('secp:scrape')->assertSuccessful();
        $this->assertCount(50, $this->fetches, 'The rest follow on the next run.');
        $this->assertSame(1, max($this->fetches));
    }

    public function test_a_dry_run_records_nothing(): void
    {
        $this->portalShows(['NTC.1' => ['80111600']]);

        $this->artisan('secp:scrape', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, DB::table('portal_notice_checks')->count());
    }

    public function test_old_checks_are_pruned(): void
    {
        DB::table('portal_notice_checks')->insert([
            'notice_uid' => 'NTC.OLD', 'unspsc' => '[]', 'checked_at' => now()->subDays(200),
        ]);
        $this->portalShows(['NTC.1' => ['80111600']]);

        $this->artisan('secp:scrape')->assertSuccessful();

        $this->assertDatabaseMissing('portal_notice_checks', ['notice_uid' => 'NTC.OLD']);
        $this->assertDatabaseHas('portal_notice_checks', ['notice_uid' => 'NTC.1']);
    }

    /** @param array<string, list<string>> $notices notice_uid => UNSPSC codes on its page */
    private function portalShows(array $notices): void
    {
        $this->unspsc = $notices;

        $scraper = Mockery::mock(PortalScraperService::class);
        $scraper->shouldReceive('failedProcedures')->andReturn([]);
        $scraper->shouldReceive('procedureCount')->andReturn(10);
        $scraper->shouldReceive('scrapeAll')->andReturnUsing(fn () => collect(array_keys($this->unspsc))
            ->map(fn ($uid) => [
                'process_code' => "CODE-{$uid}",
                'notice_uid' => $uid,
                'title' => "Notice {$uid}",
                'buyer_name' => 'Institución',
                'amount_estimated' => 1000,
                'currency' => 'DOP',
                'published_at' => now()->subDay()->toDateTimeString(),
                'tender_deadline' => now()->addWeek()->toDateTimeString(),
                'portal_url' => "https://portal.test/{$uid}",
                'portal_status' => 'Published',
            ]));
        $scraper->shouldReceive('sweepOpenNotices')->andReturn(['notices' => collect(), 'hints' => [], 'failed' => [], 'capped' => 0]);
        $scraper->shouldReceive('listingRequests')->andReturn(0);
        $scraper->shouldReceive('findByReference')->andReturn(null);
        $scraper->shouldReceive('fetchDetail')->andReturnUsing(function ($uid) {
            $this->fetches[$uid] = ($this->fetches[$uid] ?? 0) + 1;

            return ['unspsc' => $this->unspsc[$uid], 'documents' => [], 'articles' => []];
        });

        $this->app->instance(PortalScraperService::class, $scraper);
    }

    private function rubro(string $code): void
    {
        Rubro::withoutGlobalScopes()->create([
            'company_id' => $this->companyId,
            'code' => $code,
            'name' => "Rubro {$code}",
            'level' => 'familia',
            'active' => true,
            'first_polled_at' => now()->subDay(),
        ]);
    }
}

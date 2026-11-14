<?php

namespace App\Console\Commands;

use App\Jobs\SendBidNotification;
use App\Models\Bid;
use App\Models\Company;
use App\Models\CompanyBid;
use App\Models\Setting;
use App\Services\BidMatchingService;
use App\Services\PortalScraperService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScrapeCommand extends Command
{
    protected $signature = 'secp:scrape {--dry-run : Show what would be imported without saving}';

    protected $description = 'Scrape the DGCP portal for new procurement notices and match against active rubros';

    private const EMPTY_RECHECK_HOURS = 6;

    private const CHECK_RETENTION_DAYS = 120;

    public function handle(PortalScraperService $scraper, BidMatchingService $matcher): int
    {
        $this->info('Scraping portal for open notices...');

        $notices = $scraper->scrapeAll();
        $failed = $scraper->failedProcedures();

        if ($failed !== []) {
            $this->warn(count($failed).' procedure type(s) did not respond: '.implode(', ', $failed));
        }

        if ($notices->isEmpty()) {
            // Every type failing means the portal is unreachable, not that the
            // day was quiet. Reported as a failure so it shows up instead of
            // reading as a successful empty run, which hid a three-day TLS
            // outage in October 2026.
            if (count($failed) === $scraper->procedureCount()) {
                $this->error('Portal unreachable on every procedure type — this is not an empty result.');
                Log::error('[SECP] Portal scrape failed for every procedure type', ['failed' => $failed]);

                return self::FAILURE;
            }

            $this->info('No notices found on portal.');

            return self::SUCCESS;
        }

        $this->info("Found {$notices->count()} open notice(s) across all procedure types.");

        // Filter to notices not already in DB
        $existingCodes = Bid::whereIn('process_code', $notices->pluck('process_code')->all())
            ->pluck('process_code');

        $newNotices = $notices->filter(fn ($n) => ! $existingCodes->contains($n['process_code']));

        $this->info("{$newNotices->count()} new notice(s) not yet in our database.");

        if ($newNotices->isEmpty()) {
            $this->info('All portal notices already tracked.');

            return self::SUCCESS;
        }

        // Aggregate rubros across all companies
        $rubroMap = $matcher->aggregateRubros();
        if (empty($rubroMap)) {
            $this->warn('No active rubros configured in any company. Skipping.');

            return self::SUCCESS;
        }

        // Build lookup sets for fast matching at each UNSPSC level
        $rubrosByLevel = [
            'subclase' => [],
            'clase' => [],
            'familia' => [],
        ];
        foreach ($rubroMap as $code => $entry) {
            $rubrosByLevel[$entry['level']][$code] = $entry['name'];
        }

        $this->info('Checking detail pages for UNSPSC rubro matches...');

        // A notice's UNSPSC codes do not change, so its detail page is read
        // once and the codes kept. Before this every unmatched notice in view
        // was re-fetched every fifteen minutes — over a hundred pages a run for
        // nothing, which is how a scraper gets itself blocked. Matching against
        // the stored codes still runs every time, so a rubro a company adds
        // later is matched against notices already seen.
        $checks = DB::table('portal_notice_checks')
            ->whereIn('notice_uid', $newNotices->pluck('notice_uid')->filter()->all())
            ->get()
            ->keyBy('notice_uid');

        $saved = 0;
        $notified = 0;
        $fetched = 0;

        foreach ($newNotices as $notice) {
            if (! $notice['notice_uid']) {
                continue;
            }

            $check = $checks->get($notice['notice_uid']);
            $detail = null;

            if ($check) {
                $unspscCodes = json_decode($check->unspsc, true) ?: [];

                // A page that showed no codes may simply not have finished
                // loading on the portal's side; look again, but not every run.
                if ($unspscCodes === [] && $check->checked_at < now()->subHours(self::EMPTY_RECHECK_HOURS)) {
                    $check = null;
                }
            }

            if (! $check) {
                // Spaced out: a first run, or a busy hour, can mean hundreds
                // of pages, and the portal is the one source still answering.
                if ($fetched > 0 && ! app()->runningUnitTests()) {
                    usleep(300_000);
                }

                $detail = $scraper->fetchDetail($notice['notice_uid']);
                $unspscCodes = $detail['unspsc'];
                $fetched++;

                if (! $this->option('dry-run')) {
                    DB::table('portal_notice_checks')->upsert([[
                        'notice_uid' => $notice['notice_uid'],
                        'unspsc' => json_encode(array_values($unspscCodes)),
                        'checked_at' => now(),
                    ]], ['notice_uid'], ['unspsc', 'checked_at']);
                }
            }

            if (empty($unspscCodes)) {
                if ($detail !== null) {
                    $this->line("  SKIP {$notice['process_code']} — no UNSPSC codes found");
                }

                continue;
            }

            // Match UNSPSC codes against aggregated rubros
            $matchedRubros = collect();

            foreach ($unspscCodes as $code) {
                if (isset($rubrosByLevel['subclase'][$code])) {
                    $matchedRubros->push(['code' => $code, 'name' => $rubrosByLevel['subclase'][$code]]);

                    continue;
                }

                if (isset($rubrosByLevel['clase'][$code])) {
                    $matchedRubros->push(['code' => $code, 'name' => $rubrosByLevel['clase'][$code]]);

                    continue;
                }

                // Check clase level by first 6 digits
                $clasePrefix = substr($code, 0, 6);
                foreach ($rubrosByLevel['clase'] as $rubroCode => $rubroName) {
                    if (substr($rubroCode, 0, 6) === $clasePrefix) {
                        $matchedRubros->push(['code' => $rubroCode, 'name' => $rubroName]);
                        break;
                    }
                }

                // Check familia level (first 4 digits)
                $familiaPrefix = substr($code, 0, 4);
                foreach ($rubrosByLevel['familia'] as $rubroCode => $rubroName) {
                    if (substr($rubroCode, 0, 4) === $familiaPrefix) {
                        $matchedRubros->push(['code' => $rubroCode, 'name' => $rubroName]);
                        break;
                    }
                }
            }

            $matchedRubros = $matchedRubros->unique('code')->values();

            if ($matchedRubros->isEmpty()) {
                if ($this->option('dry-run')) {
                    $this->line("  SKIP {$notice['process_code']} — UNSPSC [".implode(', ', $unspscCodes).'] no rubro match');
                }

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("[DRY] {$notice['process_code']} — {$notice['title']}");
                $this->line("      {$notice['buyer_name']} | ".($notice['amount_estimated'] ? number_format($notice['amount_estimated'], 2).' DOP' : 'N/A'));
                $this->line('      Rubros: '.$matchedRubros->pluck('name')->join(', '));

                continue;
            }

            // Matched on stored codes: the documents and articles saved with
            // the bid still have to come from the page.
            $detail ??= $scraper->fetchDetail($notice['notice_uid']);

            try {
                // The hourly poll writes to this table too. createOrFirst, not
                // firstOrCreate: the latter's SELECT-then-INSERT still collides
                // when both commands reach the same process_code at once.
                $bid = Bid::createOrFirst([
                    'process_code' => $notice['process_code'],
                ], [
                    'ocid' => 'ocds-6550wx-'.$notice['process_code'],
                    'title' => $notice['title'] ?: $notice['process_code'],
                    'buyer_name' => $notice['buyer_name'],
                    'status' => 'Proceso publicado',
                    'amount_estimated' => $notice['amount_estimated'],
                    'currency' => $notice['currency'],
                    'published_at' => $notice['published_at'],
                    'tender_deadline' => $notice['tender_deadline'],
                    'secp_url' => $notice['portal_url'],
                    'raw_data' => ['source' => 'portal_scrape', 'notice_uid' => $notice['notice_uid'], 'unspsc' => $unspscCodes],
                    'cached_documents' => $detail['documents'] ?: null,
                    'cached_articles' => $detail['articles'] ?: null,
                    'cache_refreshed_at' => ($detail['documents'] || $detail['articles']) ? now() : null,
                ]);

                if (! $bid->wasRecentlyCreated) {
                    $this->line("      [YA EXISTÍA] {$notice['process_code']} — insertado por el sondeo");
                }

                $saved++;
                $this->info("[SAVED] {$notice['process_code']} — {$notice['title']}");
                $this->info('        Rubros: '.$matchedRubros->pluck('name')->join(', '));

                // Fan out to matching companies
                $companyIds = $matcher->fanOutToCompanies($bid, $matchedRubros->all(), $rubroMap);

                $this->info('        → '.count($companyIds).' empresa(s) vinculada(s)');

                // Per-company notification dispatch
                foreach ($companyIds as $companyId) {
                    if ($matcher->shouldNotify($bid, $companyId)) {
                        $company = Company::find($companyId);
                        if ($company) {
                            SendBidNotification::dispatch($bid, $company);
                            $notified++;
                        }
                    } else {
                        CompanyBid::where('bid_id', $bid->id)
                            ->where('company_id', $companyId)
                            ->update(['notified_at' => now()]);
                    }
                }
            } catch (QueryException $e) {
                $this->line("  SKIP {$notice['process_code']} — already saved by poll");

                continue;
            }

            usleep(300_000);
        }

        Setting::set('last_scraped_at', now()->toDateTimeString());

        if (! $this->option('dry-run')) {
            DB::table('portal_notice_checks')
                ->where('checked_at', '<', now()->subDays(self::CHECK_RETENTION_DAYS))
                ->delete();
        }

        $summary = "Portal scrape complete. Checked: {$newNotices->count()} | Detail pages fetched: {$fetched} | Saved: {$saved} | Notified: {$notified}";
        $this->info($summary);
        Log::info("[SECP] {$summary}");

        return self::SUCCESS;
    }
}

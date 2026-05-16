<?php

namespace App\Console\Commands;

use App\Jobs\SendBidNotification;
use App\Models\Bid;
use App\Models\Company;
use App\Models\CompanyBid;
use App\Services\BidMatchingService;
use App\Services\PortalScraperService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class ScrapeCommand extends Command
{
    protected $signature = 'secp:scrape {--dry-run : Show what would be imported without saving}';

    protected $description = 'Scrape the DGCP portal for new procurement notices and match against active rubros';

    public function handle(PortalScraperService $scraper, BidMatchingService $matcher): int
    {
        $this->info('Scraping portal for open notices...');

        $notices = $scraper->scrapeAll();

        if ($notices->isEmpty()) {
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

        $saved = 0;
        $notified = 0;

        foreach ($newNotices as $notice) {
            if (! $notice['notice_uid']) {
                continue;
            }

            // Fetch detail page to get UNSPSC codes and documents
            $detail = $scraper->fetchDetail($notice['notice_uid']);
            $unspscCodes = $detail['unspsc'];

            if (empty($unspscCodes)) {
                $this->line("  SKIP {$notice['process_code']} — no UNSPSC codes found");

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

            try {
                // Create global bid
                $bid = Bid::create([
                    'process_code' => $notice['process_code'],
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

        $summary = "Portal scrape complete. Checked: {$newNotices->count()} | Saved: {$saved} | Notified: {$notified}";
        $this->info($summary);
        Log::info("[SECP] {$summary}");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\SendBidNotification;
use App\Models\Bid;
use App\Models\Rubro;
use App\Models\Setting;
use App\Services\PortalScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScrapeCommand extends Command
{
    protected $signature = 'secp:scrape {--dry-run : Show what would be imported without saving}';

    protected $description = 'Scrape the DGCP portal for new procurement notices and match against active rubros';

    public function handle(PortalScraperService $scraper): int
    {
        $this->info('Scraping portal for recent notices...');

        $notices = $scraper->scrapeRecent(25);

        if ($notices->isEmpty()) {
            $this->info('No recent notices found on portal.');

            return self::SUCCESS;
        }

        $this->info("Found {$notices->count()} notice(s) published in the last 25 hours.");

        // Filter to notices not already in DB
        $existingCodes = Bid::whereIn('process_code', $notices->pluck('process_code')->all())
            ->pluck('process_code');

        $newNotices = $notices->filter(fn ($n) => ! $existingCodes->contains($n['process_code']));

        $this->info("{$newNotices->count()} new notice(s) not yet in our database.");

        if ($newNotices->isEmpty()) {
            $this->info('All portal notices already tracked.');

            return self::SUCCESS;
        }

        // Load active rubros for matching
        $activeRubros = Rubro::where('active', true)->get();
        if ($activeRubros->isEmpty()) {
            $this->warn('No active rubros configured. Skipping.');

            return self::SUCCESS;
        }

        // Build lookup sets for fast matching at each UNSPSC level
        $rubrosByLevel = [
            'subclase' => $activeRubros->where('level', 'subclase')->pluck('name', 'code'), // 8 digits
            'clase' => $activeRubros->where('level', 'clase')->pluck('name', 'code'),       // 8 digits
            'familia' => $activeRubros->where('level', 'familia')->pluck('name', 'code'),    // first 6 digits
        ];

        $this->info("Checking detail pages for UNSPSC rubro matches...");

        $saved = 0;
        $notified = 0;

        // Notification filters
        $minEnabled = Setting::get('min_amount_filter') === '1';
        $minValue = (float) (Setting::get('min_amount_value') ?? 0);
        $maxEnabled = Setting::get('max_amount_filter') === '1';
        $maxValue = (float) (Setting::get('max_amount_value') ?? 0);
        $openDeadlineEnabled = Setting::get('open_deadline_filter') === '1';

        foreach ($newNotices as $notice) {
            if (! $notice['notice_uid']) {
                continue;
            }

            // Fetch detail page to get UNSPSC codes
            $unspscCodes = $scraper->fetchDetailUnspsc($notice['notice_uid']);

            if (empty($unspscCodes)) {
                $this->line("  SKIP {$notice['process_code']} — no UNSPSC codes found");
                continue;
            }

            // Match UNSPSC codes against active rubros
            $matchedRubros = collect();

            foreach ($unspscCodes as $code) {
                // Check exact match (subclase level = full 8-digit code)
                if ($rubrosByLevel['subclase']->has($code)) {
                    $matchedRubros->push(['code' => $code, 'name' => $rubrosByLevel['subclase']->get($code)]);
                    continue;
                }

                // Check clase level (8-digit code match)
                if ($rubrosByLevel['clase']->has($code)) {
                    $matchedRubros->push(['code' => $code, 'name' => $rubrosByLevel['clase']->get($code)]);
                    continue;
                }

                // Check clase level by first 6 digits (article subclase vs rubro clase)
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

            // Deduplicate
            $matchedRubros = $matchedRubros->unique('code')->values();

            if ($matchedRubros->isEmpty()) {
                if ($this->option('dry-run')) {
                    $this->line("  SKIP {$notice['process_code']} — UNSPSC [".implode(', ', $unspscCodes)."] no rubro match");
                }
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("[DRY] {$notice['process_code']} — {$notice['title']}");
                $this->line("      {$notice['buyer_name']} | ".($notice['amount_estimated'] ? number_format($notice['amount_estimated'], 2).' DOP' : 'N/A'));
                $this->line("      Rubros: ".$matchedRubros->pluck('name')->join(', '));
                continue;
            }

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
                'matched_rubros' => $matchedRubros->all(),
                'secp_url' => $notice['portal_url'],
                'raw_data' => ['source' => 'portal_scrape', 'notice_uid' => $notice['notice_uid'], 'unspsc' => $unspscCodes],
                'is_relevant' => Bid::computeRelevance($notice['title'] ?? ''),
            ]);

            $saved++;
            $this->info("[SAVED] {$notice['process_code']} — {$notice['title']}");
            $this->info("        Rubros: ".$matchedRubros->pluck('name')->join(', '));

            // Apply notification filters
            $amount = $bid->amount_estimated;
            $shouldNotify = true;

            if ($minEnabled && $amount !== null && $amount < $minValue) {
                $shouldNotify = false;
            }
            if ($shouldNotify && $maxEnabled && $maxValue > 0 && $amount !== null && $amount > $maxValue) {
                $shouldNotify = false;
            }
            if ($shouldNotify && $openDeadlineEnabled && $bid->tender_deadline && $bid->tender_deadline < now()) {
                $shouldNotify = false;
            }

            if ($shouldNotify) {
                SendBidNotification::dispatch($bid);
                $notified++;
            } else {
                $bid->update(['notified_at' => now()]);
            }

            // Small delay between detail page fetches
            usleep(300_000);
        }

        $summary = "Portal scrape complete. Checked: {$newNotices->count()} | Saved: {$saved} | Notified: {$notified}";
        $this->info($summary);
        Log::info("[SECP] {$summary}");

        return self::SUCCESS;
    }
}

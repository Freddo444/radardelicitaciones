<?php

namespace App\Console\Commands;

use App\Models\Bid;
use App\Services\PortalScraperService;
use Illuminate\Console\Command;

class BackfillPortalDocumentsCommand extends Command
{
    protected $signature = 'secp:backfill-portal-docs
                            {--limit=50 : Max bids to process per run}
                            {--dry-run : Show what would be fetched without saving}';

    protected $description = 'Backfill cached_documents for portal_scrape bids that have no documents yet';

    public function handle(PortalScraperService $scraper): int
    {
        $bids = Bid::whereRaw("JSON_EXTRACT(raw_data, '$.source') = 'portal_scrape'")
            ->whereRaw("JSON_EXTRACT(raw_data, '$.notice_uid') IS NOT NULL")
            ->whereNull('cached_documents')
            ->orderByDesc('published_at')
            ->limit((int) $this->option('limit'))
            ->get(['id', 'process_code', 'raw_data']);

        if ($bids->isEmpty()) {
            $this->info('No portal_scrape bids with missing documents.');

            return self::SUCCESS;
        }

        $this->info("Backfilling documents for {$bids->count()} bid(s)...");

        $filled = 0;
        $empty = 0;

        foreach ($bids as $bid) {
            $noticeUid = $bid->raw_data['notice_uid'];
            $detail = $scraper->fetchDetail($noticeUid);

            if ($this->option('dry-run')) {
                $count = count($detail['documents']);
                $this->line("  {$bid->process_code} ({$noticeUid}): {$count} document(s)");

                continue;
            }

            if (! empty($detail['documents'])) {
                $bid->update([
                    'cached_documents' => $detail['documents'],
                    'cache_refreshed_at' => now(),
                ]);
                $this->line("  [OK] {$bid->process_code}: ".count($detail['documents']).' document(s)');
                $filled++;
            } else {
                $this->line("  [SKIP] {$bid->process_code}: still no documents on portal");
                $empty++;
            }

            usleep(300_000);
        }

        $this->info("Done. Filled: {$filled} | Still empty: {$empty}");

        return self::SUCCESS;
    }
}

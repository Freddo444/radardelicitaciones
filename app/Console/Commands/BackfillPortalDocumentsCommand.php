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

    protected $description = 'Backfill cached_documents and cached_articles for bids with a resolvable notice_uid';

    public function handle(PortalScraperService $scraper): int
    {
        $bids = Bid::where(function ($q) {
            $q->whereRaw("JSON_EXTRACT(raw_data, '$.notice_uid') IS NOT NULL")
                ->orWhereRaw("JSON_EXTRACT(raw_data, '$.url') LIKE '%noticeUID=%'");
        })
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('cached_documents')
                        ->orWhereRaw('JSON_LENGTH(cached_documents) = 0');
                })->orWhere(function ($inner) {
                    $inner->whereNull('cached_articles')
                        ->orWhereRaw('JSON_LENGTH(cached_articles) = 0');
                });
            })
            ->where(function ($q) {
                $q->whereNull('tender_deadline')
                    ->orWhere('tender_deadline', '>=', now());
            })
            ->orderByDesc('published_at')
            ->limit((int) $this->option('limit'))
            ->get(['id', 'process_code', 'raw_data', 'secp_url', 'tender_deadline', 'cached_documents', 'cached_articles']);

        if ($bids->isEmpty()) {
            $this->info('No bids with missing documents or articles found.');

            return self::SUCCESS;
        }

        $this->info("Backfilling for {$bids->count()} bid(s)...");

        $filled = 0;
        $empty = 0;

        foreach ($bids as $bid) {
            $noticeUid = $bid->resolveNoticeUid();

            if (! $noticeUid) {
                continue;
            }

            $needsDocs = empty($bid->cached_documents);
            $needsArticles = empty($bid->cached_articles);

            $detail = $scraper->fetchDetail($noticeUid);

            if ($this->option('dry-run')) {
                $this->line("  {$bid->process_code} ({$noticeUid}): ".count($detail['documents']).' doc(s), '.count($detail['articles']).' article(s)');

                continue;
            }

            $updates = [];
            if ($needsDocs && ! empty($detail['documents'])) {
                $updates['cached_documents'] = $detail['documents'];
            }
            if ($needsArticles && ! empty($detail['articles'])) {
                $updates['cached_articles'] = $detail['articles'];
            }

            if (! empty($updates)) {
                $updates['cache_refreshed_at'] = now();
                $bid->update($updates);
                $label = implode(', ', array_filter([
                    isset($updates['cached_documents']) ? count($updates['cached_documents']).' doc(s)' : null,
                    isset($updates['cached_articles']) ? count($updates['cached_articles']).' article(s)' : null,
                ]));
                $this->line("  [OK] {$bid->process_code}: {$label}");
                $filled++;
            } else {
                $this->line("  [SKIP] {$bid->process_code}: still empty on portal");
                $empty++;
            }

            usleep(300_000);
        }

        $this->info("Done. Filled: {$filled} | Still empty: {$empty}");

        return self::SUCCESS;
    }
}

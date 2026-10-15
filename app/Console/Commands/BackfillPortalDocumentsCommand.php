<?php

namespace App\Console\Commands;

use App\Models\Bid;
use App\Services\PortalScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillPortalDocumentsCommand extends Command
{
    protected $signature = 'secp:backfill-portal-docs
                            {--limit=80 : Max bids to process per run}
                            {--stale-hours=6 : Re-scrape bids not refreshed within this many hours}
                            {--undated-days=90 : How far back to keep refreshing notices that carry no deadline}
                            {--dry-run : Show what would be fetched without saving}';

    protected $description = 'Backfill and periodically refresh cached_documents and cached_articles from the portal';

    public function handle(PortalScraperService $scraper): int
    {
        $staleHours = (int) $this->option('stale-hours');
        $staleCutoff = now()->subHours($staleHours);
        $undatedCutoff = now()->subDays((int) $this->option('undated-days'));

        // Laravel's JSON helpers rather than raw JSON_LENGTH/JSON_EXTRACT: the
        // raw form is MySQL-only, which left this query — the part that
        // actually broke — impossible to cover with a test.
        $bids = Bid::where(function ($q) {
            $q->whereNotNull('raw_data->notice_uid')
                ->orWhere('raw_data->url', 'like', '%noticeUID=%');
        })
            ->where(function ($q) use ($staleCutoff) {
                $q->whereNull('cached_documents')
                    ->orWhereJsonLength('cached_documents', 0)
                    ->orWhereNull('cached_articles')
                    ->orWhereJsonLength('cached_articles', 0)
                    ->orWhere('cache_refreshed_at', '<', $staleCutoff)
                    ->orWhereNull('cache_refreshed_at');
            })
            // A notice with no deadline has no natural end, so without the
            // published_at bound the 2019 leftovers stay eligible forever and,
            // being the stalest rows, sit permanently at the front of the queue.
            ->where(function ($q) use ($undatedCutoff) {
                $q->where('tender_deadline', '>=', now())
                    ->orWhere(function ($inner) use ($undatedCutoff) {
                        $inner->whereNull('tender_deadline')
                            ->where('published_at', '>=', $undatedCutoff);
                    });
            })
            // Stalest first, so every open notice comes round again. Ordering
            // by published_at meant the same newest rows were re-fetched every
            // hour and the rest of the backlog was never reached.
            ->orderBy('cache_refreshed_at')
            ->limit((int) $this->option('limit'))
            ->get(['id', 'process_code', 'raw_data', 'secp_url', 'published_at', 'tender_deadline',
                'cached_documents', 'cached_articles', 'cache_refreshed_at']);

        if ($bids->isEmpty()) {
            $this->info('No bids due for a document or article refresh.');

            return self::SUCCESS;
        }

        $this->info("Refreshing documents and articles for {$bids->count()} bid(s)...");

        $filled = 0;
        $updated = 0;
        $unchanged = 0;
        $failed = 0;
        $attempted = 0;

        foreach ($bids as $bid) {
            $noticeUid = $bid->resolveNoticeUid();

            if (! $noticeUid) {
                continue;
            }

            $attempted++;

            $hadDocs = count($bid->cached_documents ?? []);
            $hadArticles = count($bid->cached_articles ?? []);

            $detail = $scraper->fetchDetail($noticeUid);
            $docs = $detail['documents'];
            $articles = $detail['articles'];

            // fetchDetail() returns empty lists both when a fetch fails and
            // when a notice genuinely carries nothing, and it cannot say
            // which. Every real notice has at least one article, so treat an
            // all-empty response as a failed fetch: keep what is stored and
            // leave cache_refreshed_at alone so the next run retries it.
            if ($docs === [] && $articles === []) {
                $this->line("  [FALLÓ] {$bid->process_code}: el portal no devolvió nada; se conserva lo almacenado");
                $failed++;

                usleep(1_500_000);

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  {$bid->process_code} ({$noticeUid}): almacenado {$hadDocs}/{$hadArticles} → portal "
                    .count($docs).'/'.count($articles).' (doc/art)');

                usleep(1_500_000);

                continue;
            }

            // The portal is the source of truth: replace rather than only
            // filling blanks. Filling blanks alone is what left notices stuck
            // on whatever they had when first seen — amendments and late
            // pliegos published afterwards never appeared.
            $updates = ['cache_refreshed_at' => now()];

            if ($docs !== []) {
                $updates['cached_documents'] = $docs;
            }

            if ($articles !== []) {
                $updates['cached_articles'] = $articles;
            }

            $bid->update($updates);

            if ($hadDocs === 0 && $hadArticles === 0) {
                $this->line("  [NUEVO] {$bid->process_code}: ".count($docs).' doc(s), '.count($articles).' artículo(s)');
                $filled++;
            } elseif (count($docs) !== $hadDocs || count($articles) !== $hadArticles) {
                $this->line("  [CAMBIÓ] {$bid->process_code}: {$hadDocs}/{$hadArticles} → "
                    .count($docs).'/'.count($articles).' (doc/art)');
                $updated++;
            } else {
                $unchanged++;
            }

            usleep(1_500_000);
        }

        $this->info("Listo. Nuevos: {$filled} | Actualizados: {$updated} | Sin cambios: {$unchanged} | Fallidos: {$failed}");

        // Every fetch failing means the portal is unreachable or has started
        // refusing us, not that there was nothing to do. Reported as a failure
        // so the scheduler raises it instead of logging a quiet success, the
        // same way secp:scrape treats a total portal failure.
        if ($attempted > 0 && $failed === $attempted) {
            $this->error('El portal no respondió en ninguno de los '.$attempted.' intentos.');
            Log::error('[SECP] Portal document refresh failed for every bid attempted', ['attempted' => $attempted]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

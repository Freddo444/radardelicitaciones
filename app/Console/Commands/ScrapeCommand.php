<?php

namespace App\Console\Commands;

use App\Jobs\SendBidNotification;
use App\Models\Bid;
use App\Models\Company;
use App\Models\CompanyBid;
use App\Models\Setting;
use App\Services\BidMatchingService;
use App\Services\PortalScraperService;
use App\Services\WatchedBidNotifier;
use App\Support\PortalStatus;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The portal, read every fifteen minutes, as the source of truth for open
 * notices — DGCP's API has refused production since 2026-11-13.
 *
 * Two jobs per run. Discovery: the newest 100 of each procedure type plus a
 * sweep of every open notice, matched against the companies' rubros. Refresh:
 * every bid we track takes its status, deadline and amount from the same
 * sweep, and watched bids also have their documents re-read, so what a
 * customer sees trails the portal by one run.
 */
class ScrapeCommand extends Command
{
    protected $signature = 'secp:scrape {--dry-run : Show what would be imported without saving}';

    protected $description = 'Scrape the DGCP portal for new notices, and refresh tracked bids from it';

    private const EMPTY_RECHECK_HOURS = 6;

    private const CHECK_RETENTION_DAYS = 120;

    private const DISCOVERY_MAX_AGE_DAYS = 90;

    /** Tracked bids missing from the sweep are looked up one by one, up to this many a run. */
    private const MAX_REFERENCE_LOOKUPS = 25;

    /**
     * New detail pages read per run. The sweep surfaces every open notice,
     * and the first run after it shipped would otherwise have read several
     * hundred pages at once from a portal that had dropped connections
     * that morning. Newest first, so a backlog delays old notices, not new.
     */
    private const MAX_DISCOVERY_FETCHES = 40;

    /** A watched bid in a final status only changes by gaining documents; look every few hours. */
    private const FINAL_STATUSES = ['Cancelado', 'Proceso adjudicado y celebrado', 'Proceso desierto'];

    private const FINAL_RECHECK_HOURS = 6;

    private int $fetched = 0;

    public function handle(PortalScraperService $scraper, BidMatchingService $matcher, WatchedBidNotifier $notifier): int
    {
        $this->info('Scraping portal for open notices...');

        $newest = $scraper->scrapeAll();
        $failed = $scraper->failedProcedures();

        if ($failed !== []) {
            $this->warn(count($failed).' procedure type(s) did not respond: '.implode(', ', $failed));
        }

        // Every type failing means the portal is unreachable, not that the
        // day was quiet. Reported as a failure so it shows up instead of
        // reading as a successful empty run, which hid a three-day TLS outage
        // in October 2026.
        if ($newest->isEmpty() && count($failed) === $scraper->procedureCount()) {
            $this->error('Portal unreachable on every procedure type — this is not an empty result.');
            Log::error('[SECP] Portal scrape failed for every procedure type', ['failed' => $failed]);

            return self::FAILURE;
        }

        $hints = json_decode((string) Setting::get('portal_sweep_windows'), true) ?: [];
        $sweep = $scraper->sweepOpenNotices($hints);
        $open = $sweep['notices'];

        if (! $this->option('dry-run')) {
            Setting::set('portal_sweep_windows', json_encode($sweep['hints']));
        }

        $this->info("Sweep: {$open->count()} open notice(s) on the portal".
            ($sweep['failed'] ? ' — incomplete for '.implode(', ', $sweep['failed']) : '').
            ($sweep['capped'] ? " — {$sweep['capped']} window(s) still full" : ''));

        // Discovery candidates: the newest-100 lists (which also catch notices
        // without a deadline) and anything open the sweep found.
        $cutoff = now()->subDays(self::DISCOVERY_MAX_AGE_DAYS)->toDateTimeString();
        $candidates = $newest->keyBy('process_code');
        foreach ($open as $code => $row) {
            if (! $candidates->has($code) && ($row['published_at'] === null || $row['published_at'] >= $cutoff)) {
                $candidates->put($code, $row);
            }
        }
        $candidates = $candidates->filter(fn ($n) => PortalStatus::isOpenForOffers($n['portal_status'] ?? null));

        [$checked, $saved, $notified] = $this->discover($candidates, $scraper, $matcher);

        $listed = $open->union($newest->keyBy('process_code'));
        [$refreshed, $changed, $alerts] = $this->refresh($listed, $sweep['failed'] !== [], $scraper, $notifier);

        if (! $this->option('dry-run')) {
            Setting::set('last_scraped_at', now()->toDateTimeString());

            DB::table('portal_notice_checks')
                ->where('checked_at', '<', now()->subDays(self::CHECK_RETENTION_DAYS))
                ->delete();
        }

        $summary = "Portal scrape complete. Open on portal: {$open->count()} | New checked: {$checked} | ".
            "Detail pages fetched: {$this->fetched} | Saved: {$saved} | Notified: {$notified} | ".
            "Refreshed: {$refreshed} | Changed: {$changed} | Watch alerts: {$alerts} | ".
            'Listing requests: '.$scraper->listingRequests();
        $this->info($summary);
        Log::info("[SECP] {$summary}");

        return self::SUCCESS;
    }

    /**
     * @return array{0: int, 1: int, 2: int} checked, saved, notified
     */
    private function discover(Collection $candidates, PortalScraperService $scraper, BidMatchingService $matcher): array
    {
        $existingCodes = Bid::withTrashed()
            ->whereIn('process_code', $candidates->keys()->all())
            ->pluck('process_code')
            ->flip();

        $newNotices = $candidates->reject(fn ($n, $code) => $existingCodes->has($code));

        $this->info("{$newNotices->count()} new notice(s) not yet in our database.");

        if ($newNotices->isEmpty()) {
            return [0, 0, 0];
        }

        $rubroMap = $matcher->aggregateRubros();
        if (empty($rubroMap)) {
            $this->warn('No active rubros configured in any company. Skipping.');

            return [$newNotices->count(), 0, 0];
        }

        $rubrosByLevel = ['subclase' => [], 'clase' => [], 'familia' => []];
        foreach ($rubroMap as $code => $entry) {
            $rubrosByLevel[$entry['level']][$code] = $entry['name'];
        }

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
        $discoveryFetches = 0;
        $deferred = 0;

        $newNotices = $newNotices->sortByDesc(fn ($n) => $n['published_at'] ?? '');

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
                if ($discoveryFetches >= self::MAX_DISCOVERY_FETCHES) {
                    $deferred++;

                    continue;
                }

                $discoveryFetches++;
                $detail = $this->fetchDetail($scraper, $notice['notice_uid']);
                $unspscCodes = $detail['unspsc'];

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

            $matchedRubros = $this->matchRubros($unspscCodes, $rubrosByLevel);

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
            $detail ??= $this->fetchDetail($scraper, $notice['notice_uid']);

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
                    'status' => PortalStatus::toApp($notice['portal_status'] ?? null) ?? 'Proceso publicado',
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

                $companyIds = $matcher->fanOutToCompanies($bid, $matchedRubros->all(), $rubroMap);

                $this->info('        → '.count($companyIds).' empresa(s) vinculada(s)');

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
            }
        }

        if ($deferred > 0) {
            $this->info("{$deferred} older notice(s) left for the next run.");
        }

        return [$newNotices->count(), $saved, $notified];
    }

    /**
     * Bring every tracked bid in line with the portal: the open ones and
     * anything watched. Status, deadline and amount come from the listing
     * rows already in hand; a tracked bid the sweep did not see is looked up
     * by reference; watched bids also have their documents re-read.
     *
     * @param  Collection<string, array>  $listed  listing rows by process code
     * @return array{0: int, 1: int, 2: int} refreshed, changed, watch alerts
     */
    private function refresh(Collection $listed, bool $sweepIncomplete, PortalScraperService $scraper, WatchedBidNotifier $notifier): array
    {
        $watchedIds = array_flip($notifier->watchedBidIds());

        $tracked = Bid::where('tender_deadline', '>=', now())
            ->orWhereIn('id', array_keys($watchedIds))
            ->get();

        $refreshed = 0;
        $changed = 0;
        $alerts = 0;
        $lookups = 0;

        // Watched first, so they are never the ones the lookup cap leaves out.
        $tracked = $tracked->sortByDesc(fn (Bid $b) => isset($watchedIds[$b->id]));

        foreach ($tracked as $bid) {
            $watched = isset($watchedIds[$bid->id]);
            $row = $listed->get($bid->process_code);

            if ($row === null && in_array($bid->status, self::FINAL_STATUSES, true)
                && $bid->cache_refreshed_at?->gt(now()->subHours(self::FINAL_RECHECK_HOURS))) {
                continue;
            }

            // Open bids the sweep covers are always in it, whatever their
            // status; one missing means its deadline moved earlier or it was
            // withdrawn, and a closed watched bid is outside the sweep anyway.
            if ($row === null && ($watched || ! $sweepIncomplete) && $lookups < self::MAX_REFERENCE_LOOKUPS) {
                $lookups++;
                $row = $scraper->findByReference($bid->process_code);
            }

            $changes = $row ? $this->listingChanges($bid, $row) : [];

            if ($watched) {
                $changes = array_merge($changes, $this->documentChanges($bid, $row, $scraper));
            }

            if ($row) {
                $refreshed++;
            }

            if ($changes === []) {
                continue;
            }

            $changed++;
            $this->info("  [CAMBIO] {$bid->process_code}: ".implode(', ', $changes));

            if ($this->option('dry-run')) {
                continue;
            }

            $bid->save();

            if ($watched) {
                $notifier->notify($bid, $changes);
                $alerts++;
            }
        }

        return [$refreshed, $changed, $alerts];
    }

    /**
     * Applies the listing row to the bid (unsaved) and describes what moved.
     *
     * @return list<string>
     */
    private function listingChanges(Bid $bid, array $row): array
    {
        $changes = [];

        $status = PortalStatus::toApp($row['portal_status'] ?? null);
        if ($status && $status !== $bid->status) {
            $changes[] = "Estado: {$bid->status} → {$status}";
            $bid->status = $status;
            $bid->last_known_status = $status;
        }

        $deadline = $row['tender_deadline'] ?? null;
        if ($deadline && $bid->tender_deadline?->format('Y-m-d H:i') !== substr($deadline, 0, 16)) {
            $old = $bid->tender_deadline?->format('d/m/Y H:i') ?? '—';
            $bid->tender_deadline = $deadline;
            $changes[] = "Plazo: {$old} → {$bid->tender_deadline->format('d/m/Y H:i')}";
        }

        $amount = $row['amount_estimated'] ?? null;
        if ($amount && (! $bid->amount_estimated || abs($amount - $bid->amount_estimated) > 0.01)) {
            if ($bid->amount_estimated) {
                $changes[] = 'Monto: '.number_format($bid->amount_estimated, 2).' → '.number_format($amount, 2);
            }
            $bid->amount_estimated = $amount;
        }

        return $changes;
    }

    /**
     * A watched bid's documents, re-read from its detail page every run.
     *
     * @return list<string>
     */
    private function documentChanges(Bid $bid, ?array $row, PortalScraperService $scraper): array
    {
        $noticeUid = $bid->raw_data['notice_uid']
            ?? $row['notice_uid']
            ?? (preg_match('/noticeUID=([\w.]+)/', (string) ($bid->raw_data['url'] ?? $bid->secp_url), $m) ? $m[1] : null);

        if (! $noticeUid) {
            return [];
        }

        $detail = $this->fetchDetail($scraper, $noticeUid);

        // An all-empty page is a failed fetch, not a notice that lost its documents.
        if (! $detail['documents'] && ! $detail['articles'] && ! $detail['unspsc']) {
            return [];
        }

        $known = $bid->last_known_doc_count ?? count($bid->cached_documents ?? []);
        $now = count($detail['documents']);

        $bid->cached_documents = $detail['documents'] ?: $bid->cached_documents;
        $bid->cached_articles = $detail['articles'] ?: $bid->cached_articles;
        $bid->cache_refreshed_at = now();
        $bid->last_known_doc_count = $now;

        return $now > $known ? [($now - $known).' documento(s) nuevo(s)'] : [];
    }

    /** @return array{unspsc: array, documents: array, articles: array} */
    private function fetchDetail(PortalScraperService $scraper, string $noticeUid): array
    {
        // Spaced out: a first run, or a busy hour, can mean hundreds of
        // pages, and the portal is the one source still answering.
        if ($this->fetched > 0 && ! app()->runningUnitTests()) {
            usleep(300_000);
        }

        $this->fetched++;

        return $scraper->fetchDetail($noticeUid);
    }

    /** @param  array<string, array<string, string>>  $rubrosByLevel */
    private function matchRubros(array $unspscCodes, array $rubrosByLevel): Collection
    {
        $matched = collect();

        foreach ($unspscCodes as $code) {
            if (isset($rubrosByLevel['subclase'][$code])) {
                $matched->push(['code' => $code, 'name' => $rubrosByLevel['subclase'][$code]]);

                continue;
            }

            if (isset($rubrosByLevel['clase'][$code])) {
                $matched->push(['code' => $code, 'name' => $rubrosByLevel['clase'][$code]]);

                continue;
            }

            $clasePrefix = substr($code, 0, 6);
            foreach ($rubrosByLevel['clase'] as $rubroCode => $rubroName) {
                if (substr($rubroCode, 0, 6) === $clasePrefix) {
                    $matched->push(['code' => $rubroCode, 'name' => $rubroName]);
                    break;
                }
            }

            $familiaPrefix = substr($code, 0, 4);
            foreach ($rubrosByLevel['familia'] as $rubroCode => $rubroName) {
                if (substr($rubroCode, 0, 4) === $familiaPrefix) {
                    $matched->push(['code' => $rubroCode, 'name' => $rubroName]);
                    break;
                }
            }
        }

        return $matched->unique('code')->values();
    }
}

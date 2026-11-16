<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PortalScraperService
{
    private const PORTAL_BASE = 'https://comunidad.comprasdominicana.gob.do/Public/Tendering/ContractNoticeManagement';

    /**
     * Every procedure type the portal lists. The DGCP API poll used to be the
     * only source for the last four; from 2026-11-13 that API answered
     * production with a Cloudflare challenge, and these went dark with it.
     */
    private const SWEEP_HORIZON_DAYS = 365;

    /** A cold sweep took 64; this leaves room for a busy week without letting a fault run away. */
    private const SWEEP_MAX_REQUESTS = 150;

    /** The portal is fragile — on 2026-11-16 it dropped connections under a few hundred requests in ten minutes. */
    private const SWEEP_DELAY_MS = 1000;

    /** Consecutive failed searches after which the sweep stops for this run. */
    private const SWEEP_MAX_CONSECUTIVE_FAILURES = 2;

    /** Adjacent windows holding fewer rows than this together are merged next run. */
    private const SWEEP_MERGE_BELOW = 60;

    private const PROCEDURE_TYPES = [
        'DGCP-03-ComparacionDePrecios',       // ~97 open
        'DGCP-05-LicitacionPublicaNacional',   // ~100 open
        'DGCP-06-LicitacionPublicaInternacional', // ~88 open
        'DGCP-10-SorteoObras',                 // ~97 open (construction lottery)
        'DGCP-08-ProcesosExcepcion',           // ~100 open
        'DGCP-07-LicitacionRestringida',       // ~30 open
        'DGCP-11-SubastaInversa',              // ~100 open
        'DGCP-12-LicitacionPublicaAbreviada',  // ~89 open
        // High volume: the newest 100 span only a few hours (~14/h and ~28/h
        // on 2026-11-13), and the portal will not page past them. Scraped
        // every fifteen minutes that still catches every new notice; it
        // cannot backfill, which the DGCP API poll does when it is reachable.
        'DGCP-01-ComprasMenores',
        'DGCP-02-ComprasDebajoUmbral',
    ];

    /**
     * Scrape all open notices across important procedure types.
     * Uses AdvancedSearchAjax per type to bypass the 100-result pagination limit.
     * Each type returns <100 results, giving ~500 total open notices.
     * Filters out notices older than $maxAgeDays to avoid importing stale listings.
     *
     * @return Collection of parsed notice arrays
     */
    /** @var list<string> Procedure types whose search did not come back this run. */
    private array $failures = [];

    /** @return list<string> */
    public function failedProcedures(): array
    {
        return $this->failures;
    }

    public function procedureCount(): int
    {
        return count(self::PROCEDURE_TYPES);
    }

    public function scrapeAll(int $maxAgeDays = 90): Collection
    {
        $cutoff = now()->subDays($maxAgeDays);
        $allNotices = collect();
        $seenCodes = collect();
        $this->failures = [];

        foreach (self::PROCEDURE_TYPES as $procedure) {
            Log::debug("[PortalScraper] Searching procedure: {$procedure}");

            $html = $this->advancedSearch($procedure);
            if (! $html) {
                // Recorded rather than ignored: an empty result because the
                // portal is unreachable is not the same as an empty result
                // because nothing is published, and the caller must be able
                // to tell them apart.
                $this->failures[] = $procedure;

                continue;
            }

            $notices = $this->parsePage($html);

            foreach ($notices as $notice) {
                if ($seenCodes->contains($notice['process_code'])) {
                    continue;
                }

                // Skip notices older than the cutoff
                $pubDate = $notice['published_at'] ? new \DateTime($notice['published_at']) : null;
                if ($pubDate && $pubDate < $cutoff) {
                    continue;
                }

                // Skip notices whose offer deadline has already passed — portal doesn't
                // update estado_proceso on deadline, so we cannot trust that field
                $deadline = $notice['tender_deadline'] ? new \DateTime($notice['tender_deadline']) : null;
                if ($deadline && $deadline <= new \DateTime) {
                    continue;
                }

                $seenCodes->push($notice['process_code']);
                $allNotices->push($notice);
            }

            // Small delay between searches
            usleep(500_000);
        }

        return $allNotices;
    }

    public function __construct(private PortalTrustStore $trust) {}

    /** One repair attempt per instance, so a chain that cannot be fixed costs one AIA chase, not one per request. */
    private bool $repairAttempted = false;

    /**
     * Every portal request goes through here so trust is applied in one place:
     * the bundle (shipped intermediates plus any learned over AIA) and the OS
     * trust directory, so either a known intermediate or a complete served
     * chain is enough. See PortalTrustStore.
     */
    private function client(int $timeout): PendingRequest
    {
        $options = ['verify' => $this->trust->bundlePath()];

        if ($dir = $this->trust->osCaDirectory()) {
            $options['curl'] = [CURLOPT_CAPATH => $dir];
        }

        return Http::timeout($timeout)->withOptions($options);
    }

    /**
     * Run a portal flow; if it fails on trust, repair the chain and retry once.
     *
     * A CA change on the portal is what took the scraper down on 2026-10-13 and
     * again from 2026-11-02 for twelve days: the portal omits its
     * intermediates, so a new CA meant an issuer nothing on this side knew.
     * The repair fetches that issuer the way a browser does and keeps it only
     * if it chains to a root the OS already trusts.
     *
     * @template T
     *
     * @param  callable(): T  $flow
     * @return T
     */
    private function withTrustFallback(string $context, callable $flow)
    {
        try {
            return $flow();
        } catch (\Throwable $e) {
            if ($this->repairAttempted || ! $this->isTrustFailure($e)) {
                throw $e;
            }

            $this->repairAttempted = true;

            if (! $this->trust->repair()) {
                Log::error('[PortalScraper] The portal\'s certificate cannot be verified and the chain '
                    .'could not be repaired over AIA.', ['context' => $context, 'error' => $e->getMessage()]);

                throw $e;
            }

            return $flow();
        }
    }

    private function isTrustFailure(\Throwable $e): bool
    {
        $message = $e->getMessage();

        foreach (['cURL error 60', 'SSL certificate problem', 'unable to get local issuer',
            'certificate verify failed', 'self-signed certificate'] as $needle) {
            if (stripos($message, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * The newest 100 notices of one procedure type.
     */
    private function advancedSearch(string $procedure): ?string
    {
        return $this->searchListing(['procedure' => $procedure], "search {$procedure}");
    }

    /** @var array{mkey: string, jar: CookieJar}|null */
    private ?array $session = null;

    /** Listing requests made by this instance, for the run summary. */
    private int $listingRequests = 0;

    public function listingRequests(): int
    {
        return $this->listingRequests;
    }

    /**
     * One AdvancedSearchAjax request. The portal hands out a session cookie and
     * an mkey on its index page; both are kept for the instance rather than
     * fetched again before every search, which halves the requests of a sweep.
     * A failed search drops the session and tries once more with a fresh one.
     *
     * @param  array<string, string|int>  $filters
     */
    private function searchListing(array $filters, string $context): ?string
    {
        try {
            return $this->withTrustFallback($context, function () use ($filters, $context) {
                for ($attempt = 1; $attempt <= 2; $attempt++) {
                    $session = $this->session ??= $this->openSession();

                    if ($session === null) {
                        return null;
                    }

                    $this->listingRequests++;

                    $response = $this->client(60)
                        ->withHeaders(['Accept' => 'text/html'])
                        ->withOptions(['cookies' => $session['jar']])
                        ->get(self::PORTAL_BASE.'/AdvancedSearchAjax', [
                            'mkey' => $session['mkey'],
                            'perspective' => 'All',
                            'initAction' => 'Index',
                            'pageNumber' => 0,
                            'startIndex' => 1,
                            'endIndex' => 100,
                            'currentPagingStyle' => 0,
                            'displayAdvancedParams' => 'true',
                            'orderParam' => 'RequestOnlinePublishingDateDESC',
                            'searchExecuted' => 'True',
                        ] + $filters);

                    if ($response->successful()) {
                        return $response->body();
                    }

                    $this->session = null;
                }

                Log::warning("[PortalScraper] AdvancedSearchAjax failed ({$context})");

                return null;
            });
        } catch (\Throwable $e) {
            $this->session = null;
            Log::error("[PortalScraper] Search failed ({$context}): {$e->getMessage()}");

            return null;
        }
    }

    /** @return array{mkey: string, jar: CookieJar}|null */
    private function openSession(): ?array
    {
        $this->listingRequests++;

        $response = $this->client(30)
            ->withHeaders(['Accept' => 'text/html'])
            ->get(self::PORTAL_BASE.'/Index');

        if ($response->failed()) {
            return null;
        }

        if (! preg_match('/AdvancedSearchAjax[^;]*mkey=([a-f0-9_]+)/', $response->body(), $m)) {
            Log::warning('[PortalScraper] Could not extract mkey from initial page');

            return null;
        }

        return [
            'mkey' => $m[1],
            'jar' => CookieJar::fromArray(
                ['PublicSessionCookie' => $response->cookies()->getCookieByName('PublicSessionCookie')?->getValue() ?? ''],
                'comunidad.comprasdominicana.gob.do'
            ),
        ];
    }

    /**
     * Every open notice on the portal — any status, any procedure type —
     * read from its listing, keyed by process code.
     *
     * The listing shows the newest 100 per search and will not page, so the
     * sweep searches by deadline window, per procedure type (the portal
     * ignores the deadline filter without one), and halves any window that
     * comes back full. Windows that worked are returned as hints so the next
     * run starts from them instead of splitting from scratch. On 2026-11-16 a
     * cold sweep saw 884 open notices in 64 requests.
     *
     * @param  array<string, list<string>>  $hints  procedure => window boundaries (Y-m-d H:i)
     * @return array{notices: Collection, hints: array<string, list<string>>, failed: list<string>, capped: int}
     */
    public function sweepOpenNotices(array $hints = [], int $maxRequests = self::SWEEP_MAX_REQUESTS): array
    {
        $now = now()->startOfMinute();
        $end = $now->copy()->addDays(self::SWEEP_HORIZON_DAYS);
        $notices = collect();
        $newHints = [];
        $failed = [];
        $capped = 0;
        $budgetLeft = $maxRequests;
        $consecutiveFailures = 0;

        foreach (self::PROCEDURE_TYPES as $procedure) {
            $boundaries = collect($hints[$procedure] ?? [])
                ->map(fn ($b) => Carbon::parse($b))
                ->filter(fn ($b) => $b->gt($now) && $b->lt($end))
                ->sort()
                ->values();

            $edges = collect([$now])->concat($boundaries)->push($end)->values();
            $stack = [];
            for ($i = $edges->count() - 2; $i >= 0; $i--) {
                $stack[] = [$edges[$i], $edges[$i + 1]];
            }

            $done = [];

            while ($stack !== []) {
                if ($budgetLeft-- <= 0) {
                    Log::error('[PortalScraper] Open-notice sweep hit its request cap', ['cap' => $maxRequests]);
                    $failed[] = $procedure;
                    $newHints[$procedure] = $hints[$procedure] ?? [];

                    break 2;
                }

                [$from, $to] = array_pop($stack);

                $html = $this->searchListing([
                    'procedure' => $procedure,
                    'tendersDeadlineFrom' => $from->format('d/m/Y H:i'),
                    'tendersDeadlineTo' => $to->format('d/m/Y H:i'),
                ], "sweep {$procedure}");

                if ($html === null) {
                    $failed[] = $procedure;
                    $newHints[$procedure] = $hints[$procedure] ?? [];

                    // Failing twice in a row is the portal struggling, not one
                    // bad window: stop rather than add to it, and leave the
                    // remaining types to the next run.
                    if (++$consecutiveFailures >= self::SWEEP_MAX_CONSECUTIVE_FAILURES) {
                        Log::error('[PortalScraper] Open-notice sweep stopped: the portal is failing', [
                            'procedure' => $procedure,
                        ]);
                        $remaining = array_slice(self::PROCEDURE_TYPES, array_search($procedure, self::PROCEDURE_TYPES, true) + 1);
                        foreach ($remaining as $skipped) {
                            $failed[] = $skipped;
                            $newHints[$skipped] = $hints[$skipped] ?? [];
                        }

                        break 2;
                    }

                    continue 2;
                }

                $consecutiveFailures = 0;

                $rows = $this->parsePage($html);

                // The filter silently stops applying if the portal changes
                // its parameters; a full listing would then split forever.
                $outside = $rows->first(fn ($r) => $r['tender_deadline']
                    && ($r['tender_deadline'] < $from->format('Y-m-d H:i:00') || $r['tender_deadline'] > $to->format('Y-m-d H:i:59')));
                if ($outside) {
                    Log::error('[PortalScraper] The portal ignored the deadline filter; sweep abandoned for this type', [
                        'procedure' => $procedure, 'window' => [$from->toDateTimeString(), $to->toDateTimeString()],
                    ]);
                    $failed[] = $procedure;
                    $newHints[$procedure] = $hints[$procedure] ?? [];

                    continue 2;
                }

                $minutes = (int) $from->diffInMinutes($to);
                if ($rows->count() >= 100 && $minutes > 1) {
                    $mid = $from->copy()->addMinutes(intdiv($minutes, 2));
                    $stack[] = [$mid, $to];
                    $stack[] = [$from, $mid];

                    continue;
                }

                if ($rows->count() >= 100) {
                    $capped++;
                }

                foreach ($rows as $row) {
                    $notices->put($row['process_code'], $row + ['procedure' => $procedure]);
                }

                $done[] = [$from, $to, $rows->count()];

                if (! app()->runningUnitTests()) {
                    usleep(self::SWEEP_DELAY_MS * 1000);
                }
            }

            // Keep each boundary unless both windows around it are small
            // enough to merge, so windows follow the volume both ways.
            $kept = [];
            for ($i = 0; $i < count($done) - 1; $i++) {
                if ($done[$i][2] + $done[$i + 1][2] >= self::SWEEP_MERGE_BELOW) {
                    $kept[] = $done[$i][1]->format('Y-m-d H:i');
                } else {
                    $done[$i + 1][0] = $done[$i][0];
                    $done[$i + 1][2] += $done[$i][2];
                }
            }
            $newHints[$procedure] = $kept;
        }

        return [
            'notices' => $notices,
            'hints' => $newHints,
            'failed' => array_values(array_unique($failed)),
            'capped' => $capped,
        ];
    }

    /**
     * The listing row for one process code, or null if the portal has none.
     */
    public function findByReference(string $processCode): ?array
    {
        $html = $this->searchListing(['reference' => $processCode], "reference {$processCode}");

        return $html === null ? null : $this->parsePage($html)->firstWhere('process_code', $processCode);
    }

    /**
     * Fetch a notice detail page and extract UNSPSC codes.
     */
    public function fetchDetailUnspsc(string $noticeUid): array
    {
        return $this->fetchDetail($noticeUid)['unspsc'];
    }

    /**
     * Fetch a notice detail page and return both UNSPSC codes and the document list.
     *
     * @return array{unspsc: string[], documents: list<array{nombre_documento: string, tipo_documento: string, portal_file_id: string}>}
     */
    public function fetchDetail(string $noticeUid): array
    {
        $empty = ['unspsc' => [], 'documents' => [], 'articles' => []];

        try {
            return $this->withTrustFallback("detail {$noticeUid}", function () use ($noticeUid, $empty) {
                $response = $this->client(30)
                    ->withHeaders(['Accept' => 'text/html'])
                    ->get('https://comunidad.comprasdominicana.gob.do/Public/Tendering/OpportunityDetail/Index', [
                        'noticeUID' => $noticeUid,
                    ]);

                if ($response->failed()) {
                    return $empty;
                }

                $html = $response->body();

                return [
                    'unspsc' => $this->parseUnspsc($html),
                    'documents' => $this->parseDocuments($html),
                    'articles' => $this->parseArticles($html),
                ];
            });
        } catch (\Throwable $e) {
            Log::warning("[PortalScraper] Detail fetch failed for {$noticeUid}: {$e->getMessage()}");

            return $empty;
        }
    }

    /**
     * Download a portal document via the two-hop flow the portal uses:
     *   1. Fetch the notice detail page → session cookie + per-file mkey
     *   2. Hit DownloadFile → 200 HTML with a JS window.location.href redirect
     *   3. Follow that URL → actual file bytes
     *
     * @return array{body: string, content_type: string, filename: string}|null
     */
    public function downloadPortalDocument(string $noticeUid, string $fileId): ?array
    {
        try {
            return $this->withTrustFallback("download {$fileId}", function () use ($noticeUid, $fileId) {
                // Step 1: Fetch detail page — get session cookie and page HTML
                $detailRes = $this->client(30)
                    ->withHeaders(['Accept' => 'text/html'])
                    ->get('https://comunidad.comprasdominicana.gob.do/Public/Tendering/OpportunityDetail/Index', [
                        'noticeUID' => $noticeUid,
                    ]);

                if ($detailRes->failed()) {
                    return null;
                }

                // Step 2: Extract the per-file mkey from the onclick handler for this fileId.
                // Portal format: 'documentFileId=' + '12788233' + '&mkey=826ed0b5_822b_4799_8a9f_0e299a5381ca'
                $mkeyPattern = "/documentFileId='\\s*\\+\\s*'".preg_quote($fileId, '/')."'\\s*\\+\\s*'&mkey=([a-f0-9_]+)'/";
                if (! preg_match($mkeyPattern, $detailRes->body(), $m)) {
                    return null;
                }
                $mkey = $m[1];

                // Reuse this session for all subsequent requests — the mkey is bound to it
                $cookieJar = CookieJar::fromArray(
                    ['PublicSessionCookie' => $detailRes->cookies()->getCookieByName('PublicSessionCookie')?->getValue() ?? ''],
                    'comunidad.comprasdominicana.gob.do'
                );

                // Step 3: Hit DownloadFile — returns HTTP 200 with an HTML body containing a JS redirect,
                // not the file itself. Do not reject HTML here.
                $hopRes = $this->client(30)
                    ->withOptions(['cookies' => $cookieJar])
                    ->get('https://comunidad.comprasdominicana.gob.do/Public/Tendering/OpportunityDetail/DownloadFile', [
                        'documentFileId' => $fileId,
                        'mkey' => $mkey,
                    ]);

                if ($hopRes->failed()) {
                    return null;
                }

                // Step 4: Parse the JS redirect URL from the body
                // e.g. <script>window.location.href = '/Public/Archive/RetrieveFile/Index?DocumentId=...'</script>
                if (! preg_match("/window\\.location\\.href\\s*=\\s*['\"]([^'\"]+)['\"]/", $hopRes->body(), $r)) {
                    return null;
                }
                $fileUrl = 'https://comunidad.comprasdominicana.gob.do'.$r[1];

                // Step 5: Fetch the actual file
                $fileRes = $this->client(120)
                    ->withOptions(['cookies' => $cookieJar])
                    ->get($fileUrl);

                if ($fileRes->failed()) {
                    return null;
                }

                $contentType = $fileRes->header('Content-Type') ?? 'application/octet-stream';

                // Only reject HTML at this final step — a real file should never be HTML
                if (str_starts_with($contentType, 'text/html')) {
                    return null;
                }

                $disposition = $fileRes->header('Content-Disposition') ?? '';
                $filename = '';
                if (preg_match('/filename[^;=\n]*=[\'""]?([^\'""\n;]+)/', $disposition, $fm)) {
                    $filename = trim($fm[1], ' "\'');
                }

                return [
                    'body' => $fileRes->body(),
                    'content_type' => $contentType,
                    'filename' => $filename ?: "document-{$fileId}",
                ];
            });
        } catch (\Throwable $e) {
            Log::warning("[PortalScraper] Download failed for fileId={$fileId}: {$e->getMessage()}");

            return null;
        }
    }

    private function parseUnspsc(string $html): array
    {
        if (preg_match_all('/CategoryCode_LookupHiddenText"[^>]*value="(\d{8})"/', $html, $matches)) {
            return array_unique($matches[1]);
        }

        return [];
    }

    private function parseDocuments(string $html): array
    {
        $names = [];
        $types = [];
        $fileIds = [];

        // Document names: spnDocumentName_N
        if (preg_match_all('/spnDocumentName_(\d+)"[^>]*>\s*([^<]+?)\s*<\/span>/', $html, $m)) {
            foreach ($m[1] as $i => $idx) {
                $names[(int) $idx] = html_entity_decode(trim($m[2][$i]), ENT_QUOTES, 'UTF-8');
            }
        }

        // Document types: spnDocumentTypeSpan_N
        if (preg_match_all('/spnDocumentTypeSpan_(\d+)"[^>]*>\s*([^<]+?)\s*<\/span>/', $html, $m)) {
            foreach ($m[1] as $i => $idx) {
                $types[(int) $idx] = html_entity_decode(trim($m[2][$i]), ENT_QUOTES, 'UTF-8');
            }
        }

        // File IDs from onclick: 'documentFileId=' + '12788758'
        if (preg_match_all("/lnkDownloadLinkP3Gen_(\d+)[^>]*documentFileId='\s*\+\s*'(\d+)'/", $html, $m)) {
            foreach ($m[1] as $i => $idx) {
                $fileIds[(int) $idx] = $m[2][$i];
            }
        }

        $documents = [];
        foreach ($names as $idx => $name) {
            $documents[] = [
                'nombre_documento' => $name,
                'tipo_documento' => $types[$idx] ?? '',
                'portal_file_id' => $fileIds[$idx] ?? '',
            ];
        }

        return $documents;
    }

    private function parseArticles(string $html): array
    {
        preg_match_all(
            '/<tr[^>]+id="(incQuestionnaire[^"]+_BILN_\d+)"[^>]+class="[^"]*PriceListLine Item/',
            $html,
            $idMatches
        );

        if (empty($idMatches[1])) {
            return [];
        }

        $articles = [];
        foreach ($idMatches[1] as $rowId) {
            $p = preg_quote($rowId, '/');

            $unspsc = '';
            if (preg_match('/'.$p.'_CategoryCode_LookupHiddenText"[^>]+value="(\d+)"/', $html, $m)) {
                $unspsc = $m[1];
            }

            $desc = '';
            if (preg_match('/'.$p.'_Description"[^>]*data-prop="Desc"[^>]*>([^<]+)<\/span>/', $html, $m)) {
                $desc = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
            }

            $qty = null;
            if (preg_match('/'.$p.'_Quantity"[^>]*data-prop="Qtd"[^>]*>([^<]+)<\/span>/', $html, $m)) {
                $qty = (float) str_replace(',', '', trim($m[1]));
            }

            $unitPrice = null;
            if (preg_match('/'.$p.'_CeilingPrice"[^>]*data-prop="ClnP"[^>]*>([^<]+)<\/span>/', $html, $m)) {
                $unitPrice = (float) str_replace(',', '', trim($m[1]));
            }

            $totalPrice = null;
            if (preg_match('/'.$p.'_CeilingPriceTotal"[^>]*data-prop="ClnPT"[^>]*>([^<]+)<\/span>/', $html, $m)) {
                $totalPrice = (float) str_replace(',', '', trim($m[1]));
            }

            if ($desc === '' && $unspsc === '') {
                continue;
            }

            $articles[] = [
                'subclase' => $unspsc,
                'descripcion_articulo' => $desc,
                'cantidad' => $qty,
                'precio_unitario_estimado' => $unitPrice,
                'precio_total_estimado' => $totalPrice,
            ];
        }

        return $articles;
    }

    /**
     * Parse the Vortal HTML grid into structured notice data.
     */
    private function parsePage(string $html): Collection
    {
        $notices = collect();

        $references = $this->extractSpanValues($html, 'spnMatchingResultReference');
        $descriptions = $this->extractSpanValues($html, 'spnMatchingResultDescription');
        $authorities = $this->extractSpanValues($html, 'spnMatchingResultAuthorityName');
        $publishDates = $this->extractDateValues($html, 'dtmbNationalOfficialPublishingDate');
        $deadlines = $this->extractDateValues($html, 'dtmbDueDateForReceivingReplies');
        $amounts = $this->extractAmountValues($html);
        $noticeUids = $this->extractNoticeUids($html);
        $states = $this->extractSpanValues($html, 'spnMatchingResultContractNoticeState');

        $maxIndex = max(
            $references->keys()->max() ?? -1,
            $descriptions->keys()->max() ?? -1,
            0
        );

        for ($i = 0; $i <= $maxIndex; $i++) {
            $code = trim($references->get($i, ''));
            if (empty($code)) {
                continue;
            }

            $notices->push([
                'process_code' => $code,
                'title' => trim($descriptions->get($i, '')),
                'buyer_name' => trim($authorities->get($i, '')),
                'published_at' => $publishDates->get($i),
                'tender_deadline' => $deadlines->get($i),
                'amount_estimated' => $amounts->get($i),
                'currency' => 'DOP',
                'notice_uid' => $noticeUids->get($i),
                'portal_status' => trim($states->get($i, '')) ?: null,
                'portal_url' => $noticeUids->has($i)
                    ? 'https://comunidad.comprasdominicana.gob.do/Public/Tendering/OpportunityDetail/Index?noticeUID='.$noticeUids->get($i)
                    : null,
            ]);
        }

        return $notices;
    }

    private function extractSpanValues(string $html, string $prefix): Collection
    {
        $values = collect();

        if (preg_match_all(
            '/'.$prefix.'_(\d+)"[^>]*>([^<]*)</i',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $values->put((int) $match[1], html_entity_decode($match[2], ENT_QUOTES, 'UTF-8'));
            }
        }

        return $values;
    }

    private function extractDateValues(string $html, string $prefix): Collection
    {
        $values = collect();

        if (preg_match_all(
            '/'.$prefix.'_(\d+)_txt"[^>]*>\s*<span[^>]*>(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2})/i',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $index = (int) $match[1];
                $dateStr = trim($match[2]);

                try {
                    $dt = \DateTime::createFromFormat('d/m/Y H:i', $dateStr, new \DateTimeZone('America/Santo_Domingo'));
                    if ($dt) {
                        $values->put($index, $dt->format('Y-m-d H:i:s'));
                    }
                } catch (\Throwable) {
                }
            }
        }

        return $values;
    }

    private function extractAmountValues(string $html): Collection
    {
        $values = collect();

        if (preg_match_all(
            '/cbxBasePriceValue_(\d+)"[^>]*>([\d,.\s]+)\s*(?:Dominican\s+Pesos|Pesos\s+Dominicanos)/i',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $index = (int) $match[1];
                $cleaned = preg_replace('/[^\d.]/', '', str_replace(',', '', $match[2]));
                if ($cleaned !== '' && $cleaned !== '0') {
                    $values->put($index, (float) $cleaned);
                }
            }
        }

        return $values;
    }

    private function extractNoticeUids(string $html): Collection
    {
        $values = collect();

        if (preg_match_all('/noticeUID=\'\s*\+\s*\'(DO1\.NTC\.\d+)/', $html, $matches)) {
            foreach ($matches[1] as $i => $uid) {
                $values->put($i, $uid);
            }
        }

        return $values;
    }
}

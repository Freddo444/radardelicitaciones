<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PortalScraperService
{
    private const PORTAL_BASE = 'https://comunidad.comprasdominicana.gob.do/Public/Tendering/ContractNoticeManagement';

    /**
     * Procedure types worth scraping — high-value procurement modalities.
     * Each search returns all open notices of that type (typically <100).
     * We skip Compras Menores / Compras por Debajo del Umbral (low-value, high-volume).
     */
    private const PROCEDURE_TYPES = [
        'DGCP-03-ComparacionDePrecios',       // ~97 open
        'DGCP-05-LicitacionPublicaNacional',   // ~100 open
        'DGCP-06-LicitacionPublicaInternacional', // ~88 open
        'DGCP-10-SorteoObras',                 // ~97 open (construction lottery)
        'DGCP-08-ProcesosExcepcion',           // ~100 open
        'DGCP-07-LicitacionRestringida',       // ~30 open
    ];

    /**
     * Scrape all open notices across important procedure types.
     * Uses AdvancedSearchAjax per type to bypass the 100-result pagination limit.
     * Each type returns <100 results, giving ~500 total open notices.
     * Filters out notices older than $maxAgeDays to avoid importing stale listings.
     *
     * @return Collection of parsed notice arrays
     */
    public function scrapeAll(int $maxAgeDays = 90): Collection
    {
        $cutoff = now()->subDays($maxAgeDays);
        $allNotices = collect();
        $seenCodes = collect();

        foreach (self::PROCEDURE_TYPES as $procedure) {
            Log::debug("[PortalScraper] Searching procedure: {$procedure}");

            $html = $this->advancedSearch($procedure);
            if (! $html) {
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

                $seenCodes->push($notice['process_code']);
                $allNotices->push($notice);
            }

            // Small delay between searches
            usleep(500_000);
        }

        return $allNotices;
    }

    /**
     * Execute an AdvancedSearchAjax for a specific procedure type.
     * Requires two HTTP calls: initial page (to get session cookie + mkey), then AJAX search.
     */
    private function advancedSearch(string $procedure): ?string
    {
        try {
            // Step 1: Get session cookie and mkey from initial page load
            $initResponse = Http::timeout(30)
                ->withHeaders(['Accept' => 'text/html'])
                ->get(self::PORTAL_BASE.'/Index');

            if ($initResponse->failed()) {
                return null;
            }

            $cookies = $initResponse->cookies();
            $initHtml = $initResponse->body();

            // Extract mkey from AdvancedSearchAjax JS
            if (! preg_match('/AdvancedSearchAjax[^;]*mkey=([a-f0-9_]+)/', $initHtml, $m)) {
                Log::warning('[PortalScraper] Could not extract mkey from initial page');

                return null;
            }
            $mkey = $m[1];

            // Build cookie jar for the session
            $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray(
                ['PublicSessionCookie' => $cookies->getCookieByName('PublicSessionCookie')?->getValue() ?? ''],
                'comunidad.comprasdominicana.gob.do'
            );

            // Step 2: Execute AdvancedSearchAjax
            $searchResponse = Http::timeout(30)
                ->withHeaders(['Accept' => 'text/html'])
                ->withOptions(['cookies' => $cookieJar])
                ->get(self::PORTAL_BASE.'/AdvancedSearchAjax', [
                    'mkey' => $mkey,
                    'perspective' => 'All',
                    'initAction' => 'Index',
                    'pageNumber' => 0,
                    'startIndex' => 1,
                    'endIndex' => 100,
                    'currentPagingStyle' => 0,
                    'displayAdvancedParams' => 'true',
                    'orderParam' => 'RequestOnlinePublishingDateDESC',
                    'searchExecuted' => 'True',
                    'procedure' => $procedure,
                ]);

            if ($searchResponse->failed()) {
                Log::warning("[PortalScraper] AdvancedSearchAjax failed for {$procedure}");

                return null;
            }

            return $searchResponse->body();
        } catch (\Throwable $e) {
            Log::error("[PortalScraper] Search failed for {$procedure}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Fetch a notice detail page and extract UNSPSC codes.
     */
    public function fetchDetailUnspsc(string $noticeUid): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Accept' => 'text/html'])
                ->get('https://comunidad.comprasdominicana.gob.do/Public/Tendering/OpportunityDetail/Index', [
                    'noticeUID' => $noticeUid,
                ]);

            if ($response->failed()) {
                return [];
            }

            $html = $response->body();

            // Extract UNSPSC codes from CategoryCode hidden fields
            // Pattern: CategoryCode_LookupHiddenText" disabled="disabled" type="hidden" value="42192201"
            if (preg_match_all('/CategoryCode_LookupHiddenText"[^>]*value="(\d{8})"/', $html, $matches)) {
                return array_unique($matches[1]);
            }

            return [];
        } catch (\Throwable $e) {
            Log::warning("[PortalScraper] Detail fetch failed for {$noticeUid}: {$e->getMessage()}");

            return [];
        }
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

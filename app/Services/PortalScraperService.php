<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PortalScraperService
{
    private const PORTAL_URL = 'https://comunidad.comprasdominicana.gob.do/Public/Tendering/ContractNoticeManagement/Index';

    // Pagination via URL params doesn't work (Vortal requires session state).
    // We get 100 most-recent notices per fetch — run frequently to catch all.
    private const MAX_PAGES = 1;

    /**
     * Scrape recent notices from the portal, sorted newest-first.
     * Stops when notices fall outside the lookback window.
     *
     * @return Collection<int, array{
     *     process_code: string,
     *     title: string,
     *     buyer_name: string,
     *     published_at: string|null,
     *     tender_deadline: string|null,
     *     amount_estimated: float|null,
     *     currency: string,
     *     notice_uid: string|null,
     *     portal_url: string|null,
     * }>
     */
    public function scrapeRecent(int $hoursBack = 25): Collection
    {
        $cutoff = now()->subHours($hoursBack);
        $allNotices = collect();

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            Log::debug("[PortalScraper] Fetching page {$page}");

            $html = $this->fetchPage($page);
            if (! $html) {
                break;
            }

            $notices = $this->parsePage($html);
            if ($notices->isEmpty()) {
                break;
            }

            $recentOnThisPage = 0;

            foreach ($notices as $notice) {
                $pubDate = $notice['published_at'] ? new \DateTime($notice['published_at']) : null;

                if ($pubDate && $pubDate < $cutoff) {
                    // We've hit notices older than our window — stop entirely
                    Log::debug("[PortalScraper] Hit cutoff at {$notice['process_code']} published {$notice['published_at']}");

                    return $allNotices;
                }

                $allNotices->push($notice);
                $recentOnThisPage++;
            }

            // If every notice on this page was recent, there might be more on the next page
            if ($recentOnThisPage < $notices->count()) {
                break;
            }

            // Small delay between pages
            if ($page < self::MAX_PAGES) {
                usleep(500_000);
            }
        }

        return $allNotices;
    }

    /**
     * Fetch a single page of the portal listing, sorted by publish date descending.
     */
    private function fetchPage(int $page): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'text/html',
                    'Accept-Language' => 'es-DO,es;q=0.9',
                ])
                ->get(self::PORTAL_URL, [
                    'currentPage' => $page,
                    'orderBy' => 2,    // publish date
                    'orderDir' => 1,   // descending (newest first)
                ]);

            if ($response->failed()) {
                Log::warning("[PortalScraper] HTTP {$response->status()} on page {$page}");

                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::error("[PortalScraper] Failed to fetch page {$page}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Parse the Vortal HTML grid into structured notice data.
     */
    private function parsePage(string $html): Collection
    {
        $notices = collect();

        // Extract indexed fields from the Vortal grid spans
        $references = $this->extractSpanValues($html, 'spnMatchingResultReference');
        $descriptions = $this->extractSpanValues($html, 'spnMatchingResultDescription');
        $authorities = $this->extractSpanValues($html, 'spnMatchingResultAuthorityName');
        $publishDates = $this->extractDateValues($html, 'dtmbNationalOfficialPublishingDate');
        $deadlines = $this->extractDateValues($html, 'dtmbDueDateForReceivingReplies');
        $amounts = $this->extractAmountValues($html);
        $noticeUids = $this->extractNoticeUids($html);

        // The maximum index present across all fields
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

    /**
     * Extract span text values by their indexed ID pattern.
     * e.g., spnMatchingResultReference_0, spnMatchingResultReference_1, ...
     */
    private function extractSpanValues(string $html, string $prefix): Collection
    {
        $values = collect();

        // Pattern: id="...{prefix}_{index}" class="VortalSpan">TEXT</span>
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

    /**
     * Extract date values from Vortal date box spans.
     * Format in HTML: "25/03/2026 12:45 <font ...>(UTC -4 hours)</font>"
     */
    private function extractDateValues(string $html, string $prefix): Collection
    {
        $values = collect();

        // The date text is inside a nested span with a title attribute containing timezone info
        if (preg_match_all(
            '/'.$prefix.'_(\d+)_txt"[^>]*>\s*<span[^>]*>(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2})/i',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $index = (int) $match[1];
                $dateStr = trim($match[2]); // "25/03/2026 12:45"

                try {
                    // Parse DD/MM/YYYY HH:MM in AST (UTC-4)
                    $dt = \DateTime::createFromFormat('d/m/Y H:i', $dateStr, new \DateTimeZone('America/Santo_Domingo'));
                    if ($dt) {
                        $values->put($index, $dt->format('Y-m-d H:i:s'));
                    }
                } catch (\Throwable) {
                    // Skip unparseable dates
                }
            }
        }

        return $values;
    }

    /**
     * Extract monetary amounts from the VortalNumericSpan elements.
     * Format: "245,643.9 Dominican Pesos"
     */
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

    /**
     * Fetch a notice detail page and extract UNSPSC codes.
     * Returns the 8-digit UNSPSC codes found on the page.
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
     * Extract noticeUIDs in order of appearance.
     * They appear in JavaScript modal openers: noticeUID=' + 'DO1.NTC.1234567'
     */
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

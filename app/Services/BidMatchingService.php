<?php

namespace App\Services;

use App\Models\Bid;
use App\Models\Company;
use App\Models\CompanyBid;
use App\Models\Rubro;
use App\Models\Setting;

class BidMatchingService
{
    /**
     * Load all active rubros across companies, grouped by code for deduplication.
     *
     * Returns: [code => ['code', 'name', 'level', 'first_polled_at' (earliest), 'company_ids' => [...], 'rubro_ids' => [...]]]
     */
    public function aggregateRubros(): array
    {
        $allRubros = Rubro::withoutGlobalScopes()->where('active', true)->get();
        $map = [];

        foreach ($allRubros as $rubro) {
            $code = $rubro->code;
            if (! isset($map[$code])) {
                $map[$code] = [
                    'code' => $code,
                    'name' => $rubro->name,
                    'level' => $rubro->level,
                    'first_polled_at' => $rubro->first_polled_at,
                    'company_ids' => [],
                    'rubro_ids' => [],
                ];
            }

            $map[$code]['company_ids'][] = $rubro->company_id;
            $map[$code]['rubro_ids'][] = $rubro->id;

            // Use earliest first_polled_at (null = never polled = needs backfill)
            if ($rubro->first_polled_at === null) {
                $map[$code]['first_polled_at'] = null;
            } elseif ($map[$code]['first_polled_at'] !== null && $rubro->first_polled_at < $map[$code]['first_polled_at']) {
                $map[$code]['first_polled_at'] = $rubro->first_polled_at;
            }
        }

        return $map;
    }

    /**
     * Fan out a bid's matched rubros to per-company company_bid pivots.
     *
     * @param  Bid  $bid  The global bid record
     * @param  array  $matchedRubros  Array of ['code' => ..., 'name' => ...] that matched this bid
     * @param  array  $rubroMap  The aggregated rubro map from aggregateRubros()
     * @return array Company IDs that were matched
     */
    public function fanOutToCompanies(Bid $bid, array $matchedRubros, array $rubroMap): array
    {
        // Build per-company matched rubros
        $companyMatches = []; // company_id => [['code' => ..., 'name' => ...], ...]

        foreach ($matchedRubros as $match) {
            $code = $match['code'];
            if (! isset($rubroMap[$code])) {
                continue;
            }
            foreach ($rubroMap[$code]['company_ids'] as $companyId) {
                $companyMatches[$companyId][] = $match;
            }
        }

        // Create company_bid pivots
        foreach ($companyMatches as $companyId => $rubros) {
            CompanyBid::firstOrCreate(
                ['bid_id' => $bid->id, 'company_id' => $companyId],
                [
                    'matched_rubros' => $rubros,
                    'is_relevant' => Bid::computeRelevance($bid->title ?? '', $companyId),
                    'first_matched_at' => now(),
                ]
            );
        }

        return array_keys($companyMatches);
    }

    /**
     * Check if a bid should trigger a notification for a specific company,
     * based on that company's filter settings.
     */
    public function shouldNotify(Bid $bid, int $companyId): bool
    {
        $amount = $bid->amount_estimated;
        $modality = $bid->procurement_method;

        // Unconditional: never notify for a bid the user can no longer bid on
        if ($bid->tender_deadline && $bid->tender_deadline <= now()) {
            return false;
        }

        if (Setting::get('min_amount_filter', '0', $companyId) === '1') {
            $min = (float) Setting::get('min_amount_value', '0', $companyId);
            if ($min > 0 && $amount !== null && (float) $amount < $min) {
                return false;
            }
        }

        if (Setting::get('max_amount_filter', '0', $companyId) === '1') {
            $max = (float) Setting::get('max_amount_value', '0', $companyId);
            if ($max > 0 && $amount !== null && (float) $amount > $max) {
                return false;
            }
        }

        $excluded = json_decode(Setting::get('excluded_modalities', '[]', $companyId), true) ?: [];
        if ($modality && in_array($modality, $excluded)) {
            return false;
        }

        return true;
    }

    /**
     * "Sondear ahora" — re-match existing bids against a company's rubros.
     * No API calls. Creates company_bid pivots for any new matches found.
     *
     * @return int Number of new matches found
     */
    public function sondear(int $companyId): int
    {
        $companyRubros = Rubro::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('active', true)
            ->get()
            ->keyBy('code');

        if ($companyRubros->isEmpty()) {
            return 0;
        }

        $codes = $companyRubros->keys()->all();

        // Recent bids not yet linked to this company
        $bids = Bid::where('published_at', '>=', now()->subDays(90))
            ->whereDoesntHave('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->whereNotNull('cached_articles')
            ->get();

        $matched = 0;

        foreach ($bids as $bid) {
            $articles = $bid->cached_articles ?? [];
            $bidCodes = collect($articles)->pluck('codigoSubClaseUnspsc')
                ->merge(collect($articles)->pluck('codigoClaseUnspsc'))
                ->merge(collect($articles)->pluck('codigoFamiliaUnspsc'))
                ->filter()
                ->unique()
                ->all();

            $overlap = array_intersect($codes, $bidCodes);

            if (empty($overlap)) {
                continue;
            }

            $matchedRubros = [];
            foreach ($overlap as $code) {
                $rubro = $companyRubros[$code];
                $matchedRubros[] = ['code' => $code, 'name' => $rubro->name];
            }

            CompanyBid::create([
                'company_id' => $companyId,
                'bid_id' => $bid->id,
                'matched_rubros' => $matchedRubros,
                'is_relevant' => Bid::computeRelevance($bid->title ?? '', $companyId),
                'first_matched_at' => now(),
            ]);

            $matched++;
        }

        return $matched;
    }

    /**
     * Mark all newly-polled rubros (by code) as polled.
     */
    public function markRubrosPolled(string $code): void
    {
        Rubro::withoutGlobalScopes()
            ->where('code', $code)
            ->whereNull('first_polled_at')
            ->update(['first_polled_at' => now()]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bid extends Model
{
    protected $fillable = [
        'process_code', 'ocid', 'title', 'buyer_name', 'buyer_code',
        'procurement_method', 'status', 'amount_estimated', 'currency',
        'published_at', 'tender_deadline', 'secp_url',
        'raw_data', 'mipymes', 'mipymes_mujeres',
        'cached_documents', 'cached_articles', 'cached_contracts', 'cache_refreshed_at',
        'last_known_status', 'last_known_doc_count',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'cached_documents' => 'array',
        'cached_articles' => 'array',
        'cached_contracts' => 'array',
        'published_at' => 'datetime',
        'tender_deadline' => 'datetime',
        'cache_refreshed_at' => 'datetime',
        'amount_estimated' => 'decimal:2',
        'mipymes' => 'boolean',
        'mipymes_mujeres' => 'boolean',
        'last_known_doc_count' => 'integer',
    ];

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function watches()
    {
        return $this->hasMany(BidWatch::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_bid')
            ->using(CompanyBid::class)
            ->withPivot('matched_rubros', 'is_bookmarked', 'is_relevant', 'first_matched_at', 'notified_at')
            ->withTimestamps();
    }

    /**
     * Scope to bids matched to a specific company via company_bid pivot.
     */
    /**
     * Accessor: decode matched_rubros from pivot (arrives as JSON string).
     */
    public function getMatchedRubrosAttribute($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true) ?: [];
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->select('bids.*')
            ->addSelect(
                'company_bid.is_bookmarked',
                'company_bid.is_relevant',
                'company_bid.matched_rubros',
                'company_bid.notified_at as company_notified_at',
            )
            ->join('company_bid', 'bids.id', '=', 'company_bid.bid_id')
            ->where('company_bid.company_id', $companyId);
    }

    /**
     * Bids marked relevant for the current company (via pivot).
     */
    public function scopeRelevant(Builder $query): Builder
    {
        return $query->where('company_bid.is_relevant', true);
    }

    /**
     * Compute relevance for a bid title against a company's configured keywords.
     */
    public static function computeRelevance(string $title, ?int $companyId = null): bool
    {
        $keywords = json_decode(Setting::get('radar_keywords', '[]', $companyId), true) ?: [];
        if (empty($keywords)) {
            return false;
        }

        $lower = mb_strtolower($title);

        foreach ($keywords as $kw) {
            if (mb_strpos($lower, mb_strtolower(trim($kw))) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply the company's configured filters from Settings.
     */
    public function scopeFiltered(Builder $query, ?int $companyId = null): Builder
    {
        $cid = $companyId ?? currentCompany()?->id;

        if (Setting::get('min_amount_filter', '0', $cid) === '1') {
            $min = (float) (Setting::get('min_amount_value', '0', $cid) ?? 0);
            if ($min > 0) {
                $query->where(function ($q) use ($min) {
                    $q->whereNull('bids.amount_estimated')
                        ->orWhere('bids.amount_estimated', '>=', $min);
                });
            }
        }

        if (Setting::get('max_amount_filter', '0', $cid) === '1') {
            $max = (float) (Setting::get('max_amount_value', '0', $cid) ?? 0);
            if ($max > 0) {
                $query->where(function ($q) use ($max) {
                    $q->whereNull('bids.amount_estimated')
                        ->orWhere('bids.amount_estimated', '<=', $max);
                });
            }
        }

        $excluded = json_decode(Setting::get('excluded_modalities', '[]', $cid), true) ?: [];
        if (! empty($excluded)) {
            $query->where(function ($q) use ($excluded) {
                $q->whereNull('bids.procurement_method')
                    ->orWhereNotIn('bids.procurement_method', $excluded);
            });
        }

        if (Setting::get('open_deadline_filter', '0', $cid) === '1') {
            $query->where(function ($q) {
                $q->whereNull('bids.tender_deadline')
                    ->orWhere('bids.tender_deadline', '>=', now());
            });
        }

        // Negative keyword exclusion
        $excludedKw = json_decode(Setting::get('radar_excluded_keywords', '[]', $cid), true) ?: [];
        foreach ($excludedKw as $word) {
            $word = trim($word);
            if ($word !== '') {
                $query->where('bids.title', 'not like', "%{$word}%");
            }
        }

        return $query;
    }
}

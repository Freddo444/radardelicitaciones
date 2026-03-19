<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    protected $fillable = [
        'process_code', 'ocid', 'title', 'buyer_name', 'buyer_code',
        'procurement_method', 'status', 'amount_estimated', 'currency',
        'published_at', 'tender_deadline', 'matched_rubros', 'secp_url',
        'raw_data', 'notified_at', 'is_bookmarked', 'is_relevant', 'mipymes', 'mipymes_mujeres',
        'cached_documents', 'cached_articles', 'cached_contracts', 'cache_refreshed_at',
    ];

    protected $casts = [
        'matched_rubros' => 'array',
        'raw_data' => 'array',
        'cached_documents' => 'array',
        'cached_articles' => 'array',
        'cached_contracts' => 'array',
        'published_at' => 'datetime',
        'tender_deadline' => 'datetime',
        'notified_at' => 'datetime',
        'cache_refreshed_at' => 'datetime',
        'amount_estimated' => 'decimal:2',
        'is_bookmarked' => 'boolean',
        'is_relevant' => 'boolean',
        'mipymes' => 'boolean',
        'mipymes_mujeres' => 'boolean',
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

    /**
     * Bids matching at least one positive keyword.
     */
    public function scopeRelevant(Builder $query): Builder
    {
        return $query->where('is_relevant', true);
    }

    /**
     * Compute relevance for a bid title against configured keywords.
     */
    public static function computeRelevance(string $title): bool
    {
        $keywords = json_decode(Setting::get('radar_keywords', '[]'), true) ?: [];
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
     * Apply the user's configured filters from Settings.
     */
    public function scopeFiltered(Builder $query): Builder
    {
        if (Setting::get('min_amount_filter') === '1') {
            $min = (float) (Setting::get('min_amount_value') ?? 0);
            if ($min > 0) {
                $query->where(function ($q) use ($min) {
                    $q->whereNull('amount_estimated')
                        ->orWhere('amount_estimated', '>=', $min);
                });
            }
        }

        if (Setting::get('max_amount_filter') === '1') {
            $max = (float) (Setting::get('max_amount_value') ?? 0);
            if ($max > 0) {
                $query->where(function ($q) use ($max) {
                    $q->whereNull('amount_estimated')
                        ->orWhere('amount_estimated', '<=', $max);
                });
            }
        }

        $excluded = json_decode(Setting::get('excluded_modalities', '[]'), true) ?: [];
        if (! empty($excluded)) {
            $query->where(function ($q) use ($excluded) {
                $q->whereNull('procurement_method')
                    ->orWhereNotIn('procurement_method', $excluded);
            });
        }

        if (Setting::get('open_deadline_filter') === '1') {
            $query->where(function ($q) {
                $q->whereNull('tender_deadline')
                    ->orWhere('tender_deadline', '>=', now());
            });
        }

        // Negative keyword exclusion
        $excluded = json_decode(Setting::get('radar_excluded_keywords', '[]'), true) ?: [];
        foreach ($excluded as $word) {
            $word = trim($word);
            if ($word !== '') {
                $query->where('title', 'not like', "%{$word}%");
            }
        }

        return $query;
    }
}

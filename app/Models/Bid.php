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
        'raw_data', 'notified_at',
    ];

    protected $casts = [
        'matched_rubros' => 'array',
        'raw_data' => 'array',
        'published_at' => 'datetime',
        'tender_deadline' => 'datetime',
        'notified_at' => 'datetime',
        'amount_estimated' => 'decimal:2',
    ];

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
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

        return $query;
    }
}

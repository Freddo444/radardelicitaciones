<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CompanyBid extends Pivot
{
    protected $table = 'company_bid';

    public $incrementing = true;

    protected $fillable = [
        'company_id', 'bid_id', 'matched_rubros',
        'is_bookmarked', 'is_relevant', 'first_matched_at', 'notified_at',
    ];

    protected $casts = [
        'matched_rubros' => 'array',
        'is_bookmarked' => 'boolean',
        'is_relevant' => 'boolean',
        'first_matched_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }
}

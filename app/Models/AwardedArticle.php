<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AwardedArticle extends Model
{
    protected $fillable = [
        'contract_code',
        'process_code',
        'unspsc_familia',
        'unspsc_clase',
        'unspsc_subclase',
        'unspsc_description',
        'description',
        'unit_measure',
        'quantity',
        'unit_price',
        'total',
        'currency',
        'provider_name',
        'provider_rpe',
        'institution_name',
        'institution_code',
        'award_date',
        'api_hash',
        'raw_data',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total' => 'decimal:4',
        'award_date' => 'date',
        'raw_data' => 'array',
    ];
}

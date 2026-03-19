<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'contract_code',
        'process_code',
        'status',
        'provider_name',
        'provider_rpe',
        'institution_name',
        'institution_code',
        'amount',
        'currency',
        'payment_method',
        'payment_terms',
        'description',
        'award_date',
        'contract_date',
        'url',
        'raw_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'award_date' => 'date',
        'contract_date' => 'date',
        'raw_data' => 'array',
    ];
}

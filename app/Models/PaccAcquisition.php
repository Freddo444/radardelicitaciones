<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaccAcquisition extends Model
{
    protected $fillable = [
        'id_adquisicion',
        'uid_pacc',
        'institution_code',
        'institution_name',
        'description',
        'purpose',
        'start_date',
        'object_type',
        'estimated_amount',
        'currency',
        'modality',
        'mipymes',
        'mipymes_mujeres',
        'unspsc_familia',
        'unspsc_clase',
        'unspsc_subclase',
        'unspsc_description',
        'status',
        'api_hash',
        'raw_data',
    ];

    protected $casts = [
        'estimated_amount' => 'decimal:2',
        'start_date' => 'date',
        'mipymes' => 'boolean',
        'mipymes_mujeres' => 'boolean',
        'raw_data' => 'array',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PaccPlan::class, 'uid_pacc', 'uid_pacc');
    }
}

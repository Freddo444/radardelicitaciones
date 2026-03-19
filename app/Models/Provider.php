<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = [
        'rpe',
        'razon_social',
        'rnc',
        'status',
        'tipo_persona',
        'is_mipyme',
        'classification',
        'phone',
        'email',
        'address',
        'province',
        'municipality',
        'contact_name',
        'contact_position',
        'raw_data',
    ];

    protected $casts = [
        'is_mipyme' => 'boolean',
        'raw_data' => 'array',
    ];
}

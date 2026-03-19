<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaccPlan extends Model
{
    protected $fillable = [
        'uid_pacc',
        'institution_code',
        'institution_name',
        'period',
        'version',
        'responsible',
        'email',
        'url',
        'raw_data',
    ];

    protected $casts = [
        'period' => 'integer',
        'raw_data' => 'array',
    ];

    public function acquisitions(): HasMany
    {
        return $this->hasMany(PaccAcquisition::class, 'uid_pacc', 'uid_pacc');
    }
}

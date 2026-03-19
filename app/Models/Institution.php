<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    protected $fillable = [
        'code',
        'name',
        'acronym',
        'status',
        'address',
        'phone',
        'email',
        'notification_email',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];
}

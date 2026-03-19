<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rubro extends Model
{
    protected $fillable = ['code', 'name', 'level', 'active', 'notes', 'first_polled_at'];

    protected $casts = ['active' => 'boolean', 'first_polled_at' => 'datetime'];
}

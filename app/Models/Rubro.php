<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Rubro extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'code', 'name', 'level', 'active', 'notes', 'first_polled_at'];

    protected $casts = ['active' => 'boolean', 'first_polled_at' => 'datetime'];
}

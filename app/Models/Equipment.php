<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipment extends Model
{
    protected $fillable = [
        'company_id',
        'descripcion',
        'marca',
        'modelo',
        'anio',
        'tenencia',
        'capacidad',
        'condicion',
        'cantidad',
        'notas',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public static array $tenencias = [
        'propio' => 'Propio',
        'arrendado' => 'Arrendado',
        'leasing' => 'Leasing',
    ];

    public static array $condiciones = [
        'bueno' => 'Bueno',
        'regular' => 'Regular',
        'malo' => 'Malo',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function fichaLabel(): string
    {
        $parts = array_filter([$this->marca, $this->modelo, $this->anio]);

        return implode(' ', $parts);
    }
}

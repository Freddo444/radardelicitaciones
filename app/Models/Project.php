<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'company_id',
        'nombre',
        'cliente',
        'numero_contrato',
        'monto',
        'currency',
        'fecha_inicio',
        'fecha_fin',
        'descripcion',
        'unspsc_codigo',
        'contacto_cliente',
        'contacto_telefono',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'monto' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class)->orderBy('created_at', 'desc');
    }

    public function montoFormatted(): string
    {
        if ($this->monto === null) {
            return '—';
        }
        $symbol = $this->currency === 'USD' ? 'US$' : 'RD$';

        return $symbol.number_format($this->monto, 0, '.', ',');
    }

    public function periodoLabel(): string
    {
        $start = $this->fecha_inicio?->format('Y') ?? '?';
        $end = $this->fecha_fin ? $this->fecha_fin->format('Y') : 'Actualidad';

        return $start === $end ? $start : "{$start} — {$end}";
    }
}

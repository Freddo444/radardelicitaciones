<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfferRequirement extends Model
{
    protected $fillable = [
        'offer_id', 'parse_attempt_id', 'descripcion', 'tipo',
        'estado', 'sobre', 'source', 'superseded', 'notes', 'acceptance_reason',
    ];

    protected $casts = [
        'superseded' => 'boolean',
    ];

    public static array $tipos = [
        'documento' => 'Documento',
        'financiero' => 'Financiero',
        'personal' => 'Personal',
        'equipo' => 'Equipo',
        'experiencia' => 'Experiencia',
        'formato' => 'Formato',
        'otro' => 'Otro',
    ];

    public static array $estadoColors = [
        'PENDIENTE' => 'bg-gray-100 text-gray-600',
        'CUMPLE' => 'bg-green-50 text-green-700',
        'NO_CUMPLE' => 'bg-red-50 text-red-700',
        'ACEPTADO' => 'bg-amber-50 text-amber-700',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function parseAttempt(): BelongsTo
    {
        return $this->belongsTo(OfferParseAttempt::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OfferRequirementItem::class, 'offer_requirement_id');
    }

    public function estadoColor(): string
    {
        return self::$estadoColors[$this->estado] ?? 'bg-gray-100 text-gray-600';
    }

    /** Auto-update estado based on assigned items. */
    public function recalculateEstado(): void
    {
        if ($this->estado === 'ACEPTADO') {
            return;
        } // manual override — don't touch

        $hasItems = $this->items()->exists();
        $newEstado = $hasItems ? 'CUMPLE' : 'PENDIENTE';

        if ($newEstado !== $this->estado) {
            $this->update(['estado' => $newEstado]);
        }
    }
}

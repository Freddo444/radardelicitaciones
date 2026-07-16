<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use App\Support\Dates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'bid_id',
        'proceso_codigo', 'proceso_nombre', 'entidad_nombre', 'fecha_limite',
        'estado', 'enviado_at', 'notas',
    ];

    protected $casts = [
        'fecha_limite' => 'datetime',
        'enviado_at' => 'datetime',
    ];

    public static array $estados = [
        'borrador' => 'Borrador',
        'en_preparacion' => 'En preparación',
        'listo' => 'Listo',
        'enviado' => 'Enviado',
        'adjudicada' => 'Adjudicada',
        'perdida' => 'Perdida',
        'impugnacion' => 'Impugnación',
    ];

    public static array $estadoColors = [
        'borrador' => 'bg-gray-100 text-gray-700',
        'en_preparacion' => 'bg-blue-50 text-blue-700',
        'listo' => 'bg-green-50 text-green-700',
        'enviado' => 'bg-purple-50 text-purple-700',
        'adjudicada' => 'bg-yellow-50 text-yellow-800',
        'perdida' => 'bg-red-50 text-red-700',
        'impugnacion' => 'bg-orange-50 text-orange-700',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function parseAttempts(): HasMany
    {
        return $this->hasMany(OfferParseAttempt::class)->latest();
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(OfferRequirement::class);
    }

    public function activeRequirements(): HasMany
    {
        return $this->hasMany(OfferRequirement::class)->where('superseded', false);
    }

    public function personnel(): HasMany
    {
        return $this->hasMany(OfferPersonnel::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(OfferProject::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(OfferEquipment::class);
    }

    public function financials(): HasMany
    {
        return $this->hasMany(OfferFinancial::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(OfferSnapshot::class)->latest('assembled_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OfferEvent::class)->orderBy('event_date');
    }

    public function bidDocuments(): HasMany
    {
        return $this->hasMany(BidDocument::class)->latest();
    }

    public function generatedFiles(): HasMany
    {
        return $this->hasMany(OfferGeneratedFile::class)->latest('generated_at');
    }

    // ── State helpers ─────────────────────────────────────────────────

    public function isLocked(): bool
    {
        return in_array($this->estado, ['listo', 'enviado', 'adjudicada', 'perdida', 'impugnacion']);
    }

    public function isEditable(): bool
    {
        return in_array($this->estado, ['borrador', 'en_preparacion']);
    }

    public function activeParse(): ?OfferParseAttempt
    {
        return $this->parseAttempts()
            ->where('status', 'verified')
            ->first()
            ?? $this->parseAttempts()->first();
    }

    /** Check if all active requirements are CUMPLE or ACEPTADO. */
    public function allRequirementsMet(): bool
    {
        $reqs = $this->activeRequirements()->get();
        if ($reqs->isEmpty()) {
            return false;
        }

        return $reqs->every(fn ($r) => in_array($r->estado, ['CUMPLE', 'ACEPTADO']));
    }

    /** Check if offer can transition to `listo`. */
    public function canMarkListo(): bool
    {
        $parse = $this->activeParse();

        return $this->estado === 'en_preparacion'
            && $parse?->status === 'verified'
            && $this->allRequirementsMet();
    }

    /** Calendar days remaining until fecha_limite (0 = today, 1 = tomorrow, <0 = past). */
    public function diasRestantes(): ?int
    {
        return Dates::calendarDaysUntil($this->fecha_limite);
    }

    public function deadlineColor(): string
    {
        $days = $this->diasRestantes();
        if ($days === null) {
            return 'text-gray-400';
        }
        if ($days < 0) {
            return 'text-red-600';
        }
        if ($days <= 3) {
            return 'text-red-600';
        }
        if ($days <= 7) {
            return 'text-amber-600';
        }

        return 'text-gray-700';
    }
}

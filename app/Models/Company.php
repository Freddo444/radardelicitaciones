<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'owner_id',
        'razon_social',
        'nombre_comercial',
        'rnc',
        'direccion',
        'municipio',
        'provincia',
        'telefono',
        'email',
        'web',
        'rep_legal_nombre',
        'rep_legal_cedula',
        'rep_legal_cargo',
        'rep_legal_nacionalidad',
        'rep_legal_estado_civil',
        'rpe_numero',
        'registro_mercantil',
        'cpa_numero',
        'cpa_vence',
        'firma_path',
        'sello_path',
        'logo_path',
        'onboarding_dismissed_at',
    ];

    protected $casts = [
        'cpa_vence' => 'date',
        'onboarding_dismissed_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('joined_at')->withTimestamps();
    }

    public function bids(): BelongsToMany
    {
        return $this->belongsToMany(Bid::class, 'company_bid')
            ->using(CompanyBid::class)
            ->withPivot('matched_rubros', 'is_bookmarked', 'is_relevant', 'first_matched_at', 'notified_at')
            ->withTimestamps();
    }

    public function rubros(): HasMany
    {
        return $this->hasMany(Rubro::class);
    }

    public function vaultDocuments(): HasMany
    {
        return $this->hasMany(VaultDocument::class);
    }

    public function personnel(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function cpaExpiryDays(): ?int
    {
        return $this->cpa_vence ? (int) now()->diffInDays($this->cpa_vence, false) : null;
    }
}

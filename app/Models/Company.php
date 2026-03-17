<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
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
        'rpe_numero',
        'rpe_vence',
        'cpa_numero',
        'cpa_vence',
    ];

    protected $casts = [
        'rpe_vence' => 'date',
        'cpa_vence' => 'date',
    ];

    // Single-record accessor — always id=1
    public static function instance(): static
    {
        return static::firstOrNew(['id' => 1]);
    }

    public function vaultDocuments(): HasMany
    {
        return $this->hasMany(VaultDocument::class);
    }

    // Days until expiry, null if no date set
    public function rpeExpiryDays(): ?int
    {
        return $this->rpe_vence ? (int) now()->diffInDays($this->rpe_vence, false) : null;
    }

    public function cpaExpiryDays(): ?int
    {
        return $this->cpa_vence ? (int) now()->diffInDays($this->cpa_vence, false) : null;
    }
}

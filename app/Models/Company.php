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
        'rep_legal_nacionalidad',
        'rep_legal_estado_civil',
        'rpe_numero',
        'registro_mercantil',
        'cpa_numero',
        'cpa_vence',
        'firma_path',
        'sello_path',
        'logo_path',
    ];

    protected $casts = [
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

    public function cpaExpiryDays(): ?int
    {
        return $this->cpa_vence ? (int) now()->diffInDays($this->cpa_vence, false) : null;
    }
}

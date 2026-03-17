<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class VaultDocument extends Model
{
    protected $fillable = [
        'company_id',
        'category',
        'name',
        'filename',
        'path',
        'issued_at',
        'expires_at',
        'notes',
        'issuer',
        'document_number',
        'signed_by',
        'notarized',
        'copy_type',
        'language',
        'tags',
        'source_type',
        'source_id',
        'internal_only',
        'replaces_document_id',
        'superseded_at',
        'is_current',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'superseded_at' => 'datetime',
        'notarized' => 'boolean',
        'internal_only' => 'boolean',
        'is_current' => 'boolean',
        'tags' => 'array',
    ];

    public static array $categories = [
        'legal' => 'Legal',
        'habilitaciones' => 'Habilitaciones',
        'tributario' => 'Tributario',
        'seguridad_social' => 'Seguridad Social',
        'corporativo' => 'Corporativo',
    ];

    public static array $copyTypes = [
        'original' => 'Original',
        'copia' => 'Copia',
        'copia_certificada' => 'Copia certificada',
        'apostilla' => 'Apostilla',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function replacesDocument(): BelongsTo
    {
        return $this->belongsTo(VaultDocument::class, 'replaces_document_id');
    }

    // Scope: only current (non-superseded) versions
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    // Expiry status for UI color coding
    public function expiryStatus(): string
    {
        if (! $this->expires_at) {
            return 'none';
        }
        if ($this->expires_at->isPast()) {
            return 'expired';
        }
        if ($this->expires_at->diffInDays(now()) <= 30) {
            return 'expiring';
        }

        return 'valid';
    }

    // Create a new version of this document, superseding this one
    public function replaceWith(array $newData): static
    {
        $new = static::create(array_merge($newData, [
            'company_id' => $this->company_id,
            'category' => $newData['category'] ?? $this->category,
            'replaces_document_id' => $this->id,
            'is_current' => true,
        ]));

        $this->update([
            'is_current' => false,
            'superseded_at' => now(),
        ]);

        return $new;
    }

    // Get full version chain, newest to oldest
    public function versionChain(): Collection
    {
        $chain = collect([$this]);
        $current = $this;

        while ($current->replaces_document_id) {
            $current = static::find($current->replaces_document_id);
            if (! $current) {
                break;
            }
            $chain->push($current);
        }

        return $chain;
    }
}

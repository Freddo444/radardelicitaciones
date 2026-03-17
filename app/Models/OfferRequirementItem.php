<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferRequirementItem extends Model
{
    protected $fillable = [
        'offer_requirement_id', 'vault_ref_type', 'vault_ref_id', 'role_note',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(OfferRequirement::class, 'offer_requirement_id');
    }

    /** Resolve the referenced vault record. Returns null if not found. */
    public function resolveRef(): ?Model
    {
        return match ($this->vault_ref_type) {
            'vault_documents' => VaultDocument::find($this->vault_ref_id),
            'personnel' => Personnel::find($this->vault_ref_id),
            'projects' => Project::find($this->vault_ref_id),
            'equipment' => Equipment::find($this->vault_ref_id),
            'financial_records' => FinancialRecord::find($this->vault_ref_id),
            'offer_generated_files' => OfferGeneratedFile::find($this->vault_ref_id),
            default => null,
        };
    }

    public function refLabel(): string
    {
        $ref = $this->resolveRef();
        if (! $ref) {
            return '(eliminado)';
        }

        return match ($this->vault_ref_type) {
            'vault_documents' => $ref->name ?? $ref->nombre ?? '?',
            'personnel' => $ref->nombre ?? '?',
            'projects' => $ref->nombre ?? '?',
            'equipment' => $ref->descripcion ?? '?',
            'financial_records' => 'Año fiscal '.($ref->anio_fiscal ?? '?'),
            'offer_generated_files' => ($ref->form_code ?? '?').' — '.$ref->generated_at?->format('d/m/Y'),
            default => '?',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfferGeneratedFile extends Model
{
    protected $fillable = [
        'offer_id',
        'form_code',
        'source_context_json',
        'path',
        'sha256',
        'file_size',
        'supersedes_id',
        'generated_at',
        'generated_by',
    ];

    protected $casts = [
        'source_context_json' => 'array',
        'generated_at' => 'datetime',
    ];

    /** All form codes the generator supports, with human labels. */
    public static array $forms = [
        'SNCC.F.033' => 'F.033 — Oferta económica',
        'SNCC.F.034' => 'F.034 — Presentación de oferta',
        'SNCC.F.036' => 'F.036 — Equipos del oferente',
        'SNCC.F.037' => 'F.037 — Personal de plantilla del oferente',
        'SNCC.F.042' => 'F.042 — Información del oferente',
        'SNCC.D.045' => 'D.045 — Currículo del personal profesional',
        'SNCC.D.048' => 'D.048 — Experiencia profesional del personal',
        'SNCC.D.049' => 'D.049 — Experiencia como contratista',
        'DECL.JURADA' => 'Declaración jurada (Art. 14)',
        'DECL.COMPROMISO_ETICO' => 'Compromiso ético de proveedores',
    ];

    /** Forms that require selecting a personnel record. */
    public static array $requiresPersonnel = ['SNCC.D.045', 'SNCC.D.048'];

    /** Forms that require selecting a financial year. */
    public static array $requiresFinancialYear = [];

    /** Forms that require selecting projects. */
    public static array $requiresProjects = ['SNCC.D.049'];

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(OfferGeneratedFile::class, 'supersedes_id');
    }

    public function supersededBy(): HasMany
    {
        return $this->hasMany(OfferGeneratedFile::class, 'supersedes_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function fileSizeFormatted(): string
    {
        if (! $this->file_size) {
            return '—';
        }
        if ($this->file_size >= 1048576) {
            return round($this->file_size / 1048576, 1).' MB';
        }

        return round($this->file_size / 1024, 1).' KB';
    }
}

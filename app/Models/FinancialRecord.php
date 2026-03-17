<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialRecord extends Model
{
    protected $fillable = [
        'company_id', 'anio_fiscal', 'currency',
        'activo_total', 'activo_circulante', 'inventarios',
        'pasivo_total', 'pasivo_circulante', 'patrimonio',
        'ingresos', 'utilidad',
        'idx_solvencia', 'idx_liquidez', 'idx_endeudamiento', 'idx_capital_trabajo',
        'solvencia_override', 'liquidez_override', 'endeudamiento_override', 'capital_trabajo_override',
        'override_razon',
        'path_ir2', 'filename_ir2',
        'path_estado_financiero', 'filename_estado_financiero',
        'notas',
    ];

    protected $casts = [
        'activo_total' => 'decimal:2',
        'activo_circulante' => 'decimal:2',
        'inventarios' => 'decimal:2',
        'pasivo_total' => 'decimal:2',
        'pasivo_circulante' => 'decimal:2',
        'patrimonio' => 'decimal:2',
        'ingresos' => 'decimal:2',
        'utilidad' => 'decimal:2',
        'idx_solvencia' => 'decimal:4',
        'idx_liquidez' => 'decimal:4',
        'idx_endeudamiento' => 'decimal:4',
        'idx_capital_trabajo' => 'decimal:2',
        'solvencia_override' => 'decimal:4',
        'liquidez_override' => 'decimal:4',
        'endeudamiento_override' => 'decimal:4',
        'capital_trabajo_override' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Recalculate and store all four indices from current balance values.
     * Division by zero returns null. Saves the record.
     */
    public function recalculateIndices(): void
    {
        $this->idx_solvencia = $this->safeDivide($this->activo_total, $this->pasivo_total);
        $this->idx_liquidez = $this->safeDivide(
            ($this->activo_circulante ?? 0) - ($this->inventarios ?? 0),
            $this->pasivo_circulante
        );
        $this->idx_endeudamiento = $this->safeDivide($this->pasivo_total, $this->activo_total);
        $this->idx_capital_trabajo = $this->activo_circulante !== null && $this->pasivo_circulante !== null
            ? (float) $this->activo_circulante - (float) $this->pasivo_circulante
            : null;

        $this->saveQuietly();
    }

    /** Returns effective solvencia: override if set, else calculated. */
    public function solvencia(): ?string
    {
        return $this->solvencia_override ?? $this->idx_solvencia;
    }

    public function liquidez(): ?string
    {
        return $this->liquidez_override ?? $this->idx_liquidez;
    }

    public function endeudamiento(): ?string
    {
        return $this->endeudamiento_override ?? $this->idx_endeudamiento;
    }

    public function capitalTrabajo(): ?string
    {
        return $this->capital_trabajo_override ?? $this->idx_capital_trabajo;
    }

    public function formatIndice(?string $value, int $dp = 2): string
    {
        return $value === null ? 'N/D' : number_format((float) $value, $dp);
    }

    public function formatMonto(?string $value): string
    {
        if ($value === null) {
            return '—';
        }
        $symbol = $this->currency === 'USD' ? 'US$' : 'RD$';

        return $symbol.number_format((float) $value, 0, '.', ',');
    }

    private function safeDivide($numerator, $denominator): ?float
    {
        if ($numerator === null || $denominator === null) {
            return null;
        }
        $d = (float) $denominator;
        if ($d == 0.0) {
            return null;
        }

        return round((float) $numerator / $d, 4);
    }
}

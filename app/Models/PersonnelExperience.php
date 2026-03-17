<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelExperience extends Model
{
    protected $table = 'personnel_experience';

    protected $fillable = [
        'person_id',
        'empresa',
        'cargo',
        'fecha_inicio',
        'fecha_fin',
        'descripcion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'person_id');
    }

    public function periodoLabel(): string
    {
        $start = $this->fecha_inicio->format('m/Y');
        $end = $this->fecha_fin ? $this->fecha_fin->format('m/Y') : 'Actualidad';

        return "{$start} — {$end}";
    }
}

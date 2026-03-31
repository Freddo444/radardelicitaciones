<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Personnel extends Model
{
    use BelongsToCompany;

    protected $table = 'personnel';

    protected $fillable = [
        'company_id',
        'nombre',
        'cedula',
        'fecha_nac',
        'cargo',
        'nivel_educativo',
        'titulo',
        'institucion',
        'anio_titulo',
        'idiomas',
        'skills',
        'photo_path',
        'active',
    ];

    protected $casts = [
        'fecha_nac' => 'date',
        'active' => 'boolean',
        'idiomas' => 'array',
        'skills' => 'array',
    ];

    public static array $nivelesEducativos = [
        'bachiller' => 'Bachiller',
        'tecnico' => 'Técnico / Tecnólogo',
        'universitario' => 'Universitario (Grado)',
        'posgrado' => 'Posgrado / Maestría',
        'doctorado' => 'Doctorado',
    ];

    public function experiences(): HasMany
    {
        return $this->hasMany(PersonnelExperience::class, 'person_id')->orderBy('fecha_inicio', 'desc');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}

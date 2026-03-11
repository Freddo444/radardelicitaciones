<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogItem extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'subclase';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'subclase', 'descripcion_subclase',
        'clase',    'descripcion_clase',
        'familia',  'descripcion_familia',
        'segmento', 'descripcion_segmento',
    ];
}

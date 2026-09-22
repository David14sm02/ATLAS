<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatEntidad extends Model
{
    use HasFactory;

    protected $table = 'cat_entidades';

    protected $fillable = [
        'clave_inegi',
        'nombre',
        'abreviatura',
    ];

    /**
     * Planteles ubicados en esta entidad federativa.
     */
    public function planteles(): HasMany
    {
        return $this->hasMany(CatPlantel::class, 'entidad_id')->orderBy('nombre');
    }
}

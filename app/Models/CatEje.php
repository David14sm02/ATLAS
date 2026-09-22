<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatEje extends Model
{
    use HasFactory;

    protected $table = 'cat_ejes';

    protected $fillable = [
        'clave',
        'nombre',
        'icono',
        'orden',
    ];

    /**
     * Submódulos que pertenecen a este eje.
     */
    public function submodulos(): HasMany
    {
        return $this->hasMany(CatSubmodulo::class, 'eje_id')->orderBy('clave');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatSubmodulo extends Model
{
    use HasFactory;

    protected $table = 'cat_submodulos';

    protected $fillable = [
        'eje_id',
        'clave',
        'nombre',
        'descripcion',
        'activo_mvp',
    ];

    protected function casts(): array
    {
        return [
            'activo_mvp' => 'boolean',
        ];
    }

    /**
     * Eje rector al que pertenece el submódulo.
     */
    public function eje(): BelongsTo
    {
        return $this->belongsTo(CatEje::class, 'eje_id');
    }

    /**
     * Registros de captura asociados a este submódulo.
     */
    public function registros(): HasMany
    {
        return $this->hasMany(RepRegistroBase::class, 'submodulo_id');
    }

    /**
     * Nombre formal compuesto (ej. "2.2 Modelo Talento Emprendedor (MTE)").
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->clave} {$this->nombre}";
    }
}

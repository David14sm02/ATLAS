<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatPlantel extends Model
{
    use HasFactory;

    protected $table = 'cat_planteles';

    protected $fillable = [
        'entidad_id',
        'clave_tecnm',
        'nombre',
        'municipio',
        'sostenimiento',
        'latitud',
        'longitud',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
        ];
    }

    /**
     * Entidad federativa a la que pertenece el plantel.
     */
    public function entidad(): BelongsTo
    {
        return $this->belongsTo(CatEntidad::class, 'entidad_id');
    }

    /**
     * Usuarios asignados a este plantel (operadores/enlaces locales).
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'plantel_id');
    }

    /**
     * Registros de captura reportados por este plantel.
     */
    public function registros(): HasMany
    {
        return $this->hasMany(RepRegistroBase::class, 'plantel_id');
    }

    /**
     * Bitácoras de carga masiva de Excel de este plantel.
     */
    public function bitacoras(): HasMany
    {
        return $this->hasMany(RepBitacoraCarga::class, 'plantel_id');
    }
}

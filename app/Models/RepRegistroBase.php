<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepRegistroBase extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rep_registros_base';

    protected $fillable = [
        'plantel_id',
        'submodulo_id',
        'periodo_id',
        'user_id',
        'estado',
        'docentes_mujeres',
        'docentes_hombres',
        'estudiantes_mujeres',
        'estudiantes_hombres',
        'detalles_adicionales',
        'archivo_evidencia',
        'observaciones',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'detalles_adicionales' => 'array',
            'docentes_mujeres' => 'integer',
            'docentes_hombres' => 'integer',
            'estudiantes_mujeres' => 'integer',
            'estudiantes_hombres' => 'integer',
            'total_docentes' => 'integer',
            'total_estudiantes' => 'integer',
            'total_general' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Plantel que emitió el reporte (Multi-Tenancy).
     */
    public function plantel(): BelongsTo
    {
        return $this->belongsTo(CatPlantel::class, 'plantel_id');
    }

    /**
     * Submódulo del marco institucional al que pertenece este reporte.
     */
    public function submodulo(): BelongsTo
    {
        return $this->belongsTo(CatSubmodulo::class, 'submodulo_id');
    }

    /**
     * Periodo trimestral del reporte.
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(CatPeriodo::class, 'periodo_id');
    }

    /**
     * Usuario capturista que creó o registró los datos.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Usuario directivo que autorizó o publicó el reporte.
     */
    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    // Scopes de Conveniencia y Filtrado
    public function scopePublicados(Builder $query): Builder
    {
        return $query->where('estado', 'PUBLICADO');
    }

    public function scopeBorradores(Builder $query): Builder
    {
        return $query->where('estado', 'BORRADOR');
    }

    public function scopeDeSubmodulo(Builder $query, string $clave): Builder
    {
        return $query->whereHas('submodulo', fn (Builder $q) => $q->where('clave', $clave));
    }

    public function scopeParaPlantel(Builder $query, int $plantelId): Builder
    {
        return $query->where('plantel_id', $plantelId);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatPeriodo extends Model
{
    use HasFactory;

    protected $table = 'cat_periodos';

    protected $fillable = [
        'anio',
        'trimestre',
        'fecha_inicio',
        'fecha_limite',
        'bloqueado',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'trimestre' => 'integer',
            'fecha_inicio' => 'date',
            'fecha_limite' => 'date',
            'bloqueado' => 'boolean',
        ];
    }

    /**
     * Registros de captura asociados a este periodo trimestral.
     */
    public function registros(): HasMany
    {
        return $this->hasMany(RepRegistroBase::class, 'periodo_id');
    }

    /**
     * Etiqueta amigable de lectura (ej. "2026 - Trimestre 3 (Q3)").
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->anio} - Trimestre {$this->trimestre} (Q{$this->trimestre})";
    }
}

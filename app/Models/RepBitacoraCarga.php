<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepBitacoraCarga extends Model
{
    use HasFactory;

    protected $table = 'rep_bitacora_cargas';

    protected $fillable = [
        'plantel_id',
        'user_id',
        'submodulo_id',
        'periodo_id',
        'archivo_nombre',
        'estatus',
        'filas_procesadas',
        'filas_con_error',
        'errores_detalle',
    ];

    protected function casts(): array
    {
        return [
            'filas_procesadas' => 'integer',
            'filas_con_error' => 'integer',
            'errores_detalle' => 'array',
        ];
    }

    public function plantel(): BelongsTo
    {
        return $this->belongsTo(CatPlantel::class, 'plantel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function submodulo(): BelongsTo
    {
        return $this->belongsTo(CatSubmodulo::class, 'submodulo_id');
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(CatPeriodo::class, 'periodo_id');
    }
}

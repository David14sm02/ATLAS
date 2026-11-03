<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class RepDictamenLog extends Model
{
    public $timestamps = false;

    protected $table = 'rep_dictamenes_log';

    protected $fillable = [
        'plantel_id',
        'registro_id',
        'modulo',
        'entidad_tipo',
        'entidad_id_ref',
        'user_id',
        'usuario_nombre',
        'usuario_rol_clave',
        'usuario_cargo',
        'accion',
        'estatus_anterior',
        'estatus_nuevo',
        'motivo_rechazo_tipo',
        'observaciones',
        'valores_anteriores',
        'valores_nuevos',
        'payload_snapshot',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'valores_anteriores' => 'array',
            'valores_nuevos' => 'array',
            'payload_snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Blindaje de Caja Negra (Append-Only):
     * Bloquea a nivel de ORM cualquier intento de modificar o eliminar un registro de auditoría.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Violación de integridad forense: Los registros de la bitácora de auditoría son inmutables y no pueden modificarse.');
        });

        static::deleting(function (): void {
            throw new LogicException('Violación de integridad forense: Los registros de la bitácora de auditoría son inmutables y no pueden eliminarse.');
        });
    }

    public function plantel(): BelongsTo
    {
        return $this->belongsTo(CatPlantel::class, 'plantel_id');
    }

    public function registro(): BelongsTo
    {
        return $this->belongsTo(RepRegistroBase::class, 'registro_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope para filtrar automáticamente según el rol del usuario autenticado.
     */
    public function scopeVisibleParaUsuario(Builder $query, User $user): Builder
    {
        if ($user->esNacional()) {
            return $query;
        }

        if ($user->esSupervisorTerritorial() && $user->entidad_id) {
            return $query->whereHas('plantel', fn (Builder $q) => $q
                ->where('entidad_id', $user->entidad_id)
                ->where('sostenimiento', 'DESCENTRALIZADO'));
        }

        if ($user->esAdminPlantel() && $user->plantel_id) {
            return $query->where('plantel_id', $user->plantel_id);
        }

        return $query->whereRaw('1 = 0');
    }
}

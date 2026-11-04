<?php

namespace App\Models\Traits;

use App\Models\CatPlantel;
use App\Models\Scopes\PlantelScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToPlantel
{
    /**
     * Inicializa el trait de Multi-Tenancy Scoped.
     * Registra el Global Scope y asigna automáticamente el plantel al crear registros.
     */
    public static function bootBelongsToPlantel(): void
    {
        static::addGlobalScope(new PlantelScope);

        static::creating(function (Model $model): void {
            if (Auth::check()) {
                $user = Auth::user();

                if ($user && ! $user->esNacional() && $user->plantel_id) {
                    $model->plantel_id = $user->plantel_id;
                }
            }
        });
    }

    /**
     * Relación con el Plantel / Instituto Tecnológico al que pertenece el registro.
     */
    public function plantel(): BelongsTo
    {
        return $this->belongsTo(CatPlantel::class, 'plantel_id');
    }

    /**
     * Scope para omitir el aislamiento Multi-Tenancy y consultar datos consolidados.
     */
    public function scopeDeTodosLosPlanteles(Builder $query): Builder
    {
        return $query->withoutGlobalScope(PlantelScope::class);
    }

    /**
     * Scope para forzar la consulta a un plantel específico.
     */
    public function scopeParaPlantel(Builder $query, int $plantelId): Builder
    {
        return $query->withoutGlobalScope(PlantelScope::class)
            ->where($this->qualifyColumn('plantel_id'), $plantelId);
    }
}


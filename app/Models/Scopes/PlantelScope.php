<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class PlantelScope implements Scope
{
    /**
     * Aplica el aislamiento Multi-Tenancy (Scoped) por Plantel.
     * Si el usuario es directivo nacional (sin plantel asignado), tiene visibilidad de todos los planteles.
     * Si el usuario pertenece a un plantel específico, solo puede consultar registros de su plantel.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user && ! $user->esNacional() && $user->plantel_id) {
                $builder->where($model->qualifyColumn('plantel_id'), $user->plantel_id);
            }
        }
    }
}


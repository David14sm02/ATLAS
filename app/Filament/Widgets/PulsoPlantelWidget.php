<?php

namespace App\Filament\Widgets;

use App\Models\CatPeriodo;
use App\Models\CatSubmodulo;
use App\Models\RepRegistroBase;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PulsoPlantelWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user ? (! $user->esNacional() && ! $user->esSupervisorTerritorial()) : false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $plantel = $user?->plantel;
        $periodo = CatPeriodo::where('bloqueado', false)->orderBy('anio')->orderBy('trimestre')->first()
            ?? CatPeriodo::orderByDesc('anio')->orderByDesc('trimestre')->first();

        $totalSubmodulosMvp = CatSubmodulo::where('activo_mvp', true)->count() ?: 2;
        $submodulosAsignadosCount = $user?->esAdminPlantel()
            ? $totalSubmodulosMvp
            : ($user?->submodulosAsignados()->count() ?: 0);

        $publicadosCount = 0;
        $borradoresCount = 0;

        if ($plantel && $periodo) {
            $publicadosCount = RepRegistroBase::where('plantel_id', $plantel->id)
                ->where('periodo_id', $periodo->id)
                ->where('estado', 'PUBLICADO')
                ->count();

            $borradoresCount = RepRegistroBase::where('plantel_id', $plantel->id)
                ->where('periodo_id', $periodo->id)
                ->where('estado', 'BORRADOR')
                ->count();
        }

        $regimenLabel = match ($plantel?->tipo_institucion) {
            'CENTRO_ESPECIALIZADO' => 'Centro Especializado (CRODE / CIIDET / CENIDET)',
            'IT_DESCENTRALIZADO' => 'Instituto Descentralizado (OPD)',
            default => 'Instituto Tecnológico Federal',
        };

        return [
            Stat::make('Instituto / Centro Asignado', $plantel?->siglas ?? 'Sin plantel')
                ->description(($plantel?->nombre ?? 'Usuario Local')." • {$regimenLabel}")
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),

            Stat::make('Perfil Institucional Activo', $user?->cargo ?? $user?->rol_clave ?? 'Operador')
                ->description("Clave de Rol: {$user?->rol_clave} • {$user?->zona_geografica}")
                ->descriptionIcon('heroicon-m-identification')
                ->color('info'),

            Stat::make('Avance Trimestral del Plantel', "{$publicadosCount} / {$submodulosAsignadosCount} Publicados")
                ->description("{$borradoresCount} en borrador • Periodo: ".($periodo?->nombre_corto ?? '2026'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($publicadosCount >= $submodulosAsignadosCount && $submodulosAsignadosCount > 0 ? 'success' : 'warning'),
        ];
    }
}

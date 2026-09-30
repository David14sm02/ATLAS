<?php

namespace App\Filament\Widgets;

use App\Models\CatPeriodo;
use App\Models\CatPlantel;
use App\Models\RepRegistroBase;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class PulsoNacionalWidget extends Widget
{
    protected static string $view = 'filament.widgets.pulso-nacional-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        // 1. Periodo Trimestral Activo
        $periodo = CatPeriodo::where('bloqueado', false)->orderBy('anio')->orderBy('trimestre')->first()
            ?? CatPeriodo::orderByDesc('anio')->orderByDesc('trimestre')->first();

        $diasRestantes = 0;
        if ($periodo && $periodo->fecha_limite) {
            $diasRestantes = max(0, (int) now()->diffInDays(Carbon::parse($periodo->fecha_limite), false));
        }

        $periodoId = $periodo?->id ?? 0;

        // 2. Cobertura Institucional y Agregados (Caché local de 60 segundos)
        $cacheKey = "dashboard_pulso_data_{$periodoId}";
        $data = cache()->remember($cacheKey, 60, function () use ($periodoId) {
            $totalPlantelesRegistrados = CatPlantel::where('activo', true)->count();
            $metaPlanteles = $totalPlantelesRegistrados > 0 ? $totalPlantelesRegistrados : 263;

            $plantelesPublicados = RepRegistroBase::where('periodo_id', $periodoId)
                ->where('estado', 'PUBLICADO')
                ->distinct('plantel_id')
                ->count('plantel_id');

            $plantelesBorrador = RepRegistroBase::where('periodo_id', $periodoId)
                ->where('estado', 'BORRADOR')
                ->whereNotIn('plantel_id', function ($query) use ($periodoId) {
                    $query->select('plantel_id')
                        ->from('rep_registros_base')
                        ->where('periodo_id', $periodoId)
                        ->where('estado', 'PUBLICADO');
                })
                ->distinct('plantel_id')
                ->count('plantel_id');

            $plantelesSinReporte = max(0, $metaPlanteles - ($plantelesPublicados + $plantelesBorrador));

            $porcentajeCumplimiento = $metaPlanteles > 0
                ? round(($plantelesPublicados / $metaPlanteles) * 100, 1)
                : 0;

            $registrosPeriodo = RepRegistroBase::where('periodo_id', $periodoId)->get();

            $totalGeneral = $registrosPeriodo->sum('total_general');
            $totalDocentes = $registrosPeriodo->sum('total_docentes');
            $totalEstudiantes = $registrosPeriodo->sum('total_estudiantes');

            $totalMujeres = $registrosPeriodo->sum(fn ($r) => (int) $r->docentes_mujeres + (int) $r->estudiantes_mujeres);
            $totalHombres = $registrosPeriodo->sum(fn ($r) => (int) $r->docentes_hombres + (int) $r->estudiantes_hombres);

            $porcentajeMujeres = $totalGeneral > 0 ? round(($totalMujeres / $totalGeneral) * 100, 1) : 0;
            $porcentajeHombres = $totalGeneral > 0 ? round(($totalHombres / $totalGeneral) * 100, 1) : 0;

            return [
                'metaPlanteles' => $metaPlanteles,
                'plantelesPublicados' => $plantelesPublicados,
                'plantelesBorrador' => $plantelesBorrador,
                'plantelesSinReporte' => $plantelesSinReporte,
                'porcentajeCumplimiento' => $porcentajeCumplimiento,
                'totalGeneral' => $totalGeneral,
                'totalDocentes' => $totalDocentes,
                'totalEstudiantes' => $totalEstudiantes,
                'totalMujeres' => $totalMujeres,
                'totalHombres' => $totalHombres,
                'porcentajeMujeres' => $porcentajeMujeres,
                'porcentajeHombres' => $porcentajeHombres,
            ];
        });

        return array_merge([
            'periodo' => $periodo,
            'diasRestantes' => $diasRestantes,
        ], $data);
    }
}

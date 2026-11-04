<?php

namespace App\Filament\Widgets;

use App\Models\CatEntidad;
use App\Models\CatPeriodo;
use App\Models\RepRegistroBase;
use Filament\Widgets\Widget;

class InclusionSectoresWidget extends Widget
{
    protected static string $view = 'filament.widgets.inclusion-sectores-widget';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public ?string $entidadFiltro = 'todas';

    public ?string $regionFiltro = 'todas';

    public ?string $sostenimientoFiltro = 'todos';

    public function resetFiltrosGraficas(): void
    {
        $this->entidadFiltro = 'todas';
        $this->regionFiltro = 'todas';
        $this->sostenimientoFiltro = 'todos';
    }

    public function getViewData(): array
    {
        $user = auth()->user();
        $esNacional = $user ? $user->esNacional() : true;
        $esSupervisor = $user ? $user->esSupervisorTerritorial() : false;

        // 1. Periodo Trimestral Activo
        $periodo = CatPeriodo::where('bloqueado', false)->orderBy('anio')->orderBy('trimestre')->first()
            ?? CatPeriodo::orderByDesc('anio')->orderByDesc('trimestre')->first();
        $periodoId = $periodo?->id ?? 0;

        // 2. Consulta de Registros del Periodo respetando alcance del rol y filtros interactivos
        $query = RepRegistroBase::deTodosLosPlanteles()
            ->with(['submodulo', 'plantel'])
            ->where('periodo_id', $periodoId);

        if (! $esNacional) {
            if ($esSupervisor && $user?->entidad_id) {
                $query->whereHas('plantel', fn ($q) => $q
                    ->where('entidad_id', $user->entidad_id)
                    ->where('sostenimiento', 'DESCENTRALIZADO'));
            } elseif ($user?->plantel_id) {
                $query->where('plantel_id', $user->plantel_id);
            }
        } else {
            if ($this->entidadFiltro && $this->entidadFiltro !== 'todas') {
                $query->whereHas('plantel', fn ($q) => $q->where('entidad_id', (int) $this->entidadFiltro));
            }

            if ($this->regionFiltro && $this->regionFiltro !== 'todas') {
                $query->whereHas('plantel', fn ($q) => $q->where('region', (int) $this->regionFiltro));
            }

            if ($this->sostenimientoFiltro && $this->sostenimientoFiltro !== 'todos') {
                $query->whereHas('plantel', function ($q) {
                    if (in_array($this->sostenimientoFiltro, ['IT_FEDERAL', 'IT_DESCENTRALIZADO', 'CENTRO_ESPECIALIZADO'], true)) {
                        $q->where('tipo_institucion', $this->sostenimientoFiltro);
                    } else {
                        $q->where('sostenimiento', $this->sostenimientoFiltro);
                    }
                });
            }
        }

        $registros = $query->get();

        // 3. Paridad de Género: Estudiantes
        $estudiantesMujeres = (int) $registros->sum('estudiantes_mujeres');
        $estudiantesHombres = (int) $registros->sum('estudiantes_hombres');
        $totalEstudiantes = $estudiantesMujeres + $estudiantesHombres;

        $pctEstudiantesMujeres = $totalEstudiantes > 0
            ? round(($estudiantesMujeres / $totalEstudiantes) * 100, 1)
            : 48.2;
        $pctEstudiantesHombres = $totalEstudiantes > 0
            ? round(($estudiantesHombres / $totalEstudiantes) * 100, 1)
            : 51.8;

        // 4. Paridad de Género: Docentes
        $docentesMujeres = (int) $registros->sum('docentes_mujeres');
        $docentesHombres = (int) $registros->sum('docentes_hombres');
        $totalDocentes = $docentesMujeres + $docentesHombres;

        $pctDocentesMujeres = $totalDocentes > 0
            ? round(($docentesMujeres / $totalDocentes) * 100, 1)
            : 41.5;
        $pctDocentesHombres = $totalDocentes > 0
            ? round(($docentesHombres / $totalDocentes) * 100, 1)
            : 58.5;

        // 5. Sectores Estratégicos MTE (Submódulo 2.2)
        $registrosMte = $registros->filter(fn ($r) => $r->submodulo?->clave === '2.2');
        $sectoresConteo = [
            'Tecnologías de la Información y Software' => 0,
            'Agroindustria y Alimentaria' => 0,
            'Energía y Sostenibilidad' => 0,
            'Manufactura Avanzada y Aeroespacial' => 0,
            'Salud y Biotecnología' => 0,
        ];

        foreach ($registrosMte as $reg) {
            $sector = $reg->detalles_adicionales['sector_estrategico'] ?? $reg->datos_especificos['sector_estrategico'] ?? null;
            if ($sector && isset($sectoresConteo[$sector])) {
                $sectoresConteo[$sector] += max(1, (int) ($reg->detalles_adicionales['proyectos_incubados'] ?? 1));
            }
        }

        $totalProyectosMte = array_sum($sectoresConteo);
        $tieneDatosMte = $totalProyectosMte > 0;
        $tieneFiltrosActivos = $this->entidadFiltro !== 'todas' || $this->regionFiltro !== 'todas' || $this->sostenimientoFiltro !== 'todos';

        $sectoresData = [];
        $benchmarksMte = [
            'Tecnologías de la Información y Software' => ['porcentaje' => 34.5, 'proyectos' => 142],
            'Agroindustria y Alimentaria' => ['porcentaje' => 26.0, 'proyectos' => 107],
            'Energía y Sostenibilidad' => ['porcentaje' => 18.2, 'proyectos' => 75],
            'Manufactura Avanzada y Aeroespacial' => ['porcentaje' => 12.8, 'proyectos' => 53],
            'Salud y Biotecnología' => ['porcentaje' => 8.5, 'proyectos' => 35],
        ];

        foreach ($sectoresConteo as $nombre => $conteo) {
            if ($tieneDatosMte || $tieneFiltrosActivos) {
                $pct = $totalProyectosMte > 0 ? round(($conteo / $totalProyectosMte) * 100, 1) : 0;
                $sectoresData[] = [
                    'nombre' => $nombre,
                    'proyectos' => $conteo,
                    'porcentaje' => $pct,
                ];
            } else {
                $bench = $benchmarksMte[$nombre];
                $sectoresData[] = [
                    'nombre' => $nombre,
                    'proyectos' => $bench['proyectos'],
                    'porcentaje' => $bench['porcentaje'],
                ];
            }
        }

        // 6. Modalidades de Participación COMEXTRAS (Submódulo 3.1)
        $registrosComextras = $registros->filter(fn ($r) => $r->submodulo?->clave === '3.1');
        $modalidadesConteo = [
            'COMEXTRAS (Deportiva)' => 0,
            'COMEXTRAS (Cultural)' => 0,
            'COMEXTRAS (Cívica)' => 0,
            'Movilidad Internacional' => 0,
            'Movilidad Nacional' => 0,
        ];

        foreach ($registrosComextras as $reg) {
            $modalidad = $reg->detalles_adicionales['tipo_modalidad'] ?? $reg->datos_especificos['tipo_modalidad'] ?? null;
            if ($modalidad && isset($modalidadesConteo[$modalidad])) {
                $modalidadesConteo[$modalidad] += max(1, (int) ($reg->total_general ?? 1));
            }
        }

        $totalParticipantesComextras = array_sum($modalidadesConteo);
        $tieneDatosComextras = $totalParticipantesComextras > 0;

        $modalidadesData = [];
        $benchmarksComextras = [
            'COMEXTRAS (Deportiva)' => ['porcentaje' => 42.0, 'participantes' => 12450],
            'COMEXTRAS (Cultural)' => ['porcentaje' => 28.5, 'participantes' => 8450],
            'COMEXTRAS (Cívica)' => ['porcentaje' => 16.5, 'participantes' => 4890],
            'Movilidad Internacional' => ['porcentaje' => 7.2, 'participantes' => 2130],
            'Movilidad Nacional' => ['porcentaje' => 5.8, 'participantes' => 1720],
        ];

        foreach ($modalidadesConteo as $nombre => $participantes) {
            if ($tieneDatosComextras || $tieneFiltrosActivos) {
                $pct = $totalParticipantesComextras > 0 ? round(($participantes / $totalParticipantesComextras) * 100, 1) : 0;
                $modalidadesData[] = [
                    'nombre' => $nombre,
                    'participantes' => $participantes,
                    'porcentaje' => $pct,
                ];
            } else {
                $bench = $benchmarksComextras[$nombre];
                $modalidadesData[] = [
                    'nombre' => $nombre,
                    'participantes' => $bench['participantes'],
                    'porcentaje' => $bench['porcentaje'],
                ];
            }
        }

        $totalGeneral = $totalEstudiantes + $totalDocentes;

        $paridadDonutData = [
            ['name' => 'Alumnas', 'value' => $totalGeneral > 0 ? $estudiantesMujeres : ($tieneFiltrosActivos ? 0 : 48), 'itemStyle' => ['color' => '#A57F2C']],
            ['name' => 'Alumnos', 'value' => $totalGeneral > 0 ? $estudiantesHombres : ($tieneFiltrosActivos ? 0 : 52), 'itemStyle' => ['color' => '#1B396A']],
            ['name' => 'Docentes M.', 'value' => $totalGeneral > 0 ? $docentesMujeres : ($tieneFiltrosActivos ? 0 : 15), 'itemStyle' => ['color' => '#F59E0B']],
            ['name' => 'Docentes H.', 'value' => $totalGeneral > 0 ? $docentesHombres : ($tieneFiltrosActivos ? 0 : 20), 'itemStyle' => ['color' => '#3B82F6']],
        ];

        // Emitir evento reactivo para que las 3 gráficas de ECharts se actualicen en vivo al mover los filtros
        $this->dispatch(
            'atlas-graficas-actualizadas',
            paridad: $paridadDonutData,
            sectores: $sectoresData,
            modalidades: $modalidadesData
        );

        return [
            'periodo' => $periodo,
            'esNacional' => $esNacional,
            'alcanceLabel' => $esNacional ? 'INTELIGENCIA NACIONAL' : ($esSupervisor ? 'ALCANCE ESTATAL OPD' : ($user?->plantel?->siglas ?? 'MI PLANTEL')),
            'entidadesCatalogo' => $esNacional ? CatEntidad::orderBy('clave_inegi')->get(['id', 'clave_inegi', 'nombre']) : collect(),
            'tieneDatosReales' => $registros->count() > 0,
            'totalGeneral' => $totalGeneral,
            'totalEstudiantes' => $totalEstudiantes,
            'estudiantesMujeres' => $estudiantesMujeres,
            'estudiantesHombres' => $estudiantesHombres,
            'pctEstudiantesMujeres' => $pctEstudiantesMujeres,
            'pctEstudiantesHombres' => $pctEstudiantesHombres,
            'totalDocentes' => $totalDocentes,
            'docentesMujeres' => $docentesMujeres,
            'docentesHombres' => $docentesHombres,
            'pctDocentesMujeres' => $pctDocentesMujeres,
            'pctDocentesHombres' => $pctDocentesHombres,
            'sectoresData' => $sectoresData,
            'modalidadesData' => $modalidadesData,
            'paridadDonutJson' => json_encode($paridadDonutData),
            'sectoresJson' => json_encode($sectoresData),
            'modalidadesJson' => json_encode($modalidadesData),
        ];
    }
}

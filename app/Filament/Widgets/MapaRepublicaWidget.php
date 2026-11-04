<?php

namespace App\Filament\Widgets;

use App\Models\CatEntidad;
use App\Models\CatPeriodo;
use App\Models\CatSubmodulo;
use App\Models\RepRegistroBase;
use Filament\Widgets\Widget;

class MapaRepublicaWidget extends Widget
{
    protected static string $view = 'filament.widgets.mapa-republica-widget';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user ? ($user->esNacional() || $user->esSupervisorTerritorial()) : false;
    }

    public ?string $submoduloFiltro = 'todos';

    public ?string $sostenimientoFiltro = 'todos';

    public ?string $regionFiltro = 'todas';

    public ?string $selectedEstadoClave = null;

    /**
     * Selecciona o deselecciona una entidad federativa al hacer clic en el mapa o lista.
     */
    public function selectEstado(?string $claveInegi = null): void
    {
        if ($this->selectedEstadoClave === $claveInegi) {
            $this->selectedEstadoClave = null;
        } else {
            $this->selectedEstadoClave = $claveInegi;
        }
    }

    /**
     * Restablece la selección de entidad y filtros a la vista nacional consolidada.
     */
    public function resetSeleccion(): void
    {
        $this->selectedEstadoClave = null;
    }

    public function resetFiltros(): void
    {
        $this->submoduloFiltro = 'todos';
        $this->sostenimientoFiltro = 'todos';
        $this->regionFiltro = 'todas';
        $this->selectedEstadoClave = null;
    }

    /**
     * Mapeo de nombres cortos para compatibilidad exacta con el GeoJSON vectorial.
     */
    protected function getGeoJsonName(string $claveInegi, string $nombreDb): string
    {
        $mapaNombres = [
            '05' => 'Coahuila',
            '09' => 'Ciudad de México',
            '16' => 'Michoacán',
            '30' => 'Veracruz',
        ];

        return $mapaNombres[$claveInegi] ?? $nombreDb;
    }

    /**
     * Respaldo de planteles oficiales por entidad federativa en caso de BD vacía.
     */
    protected function getMetaPlantelesPorEntidad(): array
    {
        return [
            '01' => ['federales' => 3, 'descentralizados' => 0],
            '02' => ['federales' => 3, 'descentralizados' => 0],
            '03' => ['federales' => 2, 'descentralizados' => 3],
            '04' => ['federales' => 3, 'descentralizados' => 4],
            '05' => ['federales' => 4, 'descentralizados' => 6],
            '06' => ['federales' => 1, 'descentralizados' => 0],
            '07' => ['federales' => 4, 'descentralizados' => 1],
            '08' => ['federales' => 7, 'descentralizados' => 2],
            '09' => ['federales' => 9, 'descentralizados' => 0],
            '10' => ['federales' => 3, 'descentralizados' => 4],
            '11' => ['federales' => 5, 'descentralizados' => 8],
            '12' => ['federales' => 6, 'descentralizados' => 3],
            '13' => ['federales' => 3, 'descentralizados' => 5],
            '14' => ['federales' => 4, 'descentralizados' => 13],
            '15' => ['federales' => 3, 'descentralizados' => 15],
            '16' => ['federales' => 6, 'descentralizados' => 12],
            '17' => ['federales' => 3, 'descentralizados' => 0],
            '18' => ['federales' => 3, 'descentralizados' => 0],
            '19' => ['federales' => 2, 'descentralizados' => 0],
            '20' => ['federales' => 10, 'descentralizados' => 3],
            '21' => ['federales' => 3, 'descentralizados' => 13],
            '22' => ['federales' => 3, 'descentralizados' => 0],
            '23' => ['federales' => 3, 'descentralizados' => 3],
            '24' => ['federales' => 4, 'descentralizados' => 4],
            '25' => ['federales' => 3, 'descentralizados' => 2],
            '26' => ['federales' => 5, 'descentralizados' => 3],
            '27' => ['federales' => 3, 'descentralizados' => 6],
            '28' => ['federales' => 6, 'descentralizados' => 1],
            '29' => ['federales' => 3, 'descentralizados' => 1],
            '30' => ['federales' => 8, 'descentralizados' => 19],
            '31' => ['federales' => 4, 'descentralizados' => 4],
            '32' => ['federales' => 1, 'descentralizados' => 7],
        ];
    }

    protected function aplicarFiltroTipoPlantel($query): void
    {
        if (! $this->sostenimientoFiltro || $this->sostenimientoFiltro === 'todos') {
            return;
        }

        if (in_array($this->sostenimientoFiltro, ['IT_FEDERAL', 'IT_DESCENTRALIZADO', 'CENTRO_ESPECIALIZADO'], true)) {
            $query->where('tipo_institucion', $this->sostenimientoFiltro);
        } elseif (in_array($this->sostenimientoFiltro, ['FEDERAL', 'DESCENTRALIZADO'], true)) {
            $query->where('sostenimiento', $this->sostenimientoFiltro);
        }
    }

    public function getViewData(): array
    {
        // 1. Periodo Trimestral Activo
        $periodo = CatPeriodo::where('bloqueado', false)->orderBy('anio')->orderBy('trimestre')->first()
            ?? CatPeriodo::orderByDesc('anio')->orderByDesc('trimestre')->first();
        $periodoId = $periodo?->id ?? 0;

        // 2. Todos los 35 Submódulos agrupados por Eje para el selector
        $submodulos = CatSubmodulo::with('eje')->orderBy('eje_id')->orderBy('id')->get();

        // 3. Catálogo de Entidades Federativas con planteles filtrados por Sostenimiento/Tipo y Región
        $entidades = CatEntidad::with(['planteles' => function ($query) {
            $query->where('activo', true)->orderBy('nombre');
            $this->aplicarFiltroTipoPlantel($query);

            if ($this->regionFiltro && $this->regionFiltro !== 'todas') {
                $query->where('region', (int) $this->regionFiltro);
            }
        }])->orderBy('clave_inegi')->get();

        // 4. Consulta de Registros de Reporte filtrados (Alcance nacional consolidado para el mapa interactivo)
        $registrosBase = RepRegistroBase::deTodosLosPlanteles()
            ->with(['plantel.entidad', 'submodulo'])
            ->where('periodo_id', $periodoId);

        if ($this->submoduloFiltro && $this->submoduloFiltro !== 'todos') {
            if (str_starts_with($this->submoduloFiltro, 'EJE-')) {
                $ejeClave = $this->submoduloFiltro;
                $registrosBase->whereHas('submodulo.eje', fn ($q) => $q->where('clave', $ejeClave));
            } else {
                $registrosBase->whereHas('submodulo', fn ($q) => $q->where('clave', $this->submoduloFiltro));
            }
        }

        if ($this->sostenimientoFiltro && $this->sostenimientoFiltro !== 'todos') {
            $registrosBase->whereHas('plantel', fn ($q) => $this->aplicarFiltroTipoPlantel($q));
        }

        if ($this->regionFiltro && $this->regionFiltro !== 'todas') {
            $registrosBase->whereHas('plantel', fn ($q) => $q->where('region', (int) $this->regionFiltro));
        }

        $registros = $registrosBase->get();

        // Agrupar registros por entidad_id y por plantel_id
        $registrosPorEntidad = $registros->groupBy(fn ($r) => $r->plantel?->entidad_id ?? 0);
        $registrosPorPlantel = $registros->groupBy('plantel_id');

        $metasOficiales = $this->getMetaPlantelesPorEntidad();
        $mapSeriesData = [];
        $resumenEntidades = [];
        $totalPlantelesFiltradosNacional = 0;

        foreach ($entidades as $entidad) {
            $clave = $entidad->clave_inegi;
            $nombreGeo = $this->getGeoJsonName($clave, $entidad->nombre);
            $regsEntidad = $registrosPorEntidad->get($entidad->id, collect());

            $plantelesReales = $entidad->planteles;
            $tienePlantelesDb = $plantelesReales->count() > 0 || ($this->regionFiltro !== 'todas' || $this->sostenimientoFiltro !== 'todos');

            if ($tienePlantelesDb) {
                $totalPlanteles = $plantelesReales->count();
                $fedCount = $plantelesReales->where('sostenimiento', 'FEDERAL')->count();
                $descCount = $plantelesReales->where('sostenimiento', 'DESCENTRALIZADO')->count();
                $centrosCount = $plantelesReales->where('tipo_institucion', 'CENTRO_ESPECIALIZADO')->count();
            } else {
                $meta = $metasOficiales[$clave] ?? ['federales' => 2, 'descentralizados' => 2];
                $fedCount = $meta['federales'];
                $descCount = $meta['descentralizados'];
                $centrosCount = 0;
                $totalPlanteles = $fedCount + $descCount;
            }

            $totalPlantelesFiltradosNacional += $totalPlanteles;

            // Agregados de desempeño
            $publicados = $regsEntidad->where('estado', 'PUBLICADO')->pluck('plantel_id')->unique()->count();
            $borradores = $regsEntidad->where('estado', 'BORRADOR')->pluck('plantel_id')->unique()->count();
            $sinReporte = max(0, $totalPlanteles - ($publicados + $borradores));

            $totalParticipantes = $regsEntidad->sum('total_general');
            $totalDocentes = $regsEntidad->sum('total_docentes');
            $totalEstudiantes = $regsEntidad->sum('total_estudiantes');
            $docentesMujeres = $regsEntidad->sum('docentes_mujeres');
            $estudiantesMujeres = $regsEntidad->sum('estudiantes_mujeres');

            $porcentajeCumplimiento = $totalPlanteles > 0
                ? round(($publicados / $totalPlanteles) * 100, 1)
                : 0;

            $mapValue = $totalParticipantes > 0 ? $totalParticipantes : $totalPlanteles;

            // Lista detallada de planteles de esta entidad
            $listaPlanteles = $plantelesReales->map(function ($p) use ($registrosPorPlantel) {
                $regsPlantel = $registrosPorPlantel->get($p->id, collect());
                $tienePublicado = $regsPlantel->where('estado', 'PUBLICADO')->isNotEmpty();
                $tieneBorrador = $regsPlantel->where('estado', 'BORRADOR')->isNotEmpty();

                return [
                    'id' => $p->id,
                    'clave_tecnm' => $p->clave_tecnm,
                    'siglas' => $p->siglas ?? $p->clave_tecnm,
                    'nombre' => $p->nombre,
                    'region' => $p->region,
                    'tipo_institucion' => $p->tipo_institucion ?? $p->sostenimiento,
                    'director' => $p->director_nombre,
                    'estatus_reporte' => $tienePublicado ? 'PUBLICADO' : ($tieneBorrador ? 'BORRADOR' : 'PENDIENTE'),
                    'participantes' => $regsPlantel->sum('total_general'),
                ];
            })->values()->all();

            $item = [
                'name' => $nombreGeo,
                'nombre_oficial' => $entidad->nombre,
                'clave_inegi' => $clave,
                'abreviatura' => $entidad->abreviatura,
                'value' => $mapValue,
                'total_planteles' => $totalPlanteles,
                'federales' => $fedCount,
                'descentralizados' => $descCount,
                'centros_especializados' => $centrosCount,
                'publicados' => $publicados,
                'borradores' => $borradores,
                'sin_reporte' => $sinReporte,
                'total_participantes' => $totalParticipantes,
                'total_docentes' => $totalDocentes,
                'total_estudiantes' => $totalEstudiantes,
                'docentes_mujeres' => $docentesMujeres,
                'estudiantes_mujeres' => $estudiantesMujeres,
                'porcentaje_cumplimiento' => $porcentajeCumplimiento,
                'lista_planteles' => $listaPlanteles,
            ];

            $mapSeriesData[] = $item;
            $resumenEntidades[$clave] = $item;
        }

        // Entidad seleccionada actualmente para el panel lateral
        $estadoSeleccionado = null;
        if ($this->selectedEstadoClave && isset($resumenEntidades[$this->selectedEstadoClave])) {
            $estadoSeleccionado = $resumenEntidades[$this->selectedEstadoClave];
        }

        // Top 5 entidades federativas con presencia en el filtro activo
        $topEntidades = collect($resumenEntidades)
            ->filter(fn ($e) => $e['total_planteles'] > 0)
            ->sortByDesc('total_planteles')
            ->take(5)
            ->values();

        // Entidades con mayor rezago de reporte en el filtro activo
        $estadosRezagados = collect($resumenEntidades)
            ->filter(fn ($e) => $e['sin_reporte'] > 0)
            ->sortByDesc('sin_reporte')
            ->take(4)
            ->values();

        // Emitir evento a Alpine/Leaflet para que repinte el mapa de calor en tiempo real al cambiar filtros
        $this->dispatch('atlas-mapa-actualizado', seriesData: $mapSeriesData);

        return [
            'periodo' => $periodo,
            'submodulos' => $submodulos,
            'mapSeriesData' => $mapSeriesData,
            'mapSeriesJson' => json_encode($mapSeriesData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
            'estadoSeleccionado' => $estadoSeleccionado,
            'topEntidades' => $topEntidades,
            'estadosRezagados' => $estadosRezagados,
            'totalEntidades' => collect($resumenEntidades)->where('total_planteles', '>', 0)->count(),
            'totalPlantelesFiltradosNacional' => $totalPlantelesFiltradosNacional,
            'selectedEstadoClave' => $this->selectedEstadoClave,
        ];
    }
}

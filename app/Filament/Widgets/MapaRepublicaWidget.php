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

    public ?string $submoduloFiltro = 'todos';

    public ?string $sostenimientoFiltro = 'todos';

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
     * Restablece la selección de entidad y regresa a la vista nacional consolidada.
     */
    public function resetSeleccion(): void
    {
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
     * Estimación de planteles oficiales (263 en total) por entidad federativa
     * como respaldo analítico en caso de que aún no se importe el catálogo completo.
     */
    protected function getMetaPlantelesPorEntidad(): array
    {
        return [
            '01' => ['federales' => 2, 'descentralizados' => 1],
            '02' => ['federales' => 3, 'descentralizados' => 3],
            '03' => ['federales' => 2, 'descentralizados' => 2],
            '04' => ['federales' => 2, 'descentralizados' => 5],
            '05' => ['federales' => 4, 'descentralizados' => 4],
            '06' => ['federales' => 1, 'descentralizados' => 1],
            '07' => ['federales' => 3, 'descentralizados' => 5],
            '08' => ['federales' => 6, 'descentralizados' => 5],
            '09' => ['federales' => 12, 'descentralizados' => 0],
            '10' => ['federales' => 3, 'descentralizados' => 5],
            '11' => ['federales' => 4, 'descentralizados' => 5],
            '12' => ['federales' => 4, 'descentralizados' => 5],
            '13' => ['federales' => 2, 'descentralizados' => 6],
            '14' => ['federales' => 4, 'descentralizados' => 12],
            '15' => ['federales' => 4, 'descentralizados' => 15],
            '16' => ['federales' => 6, 'descentralizados' => 12],
            '17' => ['federales' => 2, 'descentralizados' => 0],
            '18' => ['federales' => 2, 'descentralizados' => 2],
            '19' => ['federales' => 3, 'descentralizados' => 2],
            '20' => ['federales' => 7, 'descentralizados' => 8],
            '21' => ['federales' => 3, 'descentralizados' => 14],
            '22' => ['federales' => 2, 'descentralizados' => 2],
            '23' => ['federales' => 2, 'descentralizados' => 2],
            '24' => ['federales' => 3, 'descentralizados' => 4],
            '25' => ['federales' => 3, 'descentralizados' => 4],
            '26' => ['federales' => 5, 'descentralizados' => 4],
            '27' => ['federales' => 2, 'descentralizados' => 6],
            '28' => ['federales' => 5, 'descentralizados' => 3],
            '29' => ['federales' => 1, 'descentralizados' => 2],
            '30' => ['federales' => 7, 'descentralizados' => 21],
            '31' => ['federales' => 3, 'descentralizados' => 4],
            '32' => ['federales' => 3, 'descentralizados' => 6],
        ];
    }

    public function getViewData(): array
    {
        // 1. Periodo Trimestral Activo
        $periodo = CatPeriodo::where('bloqueado', false)->orderBy('anio')->orderBy('trimestre')->first()
            ?? CatPeriodo::orderByDesc('anio')->orderByDesc('trimestre')->first();
        $periodoId = $periodo?->id ?? 0;

        // 2. Submodulos Activos para el selector
        $submodulos = CatSubmodulo::where('activo_mvp', true)->orderBy('clave')->get();

        // 3. Catalogo de Entidades Federativas con planteles filtrados
        $entidades = CatEntidad::with(['planteles' => function ($query) {
            $query->where('activo', true);
            if ($this->sostenimientoFiltro && $this->sostenimientoFiltro !== 'todos') {
                $query->where('sostenimiento', $this->sostenimientoFiltro);
            }
        }])->orderBy('clave_inegi')->get();

        // 4. Consulta de Registros de Reporte filtrados
        $registrosBase = RepRegistroBase::query()
            ->with(['plantel.entidad', 'submodulo'])
            ->where('periodo_id', $periodoId);

        if ($this->submoduloFiltro && $this->submoduloFiltro !== 'todos') {
            $registrosBase->whereHas('submodulo', fn ($q) => $q->where('clave', $this->submoduloFiltro));
        }

        if ($this->sostenimientoFiltro && $this->sostenimientoFiltro !== 'todos') {
            $registrosBase->whereHas('plantel', fn ($q) => $q->where('sostenimiento', $this->sostenimientoFiltro));
        }

        $registros = $registrosBase->get();

        // Agrupar registros por entidad_id
        $registrosPorEntidad = $registros->groupBy(fn ($r) => $r->plantel?->entidad_id ?? 0);

        $metasOficiales = $this->getMetaPlantelesPorEntidad();
        $mapSeriesData = [];
        $resumenEntidades = [];

        foreach ($entidades as $entidad) {
            $clave = $entidad->clave_inegi;
            $nombreGeo = $this->getGeoJsonName($clave, $entidad->nombre);
            $regsEntidad = $registrosPorEntidad->get($entidad->id, collect());

            // Conteo de planteles reales vs estimación oficial
            $plantelesReales = $entidad->planteles;
            $tienePlantelesDb = $plantelesReales->count() > 0;

            if ($tienePlantelesDb) {
                $totalPlanteles = $plantelesReales->count();
                $fedCount = $plantelesReales->where('sostenimiento', 'FEDERAL')->count();
                $descCount = $plantelesReales->where('sostenimiento', 'DESCENTRALIZADO')->count();
            } else {
                $meta = $metasOficiales[$clave] ?? ['federales' => 2, 'descentralizados' => 2];
                $fedCount = $meta['federales'];
                $descCount = $meta['descentralizados'];

                if ($this->sostenimientoFiltro === 'FEDERAL') {
                    $totalPlanteles = $fedCount;
                } elseif ($this->sostenimientoFiltro === 'DESCENTRALIZADO') {
                    $totalPlanteles = $descCount;
                } else {
                    $totalPlanteles = $fedCount + $descCount;
                }
            }

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

            // El valor para la escala de calor del mapa
            $mapValue = $totalParticipantes > 0 ? $totalParticipantes : $totalPlanteles;

            $item = [
                'name' => $nombreGeo,
                'nombre_oficial' => $entidad->nombre,
                'clave_inegi' => $clave,
                'abreviatura' => $entidad->abreviatura,
                'value' => $mapValue,
                'total_planteles' => $totalPlanteles,
                'federales' => $fedCount,
                'descentralizados' => $descCount,
                'publicados' => $publicados,
                'borradores' => $borradores,
                'sin_reporte' => $sinReporte,
                'total_participantes' => $totalParticipantes,
                'total_docentes' => $totalDocentes,
                'total_estudiantes' => $totalEstudiantes,
                'docentes_mujeres' => $docentesMujeres,
                'estudiantes_mujeres' => $estudiantesMujeres,
                'porcentaje_cumplimiento' => $porcentajeCumplimiento,
            ];

            $mapSeriesData[] = $item;
            $resumenEntidades[$clave] = $item;
        }

        // Entidad seleccionada actualmente para el panel lateral
        $estadoSeleccionado = null;
        if ($this->selectedEstadoClave && isset($resumenEntidades[$this->selectedEstadoClave])) {
            $estadoSeleccionado = $resumenEntidades[$this->selectedEstadoClave];
        }

        // Top 5 entidades federativas por planteles / cobertura
        $topEntidades = collect($resumenEntidades)
            ->sortByDesc('total_planteles')
            ->take(5)
            ->values();

        // Entidades con mayor rezago de reporte
        $estadosRezagados = collect($resumenEntidades)
            ->filter(fn ($e) => $e['sin_reporte'] > 0)
            ->sortByDesc('sin_reporte')
            ->take(4)
            ->values();

        return [
            'periodo' => $periodo,
            'submodulos' => $submodulos,
            'mapSeriesData' => $mapSeriesData,
            'mapSeriesJson' => json_encode($mapSeriesData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
            'estadoSeleccionado' => $estadoSeleccionado,
            'topEntidades' => $topEntidades,
            'estadosRezagados' => $estadosRezagados,
            'totalEntidades' => count($entidades),
            'selectedEstadoClave' => $this->selectedEstadoClave,
        ];
    }
}

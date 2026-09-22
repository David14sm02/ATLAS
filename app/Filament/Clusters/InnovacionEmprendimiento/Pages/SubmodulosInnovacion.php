<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Pages;

use App\Filament\Clusters\InnovacionEmprendimiento;
use App\Models\CatEje;
use App\Models\CatSubmodulo;
use Filament\Pages\Page;

class SubmodulosInnovacion extends Page
{
    protected static ?string $cluster = InnovacionEmprendimiento::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Catálogo de Submódulos';

    protected static ?string $title = 'Eje 2: Innovación y Emprendimiento';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.clusters.submodulos-overview';

    public function getViewData(): array
    {
        $eje = CatEje::where('clave', 'EJE-02')->first();

        return [
            'ejeClave' => 'EJE 2',
            'ejeNombre' => $eje?->nombre ?? 'Innovación y Emprendimiento',
            'submodulos' => CatSubmodulo::where('eje_id', $eje?->id)->orderBy('clave')->get(),
        ];
    }
}

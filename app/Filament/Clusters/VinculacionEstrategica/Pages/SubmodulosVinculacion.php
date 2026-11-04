<?php

namespace App\Filament\Clusters\VinculacionEstrategica\Pages;

use App\Filament\Clusters\VinculacionEstrategica;
use App\Models\CatEje;
use App\Models\CatSubmodulo;
use Filament\Pages\Page;

class SubmodulosVinculacion extends Page
{
    protected static ?string $cluster = VinculacionEstrategica::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Catálogo de Submódulos';

    protected static ?string $title = 'Eje 1: Vinculación Estratégica';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.clusters.submodulos-overview';

    public function getViewData(): array
    {
        $eje = CatEje::where('clave', 'EJE-01')->first();
        $user = auth()->user();
        $submodulos = CatSubmodulo::where('eje_id', $eje?->id)->orderBy('clave')->get();
        if ($user && $user->esOperador()) {
            $submodulos = $submodulos->filter(fn ($s) => $user->puedeCapturarSubmodulo($s->clave))->values();
        }

        return [
            'ejeClave' => 'EJE 1',
            'ejeNombre' => $eje?->nombre ?? 'Vinculación Estratégica',
            'submodulos' => $submodulos,
        ];
    }
}

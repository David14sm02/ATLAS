<?php

namespace App\Filament\Clusters\Extension\Pages;

use App\Filament\Clusters\Extension;
use App\Models\CatEje;
use App\Models\CatSubmodulo;
use Filament\Pages\Page;

class SubmodulosExtension extends Page
{
    protected static ?string $cluster = Extension::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Catálogo de Submódulos';

    protected static ?string $title = 'Eje 4: Extensión';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.clusters.submodulos-overview';

    public function getViewData(): array
    {
        $eje = CatEje::where('clave', 'EJE-04')->first();
        $user = auth()->user();
        $submodulos = CatSubmodulo::where('eje_id', $eje?->id)->orderBy('clave')->get();
        if ($user && $user->esOperador()) {
            $submodulos = $submodulos->filter(fn ($s) => $user->puedeCapturarSubmodulo($s->clave))->values();
        }

        return [
            'ejeClave' => 'EJE 4',
            'ejeNombre' => $eje?->nombre ?? 'Extensión',
            'submodulos' => $submodulos,
        ];
    }
}

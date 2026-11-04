<?php

namespace App\Filament\Clusters\IntercambioAcademico\Pages;

use App\Filament\Clusters\IntercambioAcademico;
use App\Models\CatEje;
use App\Models\CatSubmodulo;
use Filament\Pages\Page;

class SubmodulosIntercambio extends Page
{
    protected static ?string $cluster = IntercambioAcademico::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Catálogo de Submódulos';

    protected static ?string $title = 'Eje 3: Intercambio Académico';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.clusters.submodulos-overview';

    public function getViewData(): array
    {
        $eje = CatEje::where('clave', 'EJE-03')->first();
        $user = auth()->user();
        $submodulos = CatSubmodulo::where('eje_id', $eje?->id)->orderBy('clave')->get();
        if ($user && $user->esOperador()) {
            $submodulos = $submodulos->filter(fn ($s) => $user->puedeCapturarSubmodulo($s->clave))->values();
        }

        return [
            'ejeClave' => 'EJE 3',
            'ejeNombre' => $eje?->nombre ?? 'Intercambio Académico',
            'submodulos' => $submodulos,
        ];
    }
}

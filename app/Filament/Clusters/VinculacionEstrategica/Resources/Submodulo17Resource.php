<?php

namespace App\Filament\Clusters\VinculacionEstrategica\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\VinculacionEstrategica;
use App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo17Resource\Pages;

class Submodulo17Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = VinculacionEstrategica::class;

    protected static string $submoduloClave = '1.7';

    protected static string $submoduloNombre = 'Indicadores de Calidad';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'submodulo-1-7';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo17::route('/'),
        ];
    }
}

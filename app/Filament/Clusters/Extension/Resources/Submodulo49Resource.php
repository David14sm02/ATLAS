<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo49Resource\Pages;

class Submodulo49Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.9';

    protected static string $submoduloNombre = 'Estrategias de Comercialización';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'submodulo-4-9';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo49::route('/'),
        ];
    }
}

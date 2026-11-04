<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo46Resource\Pages;

class Submodulo46Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.6';

    protected static string $submoduloNombre = 'Nodos de Creatividad Tecnológica';

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'submodulo-4-6';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo46::route('/'),
        ];
    }
}

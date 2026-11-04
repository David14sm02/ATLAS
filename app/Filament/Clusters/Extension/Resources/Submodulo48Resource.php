<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo48Resource\Pages;

class Submodulo48Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.8';

    protected static string $submoduloNombre = 'Servicio Externo';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'submodulo-4-8';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo48::route('/'),
        ];
    }
}

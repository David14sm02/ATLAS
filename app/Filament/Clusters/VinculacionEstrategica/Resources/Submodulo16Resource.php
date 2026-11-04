<?php

namespace App\Filament\Clusters\VinculacionEstrategica\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\VinculacionEstrategica;
use App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo16Resource\Pages;

class Submodulo16Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = VinculacionEstrategica::class;

    protected static string $submoduloClave = '1.6';

    protected static string $submoduloNombre = 'Vinculación con Sectores';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'submodulo-1-6';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo16::route('/'),
        ];
    }
}

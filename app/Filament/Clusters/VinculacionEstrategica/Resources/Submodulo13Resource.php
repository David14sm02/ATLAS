<?php

namespace App\Filament\Clusters\VinculacionEstrategica\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\VinculacionEstrategica;
use App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo13Resource\Pages;

class Submodulo13Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = VinculacionEstrategica::class;

    protected static string $submoduloClave = '1.3';

    protected static string $submoduloNombre = 'Seguimiento de Egresados y Bolsa de Trabajo';

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'submodulo-1-3';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo13::route('/'),
        ];
    }
}

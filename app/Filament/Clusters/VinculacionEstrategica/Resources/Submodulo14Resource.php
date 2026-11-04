<?php

namespace App\Filament\Clusters\VinculacionEstrategica\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\VinculacionEstrategica;
use App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo14Resource\Pages;

class Submodulo14Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = VinculacionEstrategica::class;

    protected static string $submoduloClave = '1.4';

    protected static string $submoduloNombre = 'Proyectos Estratégicos';

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'submodulo-1-4';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo14::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Clusters\VinculacionEstrategica\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\VinculacionEstrategica;
use App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo15Resource\Pages;

class Submodulo15Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = VinculacionEstrategica::class;

    protected static string $submoduloClave = '1.5';

    protected static string $submoduloNombre = 'Semiconductores';

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'submodulo-1-5';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo15::route('/'),
        ];
    }
}

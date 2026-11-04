<?php

namespace App\Filament\Clusters\VinculacionEstrategica\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\VinculacionEstrategica;
use App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo18Resource\Pages;

class Submodulo18Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = VinculacionEstrategica::class;

    protected static string $submoduloClave = '1.8';

    protected static string $submoduloNombre = 'Marco Normativo';

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'submodulo-1-8';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo18::route('/'),
        ];
    }
}

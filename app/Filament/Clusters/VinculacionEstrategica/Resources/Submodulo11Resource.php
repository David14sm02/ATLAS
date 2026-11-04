<?php

namespace App\Filament\Clusters\VinculacionEstrategica\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\VinculacionEstrategica;
use App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo11Resource\Pages;

class Submodulo11Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = VinculacionEstrategica::class;

    protected static string $submoduloClave = '1.1';

    protected static string $submoduloNombre = 'Consejos de Vinculación';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'submodulo-1-1';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo11::route('/'),
        ];
    }
}

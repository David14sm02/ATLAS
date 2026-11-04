<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo43Resource\Pages;

class Submodulo43Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.3';

    protected static string $submoduloNombre = 'Educación Continua';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'submodulo-4-3';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo43::route('/'),
        ];
    }
}

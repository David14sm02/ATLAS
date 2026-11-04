<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo411Resource\Pages;

class Submodulo411Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.11';

    protected static string $submoduloNombre = 'Deporte';

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'submodulo-4-11';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo411::route('/'),
        ];
    }
}

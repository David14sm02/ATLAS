<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo45Resource\Pages;

class Submodulo45Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.5';

    protected static string $submoduloNombre = 'Centros Innovación Automotriz (CIIA)';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'submodulo-4-5';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo45::route('/'),
        ];
    }
}

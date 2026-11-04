<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo47Resource\Pages;

class Submodulo47Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.7';

    protected static string $submoduloNombre = 'Certificación de Laboratorios';

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'submodulo-4-7';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo47::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo44Resource\Pages;

class Submodulo44Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.4';

    protected static string $submoduloNombre = 'Redes de Centros de Certificación';

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'submodulo-4-4';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo44::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo42Resource\Pages;

class Submodulo42Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.2';

    protected static string $submoduloNombre = 'Lenguas Extranjeras y Maternas';

    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'submodulo-4-2';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo42::route('/'),
        ];
    }
}

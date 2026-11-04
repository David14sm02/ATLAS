<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo410Resource\Pages;

class Submodulo410Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.10';

    protected static string $submoduloNombre = 'Arte y Cultura';

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'submodulo-4-10';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo410::route('/'),
        ];
    }
}

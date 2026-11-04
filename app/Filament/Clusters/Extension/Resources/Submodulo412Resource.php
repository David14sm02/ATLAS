<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo412Resource\Pages;

class Submodulo412Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.12';

    protected static string $submoduloNombre = 'Formación Cívica';

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'submodulo-4-12';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo412::route('/'),
        ];
    }
}

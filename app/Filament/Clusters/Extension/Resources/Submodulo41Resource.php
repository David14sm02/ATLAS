<?php

namespace App\Filament\Clusters\Extension\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\Extension;
use App\Filament\Clusters\Extension\Resources\Submodulo41Resource\Pages;

class Submodulo41Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = Extension::class;

    protected static string $submoduloClave = '4.1';

    protected static string $submoduloNombre = 'Cursos MOOC del TecNM';

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'submodulo-4-1';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo41::route('/'),
        ];
    }
}

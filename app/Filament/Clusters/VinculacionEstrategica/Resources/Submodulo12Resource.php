<?php

namespace App\Filament\Clusters\VinculacionEstrategica\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\VinculacionEstrategica;
use App\Filament\Clusters\VinculacionEstrategica\Resources\Submodulo12Resource\Pages;

class Submodulo12Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = VinculacionEstrategica::class;

    protected static string $submoduloClave = '1.2';

    protected static string $submoduloNombre = 'Convenios';

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'submodulo-1-2';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo12::route('/'),
        ];
    }
}

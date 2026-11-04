<?php

namespace App\Filament\Clusters\IntercambioAcademico\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\IntercambioAcademico;
use App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo36Resource\Pages;

class Submodulo36Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = IntercambioAcademico::class;

    protected static string $submoduloClave = '3.6';

    protected static string $submoduloNombre = 'Networking';

    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'submodulo-3-6';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo36::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Clusters\IntercambioAcademico\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\IntercambioAcademico;
use App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo34Resource\Pages;

class Submodulo34Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = IntercambioAcademico::class;

    protected static string $submoduloClave = '3.4';

    protected static string $submoduloNombre = 'Residencias Profesionales';

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'submodulo-3-4';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo34::route('/'),
        ];
    }
}

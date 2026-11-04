<?php

namespace App\Filament\Clusters\IntercambioAcademico\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\IntercambioAcademico;
use App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo37Resource\Pages;

class Submodulo37Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = IntercambioAcademico::class;

    protected static string $submoduloClave = '3.7';

    protected static string $submoduloNombre = 'Unidades Regionales de Vinculación';

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'submodulo-3-7';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo37::route('/'),
        ];
    }
}

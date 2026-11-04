<?php

namespace App\Filament\Clusters\IntercambioAcademico\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\IntercambioAcademico;
use App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo35Resource\Pages;

class Submodulo35Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = IntercambioAcademico::class;

    protected static string $submoduloClave = '3.5';

    protected static string $submoduloNombre = 'Educación Dual';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'submodulo-3-5';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo35::route('/'),
        ];
    }
}

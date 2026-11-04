<?php

namespace App\Filament\Clusters\IntercambioAcademico\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\IntercambioAcademico;
use App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo32Resource\Pages;

class Submodulo32Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = IntercambioAcademico::class;

    protected static string $submoduloClave = '3.2';

    protected static string $submoduloNombre = 'Servicio Social y Desarrollo Comunitario';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'submodulo-3-2';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo32::route('/'),
        ];
    }
}

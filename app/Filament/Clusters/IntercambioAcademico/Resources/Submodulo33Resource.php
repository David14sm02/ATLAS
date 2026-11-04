<?php

namespace App\Filament\Clusters\IntercambioAcademico\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\IntercambioAcademico;
use App\Filament\Clusters\IntercambioAcademico\Resources\Submodulo33Resource\Pages;

class Submodulo33Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = IntercambioAcademico::class;

    protected static string $submoduloClave = '3.3';

    protected static string $submoduloNombre = 'AlfabetizaTec';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'submodulo-3-3';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo33::route('/'),
        ];
    }
}

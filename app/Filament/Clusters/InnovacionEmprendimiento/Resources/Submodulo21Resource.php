<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\InnovacionEmprendimiento;
use App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo21Resource\Pages;

class Submodulo21Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = InnovacionEmprendimiento::class;

    protected static string $submoduloClave = '2.1';

    protected static string $submoduloNombre = 'Eventos de Innovación (InnovaTecNM)';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'submodulo-2-1';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo21::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\InnovacionEmprendimiento;
use App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo23Resource\Pages;

class Submodulo23Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = InnovacionEmprendimiento::class;

    protected static string $submoduloClave = '2.3';

    protected static string $submoduloNombre = 'Nodos Economía Social (NODESS)';

    protected static ?string $navigationIcon = 'heroicon-o-globe-americas';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'submodulo-2-3';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo23::route('/'),
        ];
    }
}

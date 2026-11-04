<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\InnovacionEmprendimiento;
use App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo27Resource\Pages;

class Submodulo27Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = InnovacionEmprendimiento::class;

    protected static string $submoduloClave = '2.7';

    protected static string $submoduloNombre = 'Transferencia de Tecnología';

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'submodulo-2-7';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo27::route('/'),
        ];
    }
}

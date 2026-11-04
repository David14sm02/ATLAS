<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\InnovacionEmprendimiento;
use App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo24Resource\Pages;

class Submodulo24Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = InnovacionEmprendimiento::class;

    protected static string $submoduloClave = '2.4';

    protected static string $submoduloNombre = 'Centros de Innovación Empresarial';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'submodulo-2-4';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo24::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\InnovacionEmprendimiento;
use App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo25Resource\Pages;

class Submodulo25Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = InnovacionEmprendimiento::class;

    protected static string $submoduloClave = '2.5';

    protected static string $submoduloNombre = 'Educación Financiera';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'submodulo-2-5';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo25::route('/'),
        ];
    }
}

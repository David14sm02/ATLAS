<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\InnovacionEmprendimiento;
use App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo28Resource\Pages;

class Submodulo28Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = InnovacionEmprendimiento::class;

    protected static string $submoduloClave = '2.8';

    protected static string $submoduloNombre = 'Centros de Patentamiento';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'submodulo-2-8';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo28::route('/'),
        ];
    }
}

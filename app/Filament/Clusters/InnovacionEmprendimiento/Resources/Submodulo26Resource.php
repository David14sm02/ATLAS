<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Resources;

use App\Filament\Clusters\BaseSubmoduloResource;
use App\Filament\Clusters\InnovacionEmprendimiento;
use App\Filament\Clusters\InnovacionEmprendimiento\Resources\Submodulo26Resource\Pages;

class Submodulo26Resource extends BaseSubmoduloResource
{
    protected static ?string $cluster = InnovacionEmprendimiento::class;

    protected static string $submoduloClave = '2.6';

    protected static string $submoduloNombre = 'Propiedad Intelectual';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'submodulo-2-6';

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubmodulo26::route('/'),
        ];
    }
}

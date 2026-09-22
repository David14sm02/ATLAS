<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Resources\MteResource\Pages;

use App\Filament\Clusters\InnovacionEmprendimiento\Resources\MteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMtes extends ListRecords
{
    protected static string $resource = MteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Reporte MTE'),
        ];
    }
}

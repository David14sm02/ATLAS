<?php

namespace App\Filament\Clusters\IntercambioAcademico\Resources\ComextrasResource\Pages;

use App\Filament\Clusters\IntercambioAcademico\Resources\ComextrasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListComextras extends ListRecords
{
    protected static string $resource = ComextrasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Reporte Movilidad / COMEXTRAS'),
        ];
    }
}

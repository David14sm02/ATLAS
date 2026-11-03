<?php

namespace App\Filament\Resources\BitacoraAuditoriaResource\Pages;

use App\Filament\Resources\BitacoraAuditoriaResource;
use Filament\Resources\Pages\ListRecords;

class ListBitacoraAuditorias extends ListRecords
{
    protected static string $resource = BitacoraAuditoriaResource::class;

    protected function getHeaderActions(): array
    {
        // Caja negra: sin botones de creación ni importación manual
        return [];
    }
}


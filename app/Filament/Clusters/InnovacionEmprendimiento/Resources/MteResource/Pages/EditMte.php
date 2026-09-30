<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Resources\MteResource\Pages;

use App\Filament\Clusters\InnovacionEmprendimiento\Resources\MteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditMte extends EditRecord
{
    protected static string $resource = MteResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['estado'] ?? '') === 'PUBLICADO' && is_null($this->record->published_at)) {
            $data['published_at'] = now();
            $data['published_by'] = Auth::id();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

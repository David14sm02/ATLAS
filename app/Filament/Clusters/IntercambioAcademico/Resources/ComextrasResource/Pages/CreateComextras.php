<?php

namespace App\Filament\Clusters\IntercambioAcademico\Resources\ComextrasResource\Pages;

use App\Filament\Clusters\IntercambioAcademico\Resources\ComextrasResource;
use App\Models\CatSubmodulo;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateComextras extends CreateRecord
{
    protected static string $resource = ComextrasResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $submodulo = CatSubmodulo::where('clave', '3.1')->firstOrFail();
        $data['submodulo_id'] = $submodulo->id;
        $data['user_id'] = Auth::id();

        $user = Auth::user();
        if ($user && ! $user->esNacional() && $user->plantel_id) {
            $data['plantel_id'] = $user->plantel_id;
        }

        if (($data['estado'] ?? '') === 'PUBLICADO') {
            $data['published_at'] = now();
            $data['published_by'] = Auth::id();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

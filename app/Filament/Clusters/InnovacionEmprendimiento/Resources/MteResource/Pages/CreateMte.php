<?php

namespace App\Filament\Clusters\InnovacionEmprendimiento\Resources\MteResource\Pages;

use App\Filament\Clusters\InnovacionEmprendimiento\Resources\MteResource;
use App\Models\CatSubmodulo;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMte extends CreateRecord
{
    protected static string $resource = MteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $submodulo = CatSubmodulo::where('clave', '2.2')->firstOrFail();
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

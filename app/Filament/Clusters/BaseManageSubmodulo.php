<?php

namespace App\Filament\Clusters;

use App\Models\CatSubmodulo;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

abstract class BaseManageSubmodulo extends ManageRecords
{
    protected function getHeaderActions(): array
    {
        $resourceClass = static::getResource();
        $clave = method_exists($resourceClass, 'getSubmoduloClave') ? $resourceClass::getSubmoduloClave() : '';

        return [
            Actions\CreateAction::make()
                ->label('Nuevo Reporte '.$clave)
                ->slideOver()
                ->mutateFormDataUsing(function (array $data) use ($clave): array {
                    $submodulo = CatSubmodulo::where('clave', $clave)->firstOrFail();
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
                }),
        ];
    }
}


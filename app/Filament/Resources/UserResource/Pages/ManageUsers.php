<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\RepDictamenLog;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Usuario Institucional')
                ->slideOver()
                ->mutateFormDataUsing(function (array $data): array {
                    /** @var User|null $actor */
                    $actor = Auth::user();

                    // Candado Anti-Escalamiento: si no es Superadmin, forzar su propio plantel y limitar roles a R4/R5
                    if ($actor && $actor->rol_clave !== User::ROL_SUPERADMIN) {
                        $data['plantel_id'] = $actor->plantel_id;

                        if (! in_array($data['rol_clave'] ?? '', [
                            User::ROL_OPERADOR_MOD,
                            User::ROL_SUBDIRECTOR_DELEGADO,
                            User::ROL_DIRECTOR_TITULAR,
                        ], true)) {
                            $data['rol_clave'] = User::ROL_OPERADOR_MOD;
                        }
                    }

                    return $data;
                })
                ->after(function (User $record): void {
                    /** @var User|null $actor */
                    $actor = Auth::user();
                    if (! $actor) {
                        return;
                    }

                    $submodulos = $record->submodulosAsignados()->pluck('clave')->all();

                    RepDictamenLog::create([
                        'plantel_id' => $record->plantel_id ?? $actor->plantel_id,
                        'registro_id' => null,
                        'modulo' => 'USUARIOS',
                        'entidad_tipo' => 'users',
                        'entidad_id_ref' => $record->id,
                        'user_id' => $actor->id,
                        'usuario_nombre' => $actor->name,
                        'usuario_rol_clave' => $actor->rol_clave,
                        'usuario_cargo' => $actor->cargo,
                        'accion' => RepDictamenLog::ACCION_CREACION,
                        'estatus_anterior' => null,
                        'estatus_nuevo' => 'ACTIVO',
                        'observaciones' => "Alta de usuario institucional {$record->name} ({$record->email}) con rol {$record->rol_clave}. Submódulos asignados: ".(empty($submodulos) ? 'Alcance por rol' : implode(', ', $submodulos)),
                        'valores_nuevos' => [
                            'name' => $record->name,
                            'email' => $record->email,
                            'rol_clave' => $record->rol_clave,
                            'cargo' => $record->cargo,
                            'plantel_id' => $record->plantel_id,
                            'submodulos_asignados' => $submodulos,
                        ],
                        'ip_address' => request()?->ip() ?? '127.0.0.1',
                        'user_agent' => substr((string) request()?->userAgent(), 0, 250),
                        'created_at' => now(),
                    ]);
                }),
        ];
    }
}


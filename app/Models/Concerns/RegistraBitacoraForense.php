<?php

namespace App\Models\Concerns;

use App\Models\RepBitacoraCarga;
use App\Models\RepDictamenLog;
use App\Models\RepRegistroBase;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait RegistraBitacoraForense
{
    public static function bootRegistraBitacoraForense(): void
    {
        static::created(function (Model $model): void {
            self::escribirEnBitacoraForense($model, 'CREACION');
        });

        static::updated(function (Model $model): void {
            self::escribirEnBitacoraForense($model, 'ACTUALIZACION');
        });

        static::deleted(function (Model $model): void {
            self::escribirEnBitacoraForense($model, 'ELIMINACION');
        });
    }

    protected static function escribirEnBitacoraForense(Model $model, string $eventoBase): void
    {
        /** @var User|null $actor */
        $actor = Auth::user();

        // Ignorar campos de ruido técnico en el diferencial
        $camposIgnorados = [
            'updated_at',
            'created_at',
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ];

        $valoresAnteriores = null;
        $valoresNuevos = null;

        if ($eventoBase === 'CREACION') {
            $valoresNuevos = collect($model->getAttributes())
                ->except($camposIgnorados)
                ->all();
        } elseif ($eventoBase === 'ACTUALIZACION') {
            $cambios = collect($model->getChanges())
                ->except($camposIgnorados)
                ->all();

            if (empty($cambios)) {
                return;
            }

            $valoresNuevos = $cambios;
            $valoresAnteriores = collect($cambios)
                ->mapWithKeys(fn ($val, $key) => [$key => $model->getOriginal($key)])
                ->all();
        } elseif ($eventoBase === 'ELIMINACION') {
            $valoresAnteriores = collect($model->getOriginal())
                ->except($camposIgnorados)
                ->all();
        }

        // Determinar módulo, plantel, registro y tipo de acción específica
        $plantelId = $model->getAttribute('plantel_id') ?? $actor?->plantel_id;
        $registroId = null;
        $modulo = class_basename($model);
        $accion = $eventoBase;
        $estatusAnterior = null;
        $estatusNuevo = null;
        $observaciones = null;

        if ($model instanceof RepRegistroBase) {
            $registroId = $eventoBase === 'ELIMINACION' ? null : $model->id;
            $submoduloClave = $model->submodulo?->clave ?? '';
            $modulo = $submoduloClave ? "Submódulo {$submoduloClave}" : 'Reporte de Indicadores';
            $estatusAnterior = $eventoBase === 'CREACION' ? null : (string) $model->getOriginal('estado');
            $estatusNuevo = (string) $model->getAttribute('estado');

            // Si pasó a PUBLICADO, distinguir si firmó el Director Titular o el Subdirector por Ausencia
            if ($estatusNuevo === 'PUBLICADO' && $estatusAnterior !== 'PUBLICADO') {
                if ($actor?->rol_clave === User::ROL_SUBDIRECTOR_DELEGADO) {
                    $accion = 'FIRMA_DELEGADA';
                    $observaciones = 'Reporte avalado y publicado bajo Firma por Ausencia (Delegada).';
                } else {
                    $accion = 'PUBLICACION';
                    $observaciones = 'Reporte avalado y publicado oficialmente.';
                }
            } else {
                $observaciones = $model->getAttribute('observaciones');
            }
        } elseif ($model instanceof RepBitacoraCarga) {
            $modulo = 'Carga de Plantilla Excel';
            $accion = 'CARGA_EXCEL';
            $estatusAnterior = $eventoBase === 'CREACION' ? null : (string) $model->getOriginal('estatus');
            $estatusNuevo = (string) $model->getAttribute('estatus');
            $observaciones = "Archivo: {$model->archivo_nombre} ({$model->filas_procesadas} filas procesadas, {$model->filas_con_error} errores)";
        }

        if (! $observaciones) {
            $camposModificados = array_keys($valoresNuevos ?? $valoresAnteriores ?? []);
            $observaciones = match ($accion) {
                'CREACION' => "Creación de registro en {$modulo}.",
                'ELIMINACION' => "Eliminación de registro (ID #{$model->getKey()}) en {$modulo}.",
                default => "Actualización de campos en {$modulo}: ".implode(', ', array_slice($camposModificados, 0, 6)),
            };
        }

        $request = app()->runningInConsole() ? null : request();

        RepDictamenLog::create([
            'plantel_id' => $plantelId,
            'registro_id' => $registroId,
            'modulo' => $modulo,
            'entidad_tipo' => $model::class,
            'entidad_id_ref' => $model->getKey(),
            'user_id' => $actor?->id ?? $model->getAttribute('user_id'),
            'usuario_nombre' => $actor?->name ?? 'Sistema / Seeder',
            'usuario_rol_clave' => $actor?->rol_clave ?? 'SISTEMA',
            'usuario_cargo' => $actor?->cargo ?? 'Proceso Automático',
            'accion' => $accion,
            'estatus_anterior' => $estatusAnterior,
            'estatus_nuevo' => $estatusNuevo ?? 'N/A',
            'observaciones' => $observaciones,
            'valores_anteriores' => $valoresAnteriores,
            'valores_nuevos' => $valoresNuevos,
            'payload_snapshot' => collect($model->getAttributes())->except($camposIgnorados)->all(),
            'ip_address' => $request?->ip() ?? '127.0.0.1',
            'user_agent' => $request?->userAgent() ?? 'CLI / Artisan',
            'created_at' => now(),
        ]);
    }
}


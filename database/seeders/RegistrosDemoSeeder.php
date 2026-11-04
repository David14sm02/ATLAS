<?php

namespace Database\Seeders;

use App\Models\CatPeriodo;
use App\Models\CatPlantel;
use App\Models\CatSubmodulo;
use App\Models\RepDictamenLog;
use App\Models\RepRegistroBase;
use App\Models\User;
use Illuminate\Database\Seeder;

class RegistrosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $periodo = CatPeriodo::where('bloqueado', false)->orderBy('anio')->orderBy('trimestre')->first()
            ?? CatPeriodo::orderByDesc('anio')->orderByDesc('trimestre')->first();

        if (! $periodo) {
            return;
        }

        $submodulos = CatSubmodulo::pluck('id', 'clave');
        $admin = User::where('email', 'admin@tecnm.mx')->first() ?? User::first();
        $dirCancun = User::where('email', 'director.cancun@tecnm.mx')->first() ?? $admin;
        $subCancun = User::where('email', 'vinculacion.cancun@tecnm.mx')->first() ?? $admin;
        $opComextrasCancun = User::where('email', 'comextras.cancun@tecnm.mx')->first() ?? $admin;
        $opMteCancun = User::where('email', 'mte.cancun@tecnm.mx')->first() ?? $admin;
        $dirZapopan = User::where('email', 'director.zapopan@tecnm.mx')->first() ?? $admin;
        $opZapopan = User::where('email', 'operador.zapopan@tecnm.mx')->first() ?? $admin;
        $dirCenidet = User::where('email', 'director.cenidet@tecnm.mx')->first() ?? $admin;

        $muestras = [
            // 1. I.T. de Cancún (Federal - Quintana Roo - Región 8)
            [
                'siglas' => 'ITCANCUN',
                'submodulo' => '3.1',
                'user' => $opComextrasCancun,
                'publicador' => $dirCancun,
                'estado' => 'PUBLICADO',
                'dm' => 14, 'dh' => 18, 'em' => 125, 'eh' => 132,
                'detalles' => [
                    'tipo_modalidad' => 'COMEXTRAS (Deportiva)',
                    'pais_destino' => 'México (Nacional)',
                    'convenios_activos' => 6,
                ],
                'obs' => 'Delegación deportiva y cultural del I.T. de Cancún validada por Dirección.',
            ],
            [
                'siglas' => 'ITCANCUN',
                'submodulo' => '2.2',
                'user' => $opMteCancun,
                'publicador' => $subCancun, // Firma delegada por ausencia
                'estado' => 'PUBLICADO',
                'dm' => 11, 'dh' => 15, 'em' => 94, 'eh' => 88,
                'detalles' => [
                    'sector_estrategico' => 'Tecnologías de la Información y Software',
                    'fase_predominante' => 'Prototipado y Validación',
                    'proyectos_incubados' => 18,
                ],
                'obs' => 'Proyectos MTE del trimestre firmados por Subdirección de Vinculación.',
            ],

            // 2. I.T.S. de Zapopan (Descentralizado OPD - Jalisco - Región 4)
            [
                'siglas' => 'ITSZAPOPAN',
                'submodulo' => '2.2',
                'user' => $opZapopan,
                'publicador' => $dirZapopan,
                'estado' => 'PUBLICADO',
                'dm' => 19, 'dh' => 22, 'em' => 160, 'eh' => 175,
                'detalles' => [
                    'sector_estrategico' => 'Tecnologías de la Información y Software',
                    'fase_predominante' => 'Modelo de Negocio e Incubación',
                    'proyectos_incubados' => 24,
                ],
                'obs' => 'Corte oficial MTE del I.T.S. de Zapopan.',
            ],
            [
                'siglas' => 'ITSZAPOPAN',
                'submodulo' => '3.1',
                'user' => $opZapopan,
                'publicador' => null,
                'estado' => 'BORRADOR',
                'dm' => 8, 'dh' => 10, 'em' => 64, 'eh' => 70,
                'detalles' => [
                    'tipo_modalidad' => 'Movilidad Internacional',
                    'pais_destino' => 'Colombia / España',
                    'convenios_activos' => 9,
                ],
                'obs' => 'En revisión de oficios de comisión internacional.',
            ],

            // 3. CENIDET (Centro Especializado - Morelos - Región 6)
            [
                'siglas' => 'CENIDET',
                'submodulo' => '2.2',
                'user' => $dirCenidet,
                'publicador' => $dirCenidet,
                'estado' => 'PUBLICADO',
                'dm' => 24, 'dh' => 31, 'em' => 68, 'eh' => 74,
                'detalles' => [
                    'sector_estrategico' => 'Energía y Sostenibilidad',
                    'fase_predominante' => 'Prototipado y Validación',
                    'proyectos_incubados' => 15,
                ],
                'obs' => 'Desarrollos tecnológicos de posgrado CENIDET.',
            ],

            // 4. I.T. de Celaya (Federal - Guanajuato - Región 5)
            [
                'siglas' => 'ITCELAYA',
                'submodulo' => '2.2',
                'user' => $admin,
                'publicador' => $admin,
                'estado' => 'PUBLICADO',
                'dm' => 22, 'dh' => 28, 'em' => 190, 'eh' => 210,
                'detalles' => [
                    'sector_estrategico' => 'Manufactura Avanzada y Aeroespacial',
                    'fase_predominante' => 'Empresa Legalmente Constituida',
                    'proyectos_incubados' => 21,
                ],
                'obs' => 'Reporte trimestral I.T. de Celaya.',
            ],
            [
                'siglas' => 'ITCELAYA',
                'submodulo' => '3.1',
                'user' => $admin,
                'publicador' => $admin,
                'estado' => 'PUBLICADO',
                'dm' => 12, 'dh' => 14, 'em' => 145, 'eh' => 138,
                'detalles' => [
                    'tipo_modalidad' => 'COMEXTRAS (Cultural)',
                    'pais_destino' => 'México (Nacional)',
                    'convenios_activos' => 8,
                ],
                'obs' => 'Encuentro Nacional de Arte y Cultura TecNM.',
            ],

            // 5. I.T. de Mérida (Federal - Yucatán - Región 8)
            [
                'siglas' => 'ITMERIDA',
                'submodulo' => '2.2',
                'user' => $admin,
                'publicador' => $admin,
                'estado' => 'PUBLICADO',
                'dm' => 16, 'dh' => 19, 'em' => 135, 'eh' => 142,
                'detalles' => [
                    'sector_estrategico' => 'Agroindustria y Alimentaria',
                    'fase_predominante' => 'Prototipado y Validación',
                    'proyectos_incubados' => 19,
                ],
                'obs' => 'Proyectos agroindustriales del sureste.',
            ],

            // 6. I.T. de Veracruz (Federal - Veracruz - Región 7)
            [
                'siglas' => 'ITVER',
                'submodulo' => '3.1',
                'user' => $admin,
                'publicador' => $admin,
                'estado' => 'PUBLICADO',
                'dm' => 18, 'dh' => 21, 'em' => 175, 'eh' => 182,
                'detalles' => [
                    'tipo_modalidad' => 'COMEXTRAS (Cívica)',
                    'pais_destino' => 'México (Nacional)',
                    'convenios_activos' => 5,
                ],
                'obs' => 'Bandas de guerra y escoltas institucionales.',
            ],

            // 7. I.T. de Puebla (Federal - Puebla - Región 6)
            [
                'siglas' => 'ITPUEBLA',
                'submodulo' => '3.1',
                'user' => $admin,
                'publicador' => null,
                'estado' => 'BORRADOR',
                'dm' => 10, 'dh' => 12, 'em' => 98, 'eh' => 104,
                'detalles' => [
                    'tipo_modalidad' => 'Movilidad Nacional',
                    'pais_destino' => 'México (Nacional)',
                    'convenios_activos' => 7,
                ],
                'obs' => 'Captura en proceso de validación local.',
            ],

            // 8. I.T. de Tijuana (Federal - Baja California - Región 1)
            [
                'siglas' => 'ITTIJUANA',
                'submodulo' => '2.2',
                'user' => $admin,
                'publicador' => $admin,
                'estado' => 'PUBLICADO',
                'dm' => 15, 'dh' => 20, 'em' => 150, 'eh' => 165,
                'detalles' => [
                    'sector_estrategico' => 'Salud y Biotecnología',
                    'fase_predominante' => 'Prototipado y Validación',
                    'proyectos_incubados' => 14,
                ],
                'obs' => 'Clúster de dispositivos médicos y biotecnología.',
            ],

            // 9. I.T. de Saltillo (Federal - Coahuila - Región 2)
            [
                'siglas' => 'ITSALTILLO',
                'submodulo' => '2.2',
                'user' => $admin,
                'publicador' => null,
                'estado' => 'BORRADOR',
                'dm' => 13, 'dh' => 17, 'em' => 112, 'eh' => 128,
                'detalles' => [
                    'sector_estrategico' => 'Manufactura Avanzada y Aeroespacial',
                    'fase_predominante' => 'Ideación y Sensibilización',
                    'proyectos_incubados' => 11,
                ],
                'obs' => 'En integración de evidencias trimestrales.',
            ],
        ];

        foreach ($muestras as $item) {
            $plantel = CatPlantel::where('siglas', $item['siglas'])->first();
            $submoduloId = $submodulos[$item['submodulo']] ?? null;

            if (! $plantel || ! $submoduloId) {
                continue;
            }

            $esPublicado = $item['estado'] === 'PUBLICADO';

            $registro = RepRegistroBase::withoutGlobalScopes()->updateOrCreate(
                [
                    'plantel_id' => $plantel->id,
                    'submodulo_id' => $submoduloId,
                    'periodo_id' => $periodo->id,
                ],
                [
                    'user_id' => $item['user']?->id ?? 1,
                    'estado' => $item['estado'],
                    'docentes_mujeres' => $item['dm'],
                    'docentes_hombres' => $item['dh'],
                    'estudiantes_mujeres' => $item['em'],
                    'estudiantes_hombres' => $item['eh'],
                    'detalles_adicionales' => $item['detalles'],
                    'observaciones' => $item['obs'],
                    'published_at' => $esPublicado ? now()->subHours(rand(2, 48)) : null,
                    'published_by' => $esPublicado ? ($item['publicador']?->id ?? $item['user']?->id) : null,
                ]
            );

            // Asegurar entrada demostrativa en la Bitácora Forense si fue publicado por firma delegada o titular
            if ($esPublicado && $item['publicador']) {
                $esFirmaDelegada = $item['publicador']->rol_clave === User::ROL_SUBDIRECTOR_DELEGADO;
                $accion = $esFirmaDelegada ? RepDictamenLog::ACCION_FIRMA_DELEGADA : RepDictamenLog::ACCION_PUBLICACION;

                $existeLog = RepDictamenLog::where('registro_id', $registro->id)
                    ->where('accion', $accion)
                    ->exists();

                if (! $existeLog) {
                    RepDictamenLog::create([
                        'plantel_id' => $plantel->id,
                        'registro_id' => $registro->id,
                        'modulo' => $item['submodulo'],
                        'entidad_tipo' => 'rep_registros_base',
                        'entidad_id_ref' => $registro->id,
                        'user_id' => $item['publicador']->id,
                        'usuario_nombre' => $item['publicador']->name,
                        'usuario_rol_clave' => $item['publicador']->rol_clave,
                        'usuario_cargo' => $item['publicador']->cargo,
                        'accion' => $accion,
                        'estatus_anterior' => 'BORRADOR',
                        'estatus_nuevo' => 'PUBLICADO',
                        'observaciones' => $esFirmaDelegada
                            ? 'Publicación oficial autorizada mediante Firma por Ausencia (Subdirección / Gestión y Vinculación).'
                            : 'Publicación y firma electrónica trimestral del Director Titular de Plantel.',
                        'valores_anteriores' => ['estado' => 'BORRADOR'],
                        'valores_nuevos' => [
                            'estado' => 'PUBLICADO',
                            'docentes_mujeres' => $item['dm'],
                            'docentes_hombres' => $item['dh'],
                            'estudiantes_mujeres' => $item['em'],
                            'estudiantes_hombres' => $item['eh'],
                        ],
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'ATLAS-Seeder/2.1',
                        'created_at' => now()->subHours(rand(1, 24)),
                    ]);
                }
            }
        }
    }
}


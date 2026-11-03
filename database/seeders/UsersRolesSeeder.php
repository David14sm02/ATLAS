<?php

namespace Database\Seeders;

use App\Models\CatEntidad;
use App\Models\CatPlantel;
use App\Models\CatSubmodulo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersRolesSeeder extends Seeder
{
    /**
     * Crea los usuarios institucionales de prueba (Roles v2.1) para que el equipo de Frontend y QA
     * pueda verificar las vistas diferenciadas por nivel jerárquico, tipo de plantel (Federal, OPD, Centro)
     * y asignación matricial de submódulos.
     *
     * Contraseña universal de desarrollo: password
     */
    public function run(): void
    {
        $password = Hash::make('password');

        // Planteles representativos de los 3 regímenes del TecNM
        $itSaltillo = CatPlantel::where('clave_tecnm', 'ITS')->first();       // IT_FEDERAL
        $itsIrapuato = CatPlantel::where('clave_tecnm', 'ITSI')->first();     // IT_DESCENTRALIZADO (OPD)
        $cenidet = CatPlantel::where('clave_tecnm', 'CENIDET')->first();      // CENTRO_ESPECIALIZADO

        // Entidades para supervisión estatal OPD
        $entidadGuanajuato = CatEntidad::where('clave_inegi', '11')->first();

        $usuarios = [
            // =========================================================================
            // NIVEL 1: OFICINAS CENTRALES (Alcance Nacional - plantel_id = null)
            // =========================================================================
            [
                'email' => 'admin.ti@tecnm.mx',
                'name' => 'Administrador Nacional TICs',
                'cargo' => 'Jefe(a) de Departamento de Tecnologías de la Información y Comunicaciones',
                'rol_clave' => User::ROL_SUPERADMIN,
                'plantel_id' => null,
                'entidad_id' => null,
                'zona_geografica' => 'Nacional',
            ],
            [
                'email' => 'dg@tecnm.mx',
                'name' => 'Ramón Jiménez López',
                'cargo' => 'Director General del TecNM',
                'rol_clave' => User::ROL_AUTORIZADOR_SEV_DG,
                'plantel_id' => null,
                'entidad_id' => null,
                'zona_geografica' => 'Nacional',
            ],
            [
                'email' => 'sev@tecnm.mx',
                'name' => 'Andrea Yadira Zárate Fuentes',
                'cargo' => 'Secretaria de Extensión y Vinculación',
                'rol_clave' => User::ROL_AUTORIZADOR_SEV_DG,
                'plantel_id' => null,
                'entidad_id' => null,
                'zona_geografica' => 'Nacional',
            ],
            [
                'email' => 'dveia@tecnm.mx',
                'name' => 'Marco Antonio Trujillo Martínez',
                'cargo' => 'Director de Vinculación e Intercambio Académico',
                'rol_clave' => User::ROL_REVISOR_DVEIA,
                'plantel_id' => null,
                'entidad_id' => null,
                'zona_geografica' => 'Nacional',
            ],
            [
                'email' => 'oic@tecnm.mx',
                'name' => 'Titular OIC TecNM',
                'cargo' => 'Titular del Órgano Interno de Control en el TecNM',
                'rol_clave' => User::ROL_AUDITOR_OIC,
                'plantel_id' => null,
                'entidad_id' => null,
                'zona_geografica' => 'Nacional',
            ],

            // =========================================================================
            // NIVEL 2: SUPERVISIÓN TERRITORIAL / ESTATAL OPD
            // =========================================================================
            [
                'email' => 'opd.guanajuato@tecnm.mx',
                'name' => 'Coordinación Estatal de Educación Superior Guanajuato',
                'cargo' => 'Director(a) General de Educación Superior Tecnológica Estatal',
                'rol_clave' => User::ROL_SUPERVISOR_TERRITORIAL,
                'plantel_id' => null,
                'entidad_id' => $entidadGuanajuato?->id,
                'zona_geografica' => 'Región 4 - Centro-Occidente',
            ],

            // =========================================================================
            // NIVEL 3A: PLANTEL CENTRALIZADO (IT FEDERAL - IT de Saltillo)
            // =========================================================================
            [
                'email' => 'director@saltillo.tecnm.mx',
                'name' => 'Ania Guadalupe Sánchez Ruíz',
                'cargo' => 'Directora del Instituto Tecnológico de Saltillo',
                'rol_clave' => User::ROL_DIRECTOR_TITULAR,
                'plantel_id' => $itSaltillo?->id,
                'entidad_id' => $itSaltillo?->entidad_id,
                'zona_geografica' => 'Región 2 - Noreste',
            ],
            [
                'email' => 'subdirector@saltillo.tecnm.mx',
                'name' => 'Subdirección de Planeación y Vinculación (IT Saltillo)',
                'cargo' => 'Subdirector(a) de Planeación y Vinculación (Firma por Ausencia)',
                'rol_clave' => User::ROL_SUBDIRECTOR_DELEGADO,
                'plantel_id' => $itSaltillo?->id,
                'entidad_id' => $itSaltillo?->entidad_id,
                'zona_geografica' => 'Región 2 - Noreste',
            ],
            [
                'email' => 'vinculacion@saltillo.tecnm.mx',
                'name' => 'Jefatura de Gestión Tecnológica y Vinculación (IT Saltillo)',
                'cargo' => 'Jefe(a) de Departamento de Gestión Tecnológica y Vinculación',
                'rol_clave' => User::ROL_OPERADOR_MOD,
                'plantel_id' => $itSaltillo?->id,
                'entidad_id' => $itSaltillo?->entidad_id,
                'zona_geografica' => 'Región 2 - Noreste',
                'submodulos' => ['3.1', '2.2'], // Ve COMEXTRAS y MTE
            ],
            [
                'email' => 'comextras@saltillo.tecnm.mx',
                'name' => 'Enlace de Movilidad e Intercambio (IT Saltillo)',
                'cargo' => 'Coordinador(a) de Movilidad e Intercambio Académico',
                'rol_clave' => User::ROL_OPERADOR_MOD,
                'plantel_id' => $itSaltillo?->id,
                'entidad_id' => $itSaltillo?->entidad_id,
                'zona_geografica' => 'Región 2 - Noreste',
                'submodulos' => ['3.1'], // Solo ve 3.1 COMEXTRAS (ideal para probar ocultamiento de 2.2)
            ],

            // =========================================================================
            // NIVEL 3B: PLANTEL DESCENTRALIZADO (OPD - ITS de Irapuato)
            // =========================================================================
            [
                'email' => 'director@irapuato.tecnm.mx',
                'name' => 'Mirna Ireri Sánchez Gómez',
                'cargo' => 'Directora General del Instituto Tecnológico Superior de Irapuato (OPD)',
                'rol_clave' => User::ROL_DIRECTOR_TITULAR,
                'plantel_id' => $itsIrapuato?->id,
                'entidad_id' => $itsIrapuato?->entidad_id,
                'zona_geografica' => 'Región 4 - Centro-Occidente',
            ],
            [
                'email' => 'vinculacion@irapuato.tecnm.mx',
                'name' => 'Jefatura de Vinculación (ITS Irapuato OPD)',
                'cargo' => 'Jefe(a) de Departamento de Vinculación y Extensión',
                'rol_clave' => User::ROL_OPERADOR_MOD,
                'plantel_id' => $itsIrapuato?->id,
                'entidad_id' => $itsIrapuato?->entidad_id,
                'zona_geografica' => 'Región 4 - Centro-Occidente',
                'submodulos' => ['3.1', '2.2'],
            ],

            // =========================================================================
            // NIVEL 3C: CENTRO ESPECIALIZADO (CENIDET)
            // =========================================================================
            [
                'email' => 'director@cenidet.tecnm.mx',
                'name' => 'Lorenzo Octavio Hernández Robles Arenas',
                'cargo' => 'Director del Centro Nacional de Investigación y Desarrollo Tecnológico (CENIDET)',
                'rol_clave' => User::ROL_DIRECTOR_TITULAR,
                'plantel_id' => $cenidet?->id,
                'entidad_id' => $cenidet?->entidad_id,
                'zona_geografica' => 'Región 6 - Centro-Sur',
            ],
        ];

        $submodulosMap = CatSubmodulo::pluck('id', 'clave');

        foreach ($usuarios as $data) {
            $submodulosClaves = $data['submodulos'] ?? [];
            unset($data['submodulos']);

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, [
                    'password' => $password,
                    'activo' => true,
                    'email_verified_at' => now(),
                ])
            );

            if (! empty($submodulosClaves)) {
                $ids = collect($submodulosClaves)
                    ->map(fn (string $clave) => $submodulosMap[$clave] ?? null)
                    ->filter()
                    ->all();

                $user->submodulosAsignados()->sync($ids);
            }
        }
    }
}


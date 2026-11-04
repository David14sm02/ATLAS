<?php

namespace Tests\Feature;

use App\Models\CatEje;
use App\Models\CatEntidad;
use App\Models\CatPeriodo;
use App\Models\CatPlantel;
use App\Models\CatSubmodulo;
use App\Models\RepRegistroBase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private CatPlantel $plantelPuebla;
    private CatPlantel $plantelVeracruz;
    private User $userPuebla;
    private User $userVeracruz;
    private User $adminNacional;
    private CatSubmodulo $submodulo;
    private CatPeriodo $periodo;

    protected function setUp(): void
    {
        parent::setUp();

        $eje = CatEje::create([
            'clave' => 'EJE-01',
            'nombre' => 'Eje de Prueba',
            'orden' => 1,
        ]);

        $this->submodulo = CatSubmodulo::create([
            'eje_id' => $eje->id,
            'clave' => '2.2',
            'nombre' => 'Submódulo de Prueba',
            'activo_mvp' => true,
        ]);

        $entidad = CatEntidad::create([
            'clave_inegi' => '21',
            'nombre' => 'Puebla',
            'abreviatura' => 'PUE',
        ]);

        $this->plantelPuebla = CatPlantel::create([
            'entidad_id' => $entidad->id,
            'clave_tecnm' => 'IT-PUEBLA',
            'nombre' => 'Instituto Tecnológico de Puebla',
            'municipio' => 'Puebla',
            'sostenimiento' => 'FEDERAL',
            'activo' => true,
        ]);

        $this->plantelVeracruz = CatPlantel::create([
            'entidad_id' => $entidad->id,
            'clave_tecnm' => 'IT-VERACRUZ',
            'nombre' => 'Instituto Tecnológico de Veracruz',
            'municipio' => 'Veracruz',
            'sostenimiento' => 'FEDERAL',
            'activo' => true,
        ]);

        $this->periodo = CatPeriodo::create([
            'anio' => 2026,
            'trimestre' => 1,
            'fecha_inicio' => '2026-01-01',
            'fecha_limite' => '2026-03-31',
            'bloqueado' => false,
        ]);

        $this->userPuebla = User::create([
            'plantel_id' => $this->plantelPuebla->id,
            'name' => 'Enlace Puebla',
            'email' => 'enlace.puebla@tecnm.mx',
            'password' => 'password',
        ]);

        $this->userVeracruz = User::create([
            'plantel_id' => $this->plantelVeracruz->id,
            'name' => 'Enlace Veracruz',
            'email' => 'enlace.veracruz@tecnm.mx',
            'password' => 'password',
        ]);

        $this->adminNacional = User::create([
            'plantel_id' => null,
            'name' => 'Admin Nacional',
            'email' => 'admin.nacional@tecnm.mx',
            'password' => 'password',
        ]);
    }

    public function test_operador_puebla_solo_ve_registros_de_puebla(): void
    {
        // Registro Puebla
        RepRegistroBase::withoutGlobalScopes()->create([
            'plantel_id' => $this->plantelPuebla->id,
            'submodulo_id' => $this->submodulo->id,
            'periodo_id' => $this->periodo->id,
            'user_id' => $this->userPuebla->id,
            'docentes_mujeres' => 5,
            'docentes_hombres' => 5,
            'estudiantes_mujeres' => 10,
            'estudiantes_hombres' => 10,
        ]);

        // Autenticar como usuario de Puebla
        $this->actingAs($this->userPuebla);

        $registros = RepRegistroBase::all();
        $this->assertCount(1, $registros);
        $this->assertEquals($this->plantelPuebla->id, $registros->first()->plantel_id);
    }

    public function test_operador_veracruz_no_puede_ver_registros_de_puebla(): void
    {
        // Registro Puebla
        RepRegistroBase::withoutGlobalScopes()->create([
            'plantel_id' => $this->plantelPuebla->id,
            'submodulo_id' => $this->submodulo->id,
            'periodo_id' => $this->periodo->id,
            'user_id' => $this->userPuebla->id,
            'docentes_mujeres' => 5,
            'docentes_hombres' => 5,
            'estudiantes_mujeres' => 10,
            'estudiantes_hombres' => 10,
        ]);

        // Autenticar como usuario de Veracruz
        $this->actingAs($this->userVeracruz);

        $registros = RepRegistroBase::all();
        $this->assertCount(0, $registros);
    }

    public function test_directivo_nacional_ve_registros_de_ambos_planteles(): void
    {
        // Registro Puebla
        RepRegistroBase::withoutGlobalScopes()->create([
            'plantel_id' => $this->plantelPuebla->id,
            'submodulo_id' => $this->submodulo->id,
            'periodo_id' => $this->periodo->id,
            'user_id' => $this->userPuebla->id,
            'docentes_mujeres' => 5,
            'docentes_hombres' => 5,
            'estudiantes_mujeres' => 10,
            'estudiantes_hombres' => 10,
        ]);

        // Registro Veracruz (en periodo 2 para no violar unique)
        $periodo2 = CatPeriodo::create([
            'anio' => 2026,
            'trimestre' => 2,
            'fecha_inicio' => '2026-04-01',
            'fecha_limite' => '2026-06-30',
            'bloqueado' => false,
        ]);

        RepRegistroBase::withoutGlobalScopes()->create([
            'plantel_id' => $this->plantelVeracruz->id,
            'submodulo_id' => $this->submodulo->id,
            'periodo_id' => $periodo2->id,
            'user_id' => $this->userVeracruz->id,
            'docentes_mujeres' => 8,
            'docentes_hombres' => 8,
            'estudiantes_mujeres' => 15,
            'estudiantes_hombres' => 15,
        ]);

        // Autenticar como Directivo Nacional
        $this->actingAs($this->adminNacional);

        $registros = RepRegistroBase::all();
        $this->assertCount(2, $registros);
    }

    public function test_creacion_de_registro_asigna_plantel_automaticamente(): void
    {
        $this->actingAs($this->userPuebla);

        $registro = RepRegistroBase::create([
            'submodulo_id' => $this->submodulo->id,
            'periodo_id' => $this->periodo->id,
            'user_id' => $this->userPuebla->id,
            'docentes_mujeres' => 3,
            'docentes_hombres' => 4,
            'estudiantes_mujeres' => 20,
            'estudiantes_hombres' => 25,
        ]);

        $this->assertEquals($this->plantelPuebla->id, $registro->plantel_id);
    }
}

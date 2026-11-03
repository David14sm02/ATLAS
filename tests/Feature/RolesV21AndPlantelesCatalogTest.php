<?php

namespace Tests\Feature;

use App\Filament\Clusters\InnovacionEmprendimiento\Resources\MteResource;
use App\Filament\Clusters\IntercambioAcademico\Resources\ComextrasResource;
use App\Filament\Widgets\MapaRepublicaWidget;
use App\Filament\Widgets\PulsoNacionalWidget;
use App\Filament\Widgets\PulsoPlantelWidget;
use App\Models\CatPlantel;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesV21AndPlantelesCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_populates_all_266_tecnm_planteles_and_roles_v2_1(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Verificar que los 266 planteles del Excel BASE COMEXTRAS 2026 fueron sembrados
        $this->assertSame(266, CatPlantel::count());

        // 2. Verificar desglose por tipo de institución (126 Federales, 134 OPDs, 6 Centros Especializados)
        $this->assertSame(126, CatPlantel::where('tipo_institucion', 'IT_FEDERAL')->count());
        $this->assertSame(134, CatPlantel::where('tipo_institucion', 'IT_DESCENTRALIZADO')->count());
        $this->assertSame(6, CatPlantel::where('tipo_institucion', 'CENTRO_ESPECIALIZADO')->count());

        // 3. Verificar usuarios de prueba v2.1 y sus privilegios diferenciados
        $dg = User::where('email', 'dg@tecnm.mx')->firstOrFail();
        $this->assertTrue($dg->esNacional());
        $this->assertFalse($dg->esSoloLectura());

        $oic = User::where('email', 'oic@tecnm.mx')->firstOrFail();
        $this->assertTrue($oic->esNacional());
        $this->assertTrue($oic->esAuditorOIC());
        $this->assertTrue($oic->esSoloLectura());

        $opdSupervisor = User::where('email', 'opd.guanajuato@tecnm.mx')->firstOrFail();
        $this->assertTrue($opdSupervisor->esSupervisorTerritorial());
        $this->assertTrue($opdSupervisor->esSoloLectura());

        $directorSaltillo = User::where('email', 'director@saltillo.tecnm.mx')->firstOrFail();
        $this->assertFalse($directorSaltillo->esNacional());
        $this->assertTrue($directorSaltillo->esAdminPlantel());
        $this->assertTrue($directorSaltillo->puedeCapturarSubmodulo('3.1'));
        $this->assertTrue($directorSaltillo->puedeCapturarSubmodulo('2.2'));

        // Operador con ambos submódulos asignados (3.1 y 2.2)
        $jefeVinculacion = User::where('email', 'vinculacion@saltillo.tecnm.mx')->firstOrFail();
        $this->assertTrue($jefeVinculacion->esOperador());
        $this->assertTrue($jefeVinculacion->puedeCapturarSubmodulo('3.1'));
        $this->assertTrue($jefeVinculacion->puedeCapturarSubmodulo('2.2'));

        // Operador con solo 3.1 COMEXTRAS asignado (no debe ver 2.2 MTE)
        $enlaceComextras = User::where('email', 'comextras@saltillo.tecnm.mx')->firstOrFail();
        $this->assertTrue($enlaceComextras->esOperador());
        $this->assertTrue($enlaceComextras->puedeCapturarSubmodulo('3.1'));
        $this->assertFalse($enlaceComextras->puedeCapturarSubmodulo('2.2'));

        // 4. Verificar visibilidad condicional de Widgets y Resources en Filament
        $this->actingAs($dg);
        $this->assertTrue(PulsoNacionalWidget::canView());
        $this->assertTrue(MapaRepublicaWidget::canView());
        $this->assertFalse(PulsoPlantelWidget::canView());
        $this->assertTrue(ComextrasResource::canViewAny());
        $this->assertTrue(ComextrasResource::canCreate());

        $this->actingAs($oic);
        $this->assertTrue(ComextrasResource::canViewAny());
        $this->assertFalse(ComextrasResource::canCreate());

        $this->actingAs($enlaceComextras);
        $this->assertFalse(PulsoNacionalWidget::canView());
        $this->assertFalse(MapaRepublicaWidget::canView());
        $this->assertTrue(PulsoPlantelWidget::canView());
        $this->assertTrue(ComextrasResource::canViewAny());
        $this->assertFalse(MteResource::canViewAny());
    }
}


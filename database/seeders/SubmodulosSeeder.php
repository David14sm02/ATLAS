<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubmodulosSeeder extends Seeder
{
    public function run(): void
    {
        $ejeIds = DB::table('cat_ejes')->pluck('id', 'clave');

        $submodulos = [
            // EJE 1: VINCULACIÓN ESTRATÉGICA
            ['eje_id' => $ejeIds['EJE-01'], 'clave' => '1.1', 'nombre' => 'Consejos de Vinculación', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-01'], 'clave' => '1.2', 'nombre' => 'Convenios', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-01'], 'clave' => '1.3', 'nombre' => 'Seguimiento de Egresados y Bolsa de Trabajo', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-01'], 'clave' => '1.4', 'nombre' => 'Proyectos Estratégicos', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-01'], 'clave' => '1.5', 'nombre' => 'Semiconductores', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-01'], 'clave' => '1.6', 'nombre' => 'Vinculación con Sectores', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-01'], 'clave' => '1.7', 'nombre' => 'Indicadores de Calidad', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-01'], 'clave' => '1.8', 'nombre' => 'Marco Normativo', 'activo_mvp' => false],

            // EJE 2: INNOVACIÓN Y EMPRENDIMIENTO
            ['eje_id' => $ejeIds['EJE-02'], 'clave' => '2.1', 'nombre' => 'Eventos de Innovación y Emprendimiento (InnovaTecNM)', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-02'], 'clave' => '2.2', 'nombre' => 'Modelo Talento Emprendedor (MTE)', 'activo_mvp' => true], // MVP
            ['eje_id' => $ejeIds['EJE-02'], 'clave' => '2.3', 'nombre' => 'Nodos de Impulso a la Economía Social y Solidaria (NODESS)', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-02'], 'clave' => '2.4', 'nombre' => 'Centros de Innovación e Impulso Empresarial y Social', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-02'], 'clave' => '2.5', 'nombre' => 'Educación Financiera', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-02'], 'clave' => '2.6', 'nombre' => 'Propiedad Intelectual', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-02'], 'clave' => '2.7', 'nombre' => 'Transferencia de Tecnología', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-02'], 'clave' => '2.8', 'nombre' => 'Centros de Patentamiento', 'activo_mvp' => false],

            // EJE 3: INTERCAMBIO ACADÉMICO
            ['eje_id' => $ejeIds['EJE-03'], 'clave' => '3.1', 'nombre' => 'Movilidad Nacional e Internacional (COMEXTRAS)', 'activo_mvp' => true], // MVP
            ['eje_id' => $ejeIds['EJE-03'], 'clave' => '3.2', 'nombre' => 'Servicio Social y Desarrollo Comunitario', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-03'], 'clave' => '3.3', 'nombre' => 'AlfabetizaTec', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-03'], 'clave' => '3.4', 'nombre' => 'Residencias Profesionales', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-03'], 'clave' => '3.5', 'nombre' => 'Educación Dual', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-03'], 'clave' => '3.6', 'nombre' => 'Networking', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-03'], 'clave' => '3.7', 'nombre' => 'Unidades de Sitio Regional Especializadas en Vinculación', 'activo_mvp' => false],

            // EJE 4: EXTENSIÓN
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.1', 'nombre' => 'Cursos Masivos Abiertos y en Línea (MOOC) del TecNM', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.2', 'nombre' => 'Lenguas Extranjeras y Lenguas Maternas o Indígenas', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.3', 'nombre' => 'Educación Continua', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.4', 'nombre' => 'Certificaciones (Redes de Centros de Certificación TecNM)', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.5', 'nombre' => 'Centros de Innovación Automotriz y Aeroespacial (Red CIIA - TecNM)', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.6', 'nombre' => 'Nodos de Creatividad para la Innovación Tecnológica y el Emprendimiento', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.7', 'nombre' => 'Programa de Certificación de Laboratorios TecNM', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.8', 'nombre' => 'Servicio Externo', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.9', 'nombre' => 'Estrategias de Comercialización', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.10', 'nombre' => 'Arte y Cultura', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.11', 'nombre' => 'Deporte', 'activo_mvp' => false],
            ['eje_id' => $ejeIds['EJE-04'], 'clave' => '4.12', 'nombre' => 'Formación Cívica', 'activo_mvp' => false],
        ];

        foreach ($submodulos as &$submodulo) {
            $submodulo['created_at'] = now();
            $submodulo['updated_at'] = now();
        }

        DB::table('cat_submodulos')->upsert($submodulos, ['clave'], ['eje_id', 'nombre', 'activo_mvp', 'updated_at']);
    }
}

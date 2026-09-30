<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EjesSeeder extends Seeder
{
    public function run(): void
    {
        $ejes = [
            [
                'clave' => 'EJE-01',
                'nombre' => 'Vinculación Estratégica',
                'icono' => 'heroicon-o-briefcase',
                'orden' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'EJE-02',
                'nombre' => 'Innovación y Emprendimiento',
                'icono' => 'heroicon-o-light-bulb',
                'orden' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'EJE-03',
                'nombre' => 'Intercambio Académico',
                'icono' => 'heroicon-o-academic-cap',
                'orden' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'clave' => 'EJE-04',
                'nombre' => 'Extensión',
                'icono' => 'heroicon-o-globe-americas',
                'orden' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('cat_ejes')->upsert($ejes, ['clave'], ['nombre', 'icono', 'orden', 'updated_at']);
    }
}

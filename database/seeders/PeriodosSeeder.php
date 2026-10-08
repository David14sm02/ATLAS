<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodosSeeder extends Seeder
{
    public function run(): void
    {
        $periodos = [
            [
                'anio' => 2026,
                'trimestre' => 1,
                'fecha_inicio' => '2026-01-01',
                'fecha_limite' => '2026-04-15',
                'bloqueado' => true, // Trimestre 1 ya cerrado
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'anio' => 2026,
                'trimestre' => 2,
                'fecha_inicio' => '2026-04-01',
                'fecha_limite' => '2026-07-15',
                'bloqueado' => true, // Trimestre 2 cerrado
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'anio' => 2026,
                'trimestre' => 3,
                'fecha_inicio' => '2026-07-01',
                'fecha_limite' => '2026-10-15',
                'bloqueado' => false, // Trimestre 3 activo (corte actual)
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'anio' => 2026,
                'trimestre' => 4,
                'fecha_inicio' => '2026-10-01',
                'fecha_limite' => '2027-01-15',
                'bloqueado' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('cat_periodos')->upsert(
            $periodos,
            ['anio', 'trimestre'],
            ['fecha_inicio', 'fecha_limite', 'bloqueado', 'updated_at']
        );
    }
}

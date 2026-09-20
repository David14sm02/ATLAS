<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EjesSeeder::class,
            SubmodulosSeeder::class,
            EntidadesSeeder::class,
            PeriodosSeeder::class,
        ]);
    }
}

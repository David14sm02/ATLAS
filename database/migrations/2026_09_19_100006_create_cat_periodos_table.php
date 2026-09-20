<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_periodos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio'); // 2026
            $table->unsignedTinyInteger('trimestre'); // 1, 2, 3, 4
            $table->date('fecha_inicio');
            $table->date('fecha_limite');
            $table->boolean('bloqueado')->default(false); // Congelamiento para semáforo
            $table->timestamps();

            $table->unique(['anio', 'trimestre'], 'uq_periodo_anio_trimestre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_periodos');
    }
};

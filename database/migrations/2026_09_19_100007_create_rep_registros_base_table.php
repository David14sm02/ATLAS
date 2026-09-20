<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rep_registros_base', function (Blueprint $table) {
            $table->id();

            // Llaves Dimensionales y Control de Acceso
            $table->foreignId('plantel_id')->constrained('cat_planteles')->restrictOnDelete();
            $table->foreignId('submodulo_id')->constrained('cat_submodulos')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('cat_periodos')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // Flujo de Estados
            $table->enum('estado', ['BORRADOR', 'PUBLICADO'])->default('BORRADOR');

            // Métricas Base Cuantitativas por Género
            $table->unsignedInteger('docentes_mujeres')->default(0);
            $table->unsignedInteger('docentes_hombres')->default(0);
            $table->unsignedInteger('estudiantes_mujeres')->default(0);
            $table->unsignedInteger('estudiantes_hombres')->default(0);

            // Columnas Generadas (STORED ALWAYS): Cálculo infalible por motor PostgreSQL
            $table->unsignedInteger('total_docentes')->storedAs('docentes_mujeres + docentes_hombres');
            $table->unsignedInteger('total_estudiantes')->storedAs('estudiantes_mujeres + estudiantes_hombres');
            $table->unsignedInteger('total_general')->storedAs('docentes_mujeres + docentes_hombres + estudiantes_mujeres + estudiantes_hombres');

            // Payload Semiestructurado para atributos particulares de cada submódulo
            $table->jsonb('detalles_adicionales')->default('{}');

            // Soportes del Mundo Real: Evidencias, Notas y Auditoría
            $table->string('archivo_evidencia')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            // Restricción Única Compuesta: 1 solo reporte por plantel, submódulo y trimestre
            $table->unique(['plantel_id', 'submodulo_id', 'periodo_id'], 'uq_plantel_submod_periodo');

            // Índices de Alta Concurrencia
            $table->index(['plantel_id', 'periodo_id']);
            $table->index(['submodulo_id', 'periodo_id']);
            $table->index('estado');
        });

        // Índice GIN sobre JSONB para búsquedas y agregaciones instantáneas
        DB::statement('CREATE INDEX idx_rep_detalles_gin ON rep_registros_base USING gin (detalles_adicionales);');
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_registros_base');
    }
};

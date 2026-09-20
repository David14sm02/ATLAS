<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rep_bitacora_cargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plantel_id')->constrained('cat_planteles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('submodulo_id')->nullable()->constrained('cat_submodulos')->nullOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained('cat_periodos')->nullOnDelete();

            $table->string('archivo_nombre', 255);
            $table->enum('estatus', ['PROCESANDO', 'EXITOSO', 'CON_ERRORES', 'FALLIDO'])->default('PROCESANDO');
            $table->unsignedInteger('filas_procesadas')->default(0);
            $table->unsignedInteger('filas_con_error')->default(0);
            $table->jsonb('errores_detalle')->default('[]');

            $table->timestamps();

            $table->index(['plantel_id', 'estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_bitacora_cargas');
    }
};

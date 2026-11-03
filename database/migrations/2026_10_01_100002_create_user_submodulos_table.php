<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_submodulos', function (Blueprint $table) {
            $table->id();

            // Relación N:M entre Usuario Operador y Submódulos Asignados (1 a 35)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('submodulo_id')->constrained('cat_submodulos')->cascadeOnDelete();

            // Auditoría: Quién autorizó la asignación del submódulo (Director R4 o Admin R1)
            $table->foreignId('asignado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Evitar duplicidades de asignación
            $table->unique(['user_id', 'submodulo_id'], 'uq_user_submodulo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_submodulos');
    }
};


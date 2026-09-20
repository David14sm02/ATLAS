<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_submodulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eje_id')->constrained('cat_ejes')->cascadeOnDelete();
            $table->string('clave', 10)->unique(); // '1.1', '2.2', '3.1', etc.
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->boolean('activo_mvp')->default(false); // TRUE únicamente para 2.2 y 3.1
            $table->timestamps();

            $table->index('eje_id');
            $table->index('activo_mvp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_submodulos');
    }
};

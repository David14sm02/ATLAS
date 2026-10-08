<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_planteles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entidad_id')->constrained('cat_entidades')->restrictOnDelete();
            $table->string('clave_tecnm', 30)->unique(); // Clave oficial del plantel
            $table->string('nombre', 180);
            $table->string('municipio', 100);
            $table->enum('sostenimiento', ['FEDERAL', 'DESCENTRALIZADO'])->default('FEDERAL');
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('entidad_id');
            $table->index('sostenimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_planteles');
    }
};

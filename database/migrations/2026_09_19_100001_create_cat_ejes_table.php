<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_ejes', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 10)->unique(); // 'EJE-01', 'EJE-02', 'EJE-03', 'EJE-04'
            $table->string('nombre', 150);
            $table->string('icono', 50)->nullable();
            $table->unsignedSmallInteger('orden')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_ejes');
    }
};

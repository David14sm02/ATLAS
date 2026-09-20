<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_entidades', function (Blueprint $table) {
            $table->id();
            $table->char('clave_inegi', 2)->unique(); // '01' a '32'
            $table->string('nombre', 60);
            $table->string('abreviatura', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_entidades');
    }
};

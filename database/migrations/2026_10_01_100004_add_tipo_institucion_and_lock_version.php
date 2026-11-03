<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Diferenciación de Centros Especializados (CRODE, CIIDET, CENIDET), Región y Director actual
        Schema::table('cat_planteles', function (Blueprint $table) {
            $table->string('siglas', 30)
                ->nullable()
                ->after('clave_tecnm')
                ->comment('Siglas oficiales en BASE COMEXTRAS: ITE, ITM, CRODECH, CENIDET, etc.');

            $table->unsignedTinyInteger('region')
                ->nullable()
                ->after('entidad_id')
                ->index()
                ->comment('Región oficial TecNM (1 a 9)');

            $table->string('tipo_institucion', 30)
                ->default('IT_FEDERAL')
                ->after('sostenimiento')
                ->index()
                ->comment('IT_FEDERAL, IT_DESCENTRALIZADO, CENTRO_ESPECIALIZADO');

            $table->string('director_nombre', 150)
                ->nullable()
                ->after('tipo_institucion')
                ->comment('Nombre del Director(a) en funciones según directorio oficial');

            $table->string('director_cargo', 40)
                ->nullable()
                ->after('director_nombre')
                ->comment('Director o Directora');
        });

        // 2. Control de Concurrencia y Bloqueo Optimista en Captura
        Schema::table('rep_registros_base', function (Blueprint $table) {
            $table->unsignedInteger('lock_version')
                ->default(1)
                ->after('estado')
                ->comment('Optimistic Locking: incrementa en cada guardado para evitar colisiones');
        });
    }

    public function down(): void
    {
        Schema::table('cat_planteles', function (Blueprint $table) {
            $table->dropColumn([
                'siglas',
                'region',
                'tipo_institucion',
                'director_nombre',
                'director_cargo',
            ]);
        });

        Schema::table('rep_registros_base', function (Blueprint $table) {
            $table->dropColumn('lock_version');
        });
    }
};


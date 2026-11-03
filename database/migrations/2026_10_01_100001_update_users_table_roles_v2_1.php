<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Alcance Institucional y Territorial (OPDs y Supervisión Regional)
            $table->foreignId('entidad_id')
                ->nullable()
                ->after('plantel_id')
                ->constrained('cat_entidades')
                ->nullOnDelete();

            $table->string('zona_geografica', 50)
                ->nullable()
                ->after('entidad_id')
                ->comment('Noroeste, Noreste, Centro-Occidente, Centro-Sur, Sureste / Región 1 a 9');

            // Catálogo Estricto de Roles v2.1 (MOO-PR-039 y Circular 0045)
            $table->string('rol_clave', 40)
                ->default('R5_OPERADOR_MOD')
                ->after('email')
                ->index()
                ->comment('R1_SUPERADMIN, R1_AUTORIZADOR_SEV_DG, R2_ANALISTA_NAL, R2_REVISOR_DVEIA, R2_AUDITOR_OIC, R3_SUPERVISOR_TERRITORIAL, R4_DIRECTOR_TITULAR, R4_SUBDIRECTOR_DELEGADO, R5_OPERADOR_MOD, R6_SOPORTE_DEV');

            $table->string('cargo', 150)
                ->nullable()
                ->after('name')
                ->comment('Puesto formal en organigrama: Director de Plantel, Jefe de Vinculación, etc.');

            $table->string('telefono', 20)
                ->nullable()
                ->after('cargo');

            $table->boolean('activo')
                ->default(true)
                ->after('rol_clave');

            // Seguridad y Doble Factor de Autenticación (2FA / TOTP)
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');

            // Trazabilidad de Rotación y Baja Institucional
            $table->timestamp('revocado_at')->nullable()->after('two_factor_confirmed_at');
            $table->foreignId('revocado_by')->nullable()->after('revocado_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['entidad_id']);
            $table->dropForeign(['revocado_by']);
            $table->dropColumn([
                'entidad_id',
                'zona_geografica',
                'rol_clave',
                'cargo',
                'telefono',
                'activo',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'revocado_at',
                'revocado_by',
            ]);
        });
    }
};


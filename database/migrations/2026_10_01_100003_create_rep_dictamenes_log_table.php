<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rep_dictamenes_log', function (Blueprint $table) {
            $table->id();

            // Registro base o solicitud al que pertenece el dictamen
            $table->foreignId('registro_id')
                ->constrained('rep_registros_base')
                ->cascadeOnDelete();

            // Usuario responsable de la resolución o firma
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // Acción y transición de estados
            $table->string('accion', 50)->default('DICTAMEN')->comment('CREACION, AVAL_PLANTEL, FIRMA_DELEGADA, DICTAMEN_FAVORABLE, RECHAZO, INFORME_VALIDADO');
            $table->string('estatus_anterior', 30)->nullable();
            $table->string('estatus_nuevo', 30);

            // Tipificación de rechazo u observación
            $table->string('motivo_rechazo_tipo', 50)->nullable()->comment('ERROR_ARITMETICO, FALTA_EVIDENCIA, FUERA_PERIODO, OBSERVACION_NORMATIVA, APROBACION_TACITA, OTRO');
            $table->text('observaciones')->nullable();

            // Pistas Forenses para Órgano Interno de Control (OIC / SFP)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload_snapshot')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Índices de consulta rápida para auditorías
            $table->index(['registro_id', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_dictamenes_log');
    }
};


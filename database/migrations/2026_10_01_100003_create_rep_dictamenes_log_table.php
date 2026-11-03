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

            // Aislamiento Multi-Tenant para que el Director de Plantel (R4) solo vea la bitácora de su plantel
            $table->foreignId('plantel_id')
                ->nullable()
                ->constrained('cat_planteles')
                ->nullOnDelete();

            // Registro base o reporte afectado (nullOnDelete para que si borran un borrador, el log del borrado JAMÁS desaparezca)
            $table->foreignId('registro_id')
                ->nullable()
                ->constrained('rep_registros_base')
                ->nullOnDelete();

            // Referencia polimórfica opcional para auditar cualquier entidad (Reportes, Cargas Excel, Usuarios)
            $table->string('modulo', 80)->default('REPORTE_INDICADORES')->comment('3.1 COMEXTRAS, 2.2 MTE, CARGA_EXCEL, USUARIOS');
            $table->string('entidad_tipo', 100)->nullable();
            $table->unsignedBigInteger('entidad_id_ref')->nullable();

            // Usuario responsable de la acción + Instantánea congelada (por si cambia de rol/cargo en el futuro)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('usuario_nombre', 150)->nullable();
            $table->string('usuario_rol_clave', 40)->nullable();
            $table->string('usuario_cargo', 150)->nullable();

            // Acción y transición de estados
            $table->string('accion', 50)->default('ACTUALIZACION')->comment('CREACION, ACTUALIZACION, PUBLICACION, FIRMA_DELEGADA, ELIMINACION, CARGA_EXCEL, RECHAZO');
            $table->string('estatus_anterior', 30)->nullable();
            $table->string('estatus_nuevo', 30)->nullable();

            // Tipificación de rechazo u observación y resumen legible
            $table->string('motivo_rechazo_tipo', 50)->nullable()->comment('ERROR_ARITMETICO, FALTA_EVIDENCIA, FUERA_PERIODO, OBSERVACION_NORMATIVA, APROBACION_TACITA, OTRO');
            $table->text('observaciones')->nullable();

            // Diferencial Forense (Antes vs Después) e Identificación de Red
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->json('payload_snapshot')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Marca de tiempo inmutable (sin updated_at)
            $table->timestamp('created_at')->useCurrent();

            // Índices de consulta rápida para auditorías Nacionales (R1/R2) y Locales por Plantel (R4)
            $table->index(['plantel_id', 'created_at']);
            $table->index(['registro_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('accion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_dictamenes_log');
    }
};

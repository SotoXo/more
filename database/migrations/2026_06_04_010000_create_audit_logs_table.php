<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cod_usuario_aul')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();
            $table->string('tabla_aul', 100);
            $table->unsignedBigInteger('registro_id_aul')->nullable();
            $table->string('accion_aul', 30);
            $table->string('modulo_aul', 80);
            $table->json('valores_anteriores_aul')->nullable();
            $table->json('valores_nuevos_aul')->nullable();
            $table->json('campos_modificados_aul')->nullable();
            $table->text('descripcion_aul')->nullable();
            $table->string('nivel_aul', 30)->default('info');
            $table->string('ip_aul', 45)->nullable();
            $table->text('user_agent_aul')->nullable();
            $table->string('ruta_aul', 255)->nullable();
            $table->timestamps();

            $table->index('cod_usuario_aul');
            $table->index('tabla_aul');
            $table->index('accion_aul');
            $table->index('modulo_aul');
            $table->index('nivel_aul');
            $table->index('created_at');
            $table->index(['tabla_aul', 'registro_id_aul'], 'idx_audit_logs_tabla_registro');
            $table->index(['modulo_aul', 'nivel_aul', 'created_at'], 'idx_audit_logs_modulo_nivel_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

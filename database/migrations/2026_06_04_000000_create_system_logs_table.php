<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cod_usuario_slg')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();
            $table->string('rol_slg', 50)->nullable();
            $table->string('modulo_slg', 80);
            $table->string('accion_slg', 80);
            $table->string('entidad_tipo_slg', 100)->nullable();
            $table->unsignedBigInteger('entidad_id_slg')->nullable();
            $table->text('descripcion_slg');
            $table->string('nivel_slg', 30)->default('info');
            $table->string('ip_slg', 45)->nullable();
            $table->text('user_agent_slg')->nullable();
            $table->string('metodo_http_slg', 10)->nullable();
            $table->string('ruta_slg', 255)->nullable();
            $table->json('metadata_slg')->nullable();
            $table->timestamps();

            $table->index('cod_usuario_slg');
            $table->index('modulo_slg');
            $table->index('accion_slg');
            $table->index('nivel_slg');
            $table->index('created_at');
            $table->index(['entidad_tipo_slg', 'entidad_id_slg'], 'idx_system_logs_entidad');
            $table->index(['modulo_slg', 'nivel_slg', 'created_at'], 'idx_system_logs_modulo_nivel_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};

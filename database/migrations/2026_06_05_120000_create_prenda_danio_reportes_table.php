<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_danio_reportes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_pdr', 50)->unique();
            $table->string('nombre_prenda_pdr', 180);
            $table->text('descripcion_prenda_pdr')->nullable();
            $table->unsignedBigInteger('cod_prenda_pdr')->nullable();
            $table->unsignedBigInteger('cod_prenda_variante_pdr')->nullable();
            $table->unsignedBigInteger('cod_inventario_pdr')->nullable();
            $table->foreignId('cod_usuario_reporta_pdr')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->foreignId('cod_usuario_revisa_pdr')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->string('causa_pdr', 150);
            $table->string('tipo_danio_pdr', 80);
            $table->string('severidad_pdr', 30);
            $table->integer('cantidad_pdr')->default(1);
            $table->string('estado_pdr', 40)->default('reportado');
            $table->string('decision_pdr', 80)->nullable();
            $table->text('observacion_pdr')->nullable();
            $table->text('observacion_revision_pdr')->nullable();
            $table->timestamp('fecha_reporte_pdr')->nullable();
            $table->timestamp('fecha_revision_pdr')->nullable();
            $table->boolean('afecta_inventario_pdr')->default(false);
            $table->json('metadata_pdr')->nullable();
            $table->timestamps();

            $table->foreign('cod_prenda_pdr')
                ->references('id_prenda')
                ->on('prendas')
                ->nullOnDelete();

            $table->foreign('cod_prenda_variante_pdr')
                ->references('id_prenda_variante')
                ->on('prenda_variantes')
                ->nullOnDelete();

            $table->foreign('cod_inventario_pdr')
                ->references('id_inventario')
                ->on('inventarios')
                ->nullOnDelete();

            $table->index('estado_pdr');
            $table->index('causa_pdr');
            $table->index('tipo_danio_pdr');
            $table->index('severidad_pdr');
            $table->index('fecha_reporte_pdr');
            $table->index(['cod_inventario_pdr', 'estado_pdr'], 'idx_pdr_inventario_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_danio_reportes');
    }
};

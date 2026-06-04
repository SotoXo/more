<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cod_inventario_inm')->nullable();
            $table->unsignedBigInteger('cod_prenda_variante_inm');
            $table->string('tipo_movimiento_inm', 50);
            $table->integer('cantidad_inm');
            $table->integer('cdi_antes_inm')->default(0);
            $table->integer('cdi_despues_inm')->default(0);
            $table->integer('cre_antes_inm')->default(0);
            $table->integer('cre_despues_inm')->default(0);
            $table->string('ubicacion_antes_inm', 100)->nullable();
            $table->string('ubicacion_despues_inm', 100)->nullable();
            $table->string('referencia_tipo_inm', 80)->nullable();
            $table->unsignedBigInteger('referencia_id_inm')->nullable();
            $table->foreignId('cod_usuario_inm')->nullable()->constrained('users')->nullOnDelete();
            $table->text('descripcion_inm')->nullable();
            $table->json('metadata_inm')->nullable();
            $table->timestamps();

            $table->foreign('cod_inventario_inm')
                ->references('id_inventario')
                ->on('inventarios')
                ->nullOnDelete();

            $table->foreign('cod_prenda_variante_inm')
                ->references('id_prenda_variante')
                ->on('prenda_variantes')
                ->restrictOnDelete();

            $table->index('cod_inventario_inm');
            $table->index('cod_prenda_variante_inm');
            $table->index('tipo_movimiento_inm');
            $table->index(['referencia_tipo_inm', 'referencia_id_inm'], 'idx_inv_mov_ref');
            $table->index('cod_usuario_inm');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_movimientos');
    }
};

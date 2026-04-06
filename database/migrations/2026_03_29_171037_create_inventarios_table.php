<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id('id_inventario');
            $table->unsignedBigInteger('id_prenda_variante');
            $table->integer('cantidad_disponible')->default(0);
            $table->integer('cantidad_reservada')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->string('ubicacion_fisica', 100)->nullable();
            $table->timestamp('actualizado_en')->nullable();

            $table->foreign('id_prenda_variante')
                ->references('id_prenda_variante')
                ->on('prenda_variantes')
                ->cascadeOnDelete();

            $table->unique('id_prenda_variante');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
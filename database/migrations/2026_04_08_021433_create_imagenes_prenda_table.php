<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagenes_prenda', function (Blueprint $table) {
            $table->id('id_imagen_prenda');
            $table->unsignedBigInteger('cod_prendas_igp');
            $table->unsignedBigInteger('cod_prenda_variantes_igp')->nullable();
            $table->string('rut_igp', 255);
            $table->string('url_igp', 500)->nullable();
            $table->boolean('pri_igp')->default(false);
            $table->integer('ord_igp')->default(0);
            $table->string('fue_igp', 50)->nullable();
            $table->timestamp('fec_igp')->nullable();
            $table->timestamps();

            $table->foreign('cod_prendas_igp')
                ->references('id_prenda')
                ->on('prendas')
                ->cascadeOnDelete();

            $table->foreign('cod_prenda_variantes_igp')
                ->references('id_prenda_variante')
                ->on('prenda_variantes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagenes_prenda');
    }
};
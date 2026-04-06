<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_variantes', function (Blueprint $table) {
            $table->id('id_prenda_variante');
            $table->unsignedBigInteger('id_prenda');
            $table->unsignedBigInteger('id_color');
            $table->unsignedBigInteger('id_talla');

            $table->string('sku', 50)->unique();
            $table->string('codigo_barras', 100)->nullable()->unique();
            $table->decimal('precio_venta', 10, 2)->default(0);
            $table->decimal('costo', 10, 2)->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->foreign('id_prenda')->references('id_prenda')->on('prendas')->cascadeOnDelete();
            $table->foreign('id_color')->references('id_color')->on('colores')->restrictOnDelete();
            $table->foreign('id_talla')->references('id_talla')->on('tallas')->restrictOnDelete();

            $table->unique(['id_prenda', 'id_color', 'id_talla'], 'uq_prenda_color_talla');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_variantes');
    }
};
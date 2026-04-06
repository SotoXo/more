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
            $table->unsignedBigInteger('id_prenda');
            $table->unsignedBigInteger('id_prenda_variante')->nullable();

            $table->string('ruta_imagen', 255);
            $table->string('url_imagen', 500)->nullable();
            $table->boolean('es_principal')->default(false);
            $table->unsignedInteger('orden_visualizacion')->default(0);
            $table->string('fuente', 50)->nullable();

            $table->timestamps();

            $table->foreign('id_prenda')->references('id_prenda')->on('prendas')->cascadeOnDelete();
            $table->foreign('id_prenda_variante')->references('id_prenda_variante')->on('prenda_variantes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagenes_prenda');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detecciones_ia', function (Blueprint $table) {
            $table->id('id_deteccion_ia');
            $table->unsignedBigInteger('id_imagen_prenda');

            $table->string('tipo_prenda_detectado', 100)->nullable();
            $table->string('color_detectado', 100)->nullable();
            $table->string('estilo_detectado', 100)->nullable();
            $table->string('categoria_detectada', 100)->nullable();
            $table->decimal('nivel_confianza', 5, 2)->nullable();

            $table->jsonb('respuesta_cruda')->nullable();
            $table->boolean('validado')->default(false);
            $table->timestamps();

            $table->foreign('id_imagen_prenda')
                ->references('id_imagen_prenda')
                ->on('imagenes_prenda')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detecciones_ia');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prendas', function (Blueprint $table) {
            $table->id('id_prenda');
            $table->string('nombre_prenda', 150);
            $table->text('descripcion')->nullable();

            $table->unsignedBigInteger('id_categoria');
            $table->unsignedBigInteger('id_marca')->nullable();
            $table->unsignedBigInteger('id_estilo')->nullable();
            $table->unsignedBigInteger('id_temporada')->nullable();

            $table->string('genero', 20)->nullable();
            $table->decimal('precio_base', 10, 2)->default(0);
            $table->boolean('estado')->default(true);

            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();

            $table->timestamps();

            $table->foreign('id_categoria')->references('id_categoria')->on('categorias')->restrictOnDelete();
            $table->foreign('id_marca')->references('id_marca')->on('marcas')->nullOnDelete();
            $table->foreign('id_estilo')->references('id_estilo')->on('estilos')->nullOnDelete();
            $table->foreign('id_temporada')->references('id_temporada')->on('temporadas')->nullOnDelete();

            $table->index('nombre_prenda');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prendas');
    }
};
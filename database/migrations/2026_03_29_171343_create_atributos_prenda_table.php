<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atributos_prenda', function (Blueprint $table) {
            $table->id('id_atributo_prenda');
            $table->unsignedBigInteger('id_prenda');
            $table->string('nombre_atributo', 100);
            $table->string('valor_atributo', 255);
            $table->string('fuente', 50)->nullable();
            $table->decimal('nivel_confianza', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('id_prenda')
                ->references('id_prenda')
                ->on('prendas')
                ->cascadeOnDelete();

            $table->index(['id_prenda', 'nombre_atributo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atributos_prenda');
    }
};
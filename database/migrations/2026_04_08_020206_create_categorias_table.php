<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id('id_categoria');
            $table->string('nom_cat', 100)->unique();
            $table->string('des_cat', 255)->nullable();
            $table->unsignedBigInteger('cod_categoria_padre_cat')->nullable();
            $table->boolean('est_cat')->default(true);
            $table->timestamp('fec_cat')->nullable();
            $table->timestamps();

            $table->foreign('cod_categoria_padre_cat')
                ->references('id_categoria')
                ->on('categorias')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
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
            $table->string('nombre_categoria', 100)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->unsignedBigInteger('id_categoria_padre')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->foreign('id_categoria_padre')
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
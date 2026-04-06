<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favoritos', function (Blueprint $table) {
            $table->id('id_favorito');
            $table->foreignId('id_usuario')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('id_prenda');
            $table->timestamps();

            $table->foreign('id_prenda')
                ->references('id_prenda')
                ->on('prendas')
                ->cascadeOnDelete();

            $table->unique(['id_usuario', 'id_prenda']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favoritos');
    }
};
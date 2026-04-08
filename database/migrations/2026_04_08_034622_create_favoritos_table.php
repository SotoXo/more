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
            $table->unsignedBigInteger('cod_usuarios_fav');
            $table->unsignedBigInteger('cod_prendas_fav');
            $table->timestamp('fec_fav')->nullable();
            $table->timestamps();

            $table->foreign('cod_usuarios_fav')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('cod_prendas_fav')
                ->references('id_prenda')
                ->on('prendas')
                ->cascadeOnDelete();

            $table->unique(
                ['cod_usuarios_fav', 'cod_prendas_fav'],
                'uq_usuario_prenda_favorito'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favoritos');
    }
};
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
            $table->string('nom_pre', 150);
            $table->text('des_pre')->nullable();
            $table->unsignedBigInteger('cod_categorias_pre');
            $table->unsignedBigInteger('cod_marcas_pre')->nullable();
            $table->unsignedBigInteger('cod_temporadas_pre')->nullable();
            $table->string('gen_pre', 20)->nullable();
            $table->decimal('prb_pre', 10, 2)->default(0);
            $table->boolean('est_pre')->default(true);
            $table->unsignedBigInteger('cod_usuario_registra_pre');
            $table->timestamp('fec_pre')->nullable();
            $table->timestamps();

            $table->foreign('cod_categorias_pre')
                ->references('id_categoria')
                ->on('categorias')
                ->restrictOnDelete();

            $table->foreign('cod_marcas_pre')
                ->references('id_marca')
                ->on('marcas')
                ->nullOnDelete();

            $table->foreign('cod_temporadas_pre')
                ->references('id_temporada')
                ->on('temporadas')
                ->nullOnDelete();

            $table->foreign('cod_usuario_registra_pre')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index('nom_pre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prendas');
    }
};
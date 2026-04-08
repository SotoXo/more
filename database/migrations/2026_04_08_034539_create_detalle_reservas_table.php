<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_reservas', function (Blueprint $table) {
            $table->id('id_detalle_reserva');
            $table->unsignedBigInteger('cod_reservas_dre');
            $table->unsignedBigInteger('cod_prenda_variantes_dre');
            $table->integer('can_dre');
            $table->timestamp('fec_dre')->nullable();
            $table->timestamps();

            $table->foreign('cod_reservas_dre')
                ->references('id_reserva')
                ->on('reservas')
                ->cascadeOnDelete();

            $table->foreign('cod_prenda_variantes_dre')
                ->references('id_prenda_variante')
                ->on('prenda_variantes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_reservas');
    }
};
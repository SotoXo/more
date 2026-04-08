<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id('id_detalle_venta');
            $table->unsignedBigInteger('cod_ventas_dve');
            $table->unsignedBigInteger('cod_prenda_variantes_dve');
            $table->integer('can_dve');
            $table->decimal('pun_dve', 10, 2);
            $table->decimal('sub_dve', 10, 2);
            $table->timestamp('fec_dve')->nullable();
            $table->timestamps();

            $table->foreign('cod_ventas_dve')
                ->references('id_venta')
                ->on('ventas')
                ->cascadeOnDelete();

            $table->foreign('cod_prenda_variantes_dve')
                ->references('id_prenda_variante')
                ->on('prenda_variantes')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
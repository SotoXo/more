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
            $table->unsignedBigInteger('id_venta');
            $table->unsignedBigInteger('id_prenda_variante');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->foreign('id_venta')
                ->references('id_venta')
                ->on('ventas')
                ->cascadeOnDelete();

            $table->foreign('id_prenda_variante')
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
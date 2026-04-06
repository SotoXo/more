<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id('id_movimiento_inventario');
            $table->unsignedBigInteger('id_inventario');
            $table->string('tipo_movimiento', 20);
            $table->integer('cantidad');
            $table->string('motivo', 100)->nullable();
            $table->string('referencia', 100)->nullable();
            $table->text('observacion')->nullable();
            $table->foreignId('realizado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->foreign('id_inventario')
                ->references('id_inventario')
                ->on('inventarios')
                ->cascadeOnDelete();

            $table->index('tipo_movimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
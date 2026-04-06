<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interacciones_usuarios', function (Blueprint $table) {
            $table->id('id_interaccion_usuario');
            $table->foreignId('id_usuario')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('id_prenda');
            $table->string('tipo_interaccion', 30);
            $table->jsonb('detalle_interaccion')->nullable();
            $table->timestamps();

            $table->foreign('id_prenda')
                ->references('id_prenda')
                ->on('prendas')
                ->cascadeOnDelete();

            $table->index('tipo_interaccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interacciones_usuarios');
    }
};
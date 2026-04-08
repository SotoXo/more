<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id('id_reserva');
            $table->string('nre_res', 50)->unique();
            $table->unsignedBigInteger('cod_usuario_cliente_res');
            $table->unsignedBigInteger('cod_usuario_responsable_res');
            $table->string('est_res', 30)->default('activa');
            $table->timestamp('ven_res')->nullable();
            $table->text('obs_res')->nullable();
            $table->timestamp('fec_res')->nullable();
            $table->timestamps();

            $table->foreign('cod_usuario_cliente_res')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('cod_usuario_responsable_res')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
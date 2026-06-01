<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reserva_pagos', function (Blueprint $table) {
            $table->id('id_reserva_pago');

            $table->foreignId('cod_reservas_rpg')
                ->constrained('reservas', 'id_reserva')
                ->cascadeOnDelete();

            $table->decimal('monto_declarado_rpg', 10, 2)
                ->default(0);

            $table->decimal('monto_confirmado_rpg', 10, 2)
                ->default(0);

            $table->decimal('porcentaje_rpg', 5, 2)
                ->default(0);

            $table->string('metodo_pago_rpg', 30)
                ->nullable();

            $table->string('comprobante_ruta_rpg', 255)
                ->nullable();

            $table->string('comprobante_url_rpg', 500)
                ->nullable();

            $table->string('est_rpg', 30)
                ->default('pendiente_revision');

            $table->foreignId('cod_usuario_verifica_rpg')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->timestamp('fec_verificacion_rpg')
                ->nullable();

            $table->text('obs_rpg')
                ->nullable();

            $table->timestamp('fec_rpg')
                ->nullable();

            $table->timestamps();

            $table->index('cod_reservas_rpg');
            $table->index('est_rpg');
            $table->index(['cod_reservas_rpg', 'est_rpg'], 'idx_reserva_pagos_reserva_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserva_pagos');
    }
};
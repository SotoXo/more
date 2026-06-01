<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_pagos', function (Blueprint $table) {
            $table->id('id_venta_pago');

            $table->foreignId('cod_ventas_vpg')
                ->constrained('ventas', 'id_venta')
                ->cascadeOnDelete();

            $table->decimal('monto_declarado_vpg', 10, 2)
                ->default(0);

            $table->decimal('monto_verificado_vpg', 10, 2)
                ->default(0);

            $table->string('metodo_pago_vpg', 30);

            $table->string('comprobante_ruta_vpg', 255)
                ->nullable();

            $table->string('comprobante_url_vpg', 500)
                ->nullable();

            $table->string('est_vpg', 30)
                ->default('pendiente_revision');

            $table->foreignId('cod_usuario_verifica_vpg')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->timestamp('fec_verificacion_vpg')
                ->nullable();

            $table->text('obs_vpg')
                ->nullable();

            $table->timestamp('fec_vpg')
                ->nullable();

            $table->timestamps();

            $table->index('cod_ventas_vpg');
            $table->index('est_vpg');
            $table->index('metodo_pago_vpg');
            $table->index(['cod_ventas_vpg', 'est_vpg'], 'idx_venta_pagos_venta_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_pagos');
    }
};
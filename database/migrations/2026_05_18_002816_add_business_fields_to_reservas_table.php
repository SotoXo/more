<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->decimal('sub_res', 10, 2)
                ->default(0)
                ->after('est_res');

            $table->decimal('des_res', 10, 2)
                ->default(0)
                ->after('sub_res');

            $table->decimal('tot_res', 10, 2)
                ->default(0)
                ->after('des_res');

            $table->decimal('monto_pagado_res', 10, 2)
                ->default(0)
                ->after('tot_res');

            $table->decimal('saldo_res', 10, 2)
                ->default(0)
                ->after('monto_pagado_res');

            $table->string('est_pago_res', 30)
                ->default('pendiente_revision')
                ->after('saldo_res');

            $table->timestamp('fec_disponible_recojo_res')
                ->nullable()
                ->after('ven_res');

            $table->timestamp('fec_limite_recojo_res')
                ->nullable()
                ->after('fec_disponible_recojo_res');

            $table->string('punto_recojo_res', 150)
                ->nullable()
                ->after('fec_limite_recojo_res');

            $table->string('origen_stock_res', 30)
                ->nullable()
                ->after('punto_recojo_res');

            $table->foreignId('cod_usuario_verifica_pago_res')
                ->nullable()
                ->after('origen_stock_res')
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->timestamp('fec_verificacion_pago_res')
                ->nullable()
                ->after('cod_usuario_verifica_pago_res');

            $table->text('obs_pago_res')
                ->nullable()
                ->after('fec_verificacion_pago_res');

            $table->index('est_pago_res');
            $table->index(['est_res', 'est_pago_res']);
            $table->index(['fec_disponible_recojo_res', 'fec_limite_recojo_res'], 'idx_reservas_fechas_recojo');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropForeign(['cod_usuario_verifica_pago_res']);

            $table->dropIndex(['est_pago_res']);
            $table->dropIndex(['est_res', 'est_pago_res']);
            $table->dropIndex('idx_reservas_fechas_recojo');

            $table->dropColumn([
                'sub_res',
                'des_res',
                'tot_res',
                'monto_pagado_res',
                'saldo_res',
                'est_pago_res',
                'fec_disponible_recojo_res',
                'fec_limite_recojo_res',
                'punto_recojo_res',
                'origen_stock_res',
                'cod_usuario_verifica_pago_res',
                'fec_verificacion_pago_res',
                'obs_pago_res',
            ]);
        });
    }
};
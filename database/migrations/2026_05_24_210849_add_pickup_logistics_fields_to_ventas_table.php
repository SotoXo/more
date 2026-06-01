<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('est_logistico_ven', 30)
                ->default('pendiente_revision')
                ->after('est_ven');

            $table->timestamp('fec_disponible_recojo_ven')
                ->nullable()
                ->after('fec_ven');

            $table->timestamp('fec_limite_recojo_ven')
                ->nullable()
                ->after('fec_disponible_recojo_ven');

            $table->string('punto_recojo_ven', 150)
                ->nullable()
                ->after('fec_limite_recojo_ven');

            $table->string('origen_stock_ven', 30)
                ->nullable()
                ->after('punto_recojo_ven');

            $table->timestamp('fec_entrega_ven')
                ->nullable()
                ->after('origen_stock_ven');

            $table->foreignId('cod_usuario_entrega_ven')
                ->nullable()
                ->after('fec_entrega_ven')
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->index('est_logistico_ven');
            $table->index(['fec_disponible_recojo_ven', 'fec_limite_recojo_ven'], 'idx_ventas_fechas_recojo');
            $table->index('origen_stock_ven');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['cod_usuario_entrega_ven']);

            $table->dropIndex(['est_logistico_ven']);
            $table->dropIndex('idx_ventas_fechas_recojo');
            $table->dropIndex(['origen_stock_ven']);

            $table->dropColumn([
                'est_logistico_ven',
                'fec_disponible_recojo_ven',
                'fec_limite_recojo_ven',
                'punto_recojo_ven',
                'origen_stock_ven',
                'fec_entrega_ven',
                'cod_usuario_entrega_ven',
            ]);
        });
    }
};
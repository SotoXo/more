<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->string('est_logistico_res', 30)
                ->default('pendiente_revision')
                ->after('est_pago_res');

            $table->timestamp('fec_recogida_res')
                ->nullable()
                ->after('fec_limite_recojo_res');

            $table->foreignId('cod_usuario_entrega_res')
                ->nullable()
                ->after('fec_recogida_res')
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->index('est_logistico_res');
            $table->index('fec_recogida_res');
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropForeign(['cod_usuario_entrega_res']);

            $table->dropIndex(['est_logistico_res']);
            $table->dropIndex(['fec_recogida_res']);

            $table->dropColumn([
                'est_logistico_res',
                'fec_recogida_res',
                'cod_usuario_entrega_res',
            ]);
        });
    }
};
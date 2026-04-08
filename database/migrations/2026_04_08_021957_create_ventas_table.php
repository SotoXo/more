<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('id_venta');
            $table->string('nve_ven', 50)->unique();
            $table->unsignedBigInteger('cod_usuario_cliente_ven')->nullable();
            $table->unsignedBigInteger('cod_usuario_vendedor_ven');
            $table->string('tiv_ven', 20)->default('fisica');
            $table->decimal('sub_ven', 10, 2)->default(0);
            $table->decimal('des_ven', 10, 2)->default(0);
            $table->decimal('tot_ven', 10, 2)->default(0);
            $table->string('mpa_ven', 30)->nullable();
            $table->string('est_ven', 30)->default('registrada');
            $table->timestamp('fec_ven')->nullable();
            $table->timestamps();

            $table->foreign('cod_usuario_cliente_ven')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('cod_usuario_vendedor_ven')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index(['cod_usuario_cliente_ven', 'cod_usuario_vendedor_ven']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
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
            $table->string('numero_venta', 50)->unique();

            $table->foreignId('id_usuario_cliente')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('id_usuario_vendedor')->constrained('users')->restrictOnDelete();

            $table->string('tipo_venta', 20)->default('fisica');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('metodo_pago', 30)->nullable();
            $table->string('estado', 30)->default('registrada');

            $table->timestamps();

            $table->index(['id_usuario_cliente', 'id_usuario_vendedor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
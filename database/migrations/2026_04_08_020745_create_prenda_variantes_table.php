<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_variantes', function (Blueprint $table) {
            $table->id('id_prenda_variante');
            $table->unsignedBigInteger('cod_prendas_prv');
            $table->unsignedBigInteger('cod_colores_prv');
            $table->unsignedBigInteger('cod_tallas_prv');
            $table->string('sku_prv', 50)->unique();
            $table->string('cob_prv', 100)->nullable()->unique();
            $table->decimal('pve_prv', 10, 2)->default(0);
            $table->decimal('cos_prv', 10, 2)->nullable();
            $table->boolean('est_prv')->default(true);
            $table->timestamp('fec_prv')->nullable();
            $table->timestamps();

            $table->foreign('cod_prendas_prv')
                ->references('id_prenda')
                ->on('prendas')
                ->cascadeOnDelete();

            $table->foreign('cod_colores_prv')
                ->references('id_color')
                ->on('colores')
                ->restrictOnDelete();

            $table->foreign('cod_tallas_prv')
                ->references('id_talla')
                ->on('tallas')
                ->restrictOnDelete();

            $table->unique(
                ['cod_prendas_prv', 'cod_colores_prv', 'cod_tallas_prv'],
                'uq_prenda_color_talla'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_variantes');
    }
};
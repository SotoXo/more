<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id('id_inventario');
            $table->unsignedBigInteger('cod_prenda_variantes_inv')->unique();
            $table->integer('cdi_inv')->default(0);
            $table->integer('cre_inv')->default(0);
            $table->string('ubi_inv', 100)->nullable();
            $table->timestamps();

            $table->foreign('cod_prenda_variantes_inv')
                ->references('id_prenda_variante')
                ->on('prenda_variantes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
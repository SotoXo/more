<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categoria_complementarias', function (Blueprint $table) {
            $table->id('id_categoria_complementaria');
            $table->foreignId('cod_categoria_origen_cc')->constrained('categorias', 'id_categoria')->cascadeOnDelete();
            $table->foreignId('cod_categoria_destino_cc')->constrained('categorias', 'id_categoria')->cascadeOnDelete();
            $table->unsignedSmallInteger('pri_cc')->default(1);
            $table->boolean('est_cc')->default(true);
            $table->timestamps();
            $table->unique(['cod_categoria_origen_cc', 'cod_categoria_destino_cc'], 'uq_categoria_complementaria');
            $table->index(['cod_categoria_origen_cc', 'est_cc', 'pri_cc'], 'idx_categoria_complementaria_origen');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE categoria_complementarias ADD CONSTRAINT ck_categoria_complementaria_distinta CHECK (cod_categoria_origen_cc <> cod_categoria_destino_cc)');
            DB::statement('ALTER TABLE categoria_complementarias ADD CONSTRAINT ck_categoria_complementaria_prioridad CHECK (pri_cc >= 1)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_complementarias');
    }
};

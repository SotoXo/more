<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estilos', function (Blueprint $table) {
            $table->id('id_estilo');
            $table->string('nombre_estilo', 100)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estilos');
    }
};
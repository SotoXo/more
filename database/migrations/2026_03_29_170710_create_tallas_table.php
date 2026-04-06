<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tallas', function (Blueprint $table) {
            $table->id('id_talla');
            $table->string('nombre_talla', 20)->unique();
            $table->unsignedInteger('orden_visualizacion')->default(0);
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tallas');
    }
};
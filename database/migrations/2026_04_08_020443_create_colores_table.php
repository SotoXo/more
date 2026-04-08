<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colores', function (Blueprint $table) {
            $table->id('id_color');
            $table->string('nom_col', 50)->unique();
            $table->string('hex_col', 7)->nullable();
            $table->boolean('est_col')->default(true);
            $table->timestamp('fec_col')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colores');
    }
};
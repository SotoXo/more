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
            $table->string('nom_tal', 20)->unique();
            $table->integer('ord_tal')->default(0);
            $table->boolean('est_tal')->default(true);
            $table->timestamp('fec_tal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tallas');
    }
};
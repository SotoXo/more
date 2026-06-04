<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_bak', 150);
            $table->string('archivo_bak', 255);
            $table->string('ruta_bak', 500);
            $table->string('disco_bak', 50)->default('local');
            $table->unsignedBigInteger('tamano_bak')->nullable();
            $table->string('estado_bak', 30)->default('pendiente');
            $table->text('mensaje_bak')->nullable();
            $table->foreignId('cod_usuario_bak')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();
            $table->timestamp('fec_bak')->nullable();
            $table->timestamps();

            $table->index('estado_bak');
            $table->index('cod_usuario_bak');
            $table->index('fec_bak');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_backups');
    }
};

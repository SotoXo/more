<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_danio_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cod_prenda_danio_reporte_pdi')
                ->constrained('prenda_danio_reportes')
                ->cascadeOnDelete();
            $table->string('ruta_pdi', 500);
            $table->string('url_pdi', 500)->nullable();
            $table->string('descripcion_pdi', 255)->nullable();
            $table->integer('orden_pdi')->default(1);
            $table->timestamps();

            $table->index('cod_prenda_danio_reporte_pdi', 'idx_pdi_reporte');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_danio_imagenes');
    }
};

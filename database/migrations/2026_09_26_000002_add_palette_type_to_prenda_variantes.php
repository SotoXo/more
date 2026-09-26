<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_variantes', function (Blueprint $table) {
            $table->string('tip_pal_prv', 16)->default('dominante');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE prenda_variantes ADD CONSTRAINT ck_prenda_variante_tipo_paleta CHECK (tip_pal_prv IN ('dominante', 'equilibrada'))");
        }
    }

    public function down(): void
    {
        Schema::table('prenda_variantes', function (Blueprint $table) {
            $table->dropColumn('tip_pal_prv');
        });
    }
};

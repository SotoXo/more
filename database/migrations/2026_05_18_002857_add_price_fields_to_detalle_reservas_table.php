<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_reservas', function (Blueprint $table) {
            $table->decimal('pun_dre', 10, 2)
                ->default(0)
                ->after('can_dre');

            $table->decimal('sub_dre', 10, 2)
                ->default(0)
                ->after('pun_dre');

            $table->index('cod_prenda_variantes_dre');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_reservas', function (Blueprint $table) {
            $table->dropIndex(['cod_prenda_variantes_dre']);

            $table->dropColumn([
                'pun_dre',
                'sub_dre',
            ]);
        });
    }
};
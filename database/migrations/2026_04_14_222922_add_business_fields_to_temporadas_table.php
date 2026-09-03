<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporadas', function (Blueprint $table) {
            $table->string('cod_tem', 20)->nullable()->after('nom_tem');
            $table->integer('ord_tem')->default(0)->after('des_tem');

            $table->unique('cod_tem');
            $table->index('ord_tem');
        });
    }

    public function down(): void
    {
        Schema::table('temporadas', function (Blueprint $table) {
            $table->dropUnique(['cod_tem']);
            $table->dropIndex(['ord_tem']);

            $table->dropColumn([
                'cod_tem',
                'ord_tem',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temporadas', function (Blueprint $table) {
            $table->date('fec_ini_tem')->nullable()->after('des_tem');
            $table->date('fec_fin_tem')->nullable()->after('fec_ini_tem');

            $table->index(['fec_ini_tem', 'fec_fin_tem']);
        });
    }

    public function down(): void
    {
        Schema::table('temporadas', function (Blueprint $table) {
            $table->dropIndex(['fec_ini_tem', 'fec_fin_tem']);

            $table->dropColumn([
                'fec_ini_tem',
                'fec_fin_tem',
            ]);
        });
    }
};
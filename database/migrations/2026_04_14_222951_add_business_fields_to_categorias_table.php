<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('tip_tal_cat', 20)->nullable()->after('des_cat');

            $table->index('tip_tal_cat');
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropIndex(['tip_tal_cat']);
            $table->dropColumn('tip_tal_cat');
        });
    }
};
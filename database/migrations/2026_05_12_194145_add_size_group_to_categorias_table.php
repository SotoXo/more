<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('gru_tal_cat', 30)
                ->nullable()
                ->after('tip_tal_cat');

            $table->index('gru_tal_cat');
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropIndex(['gru_tal_cat']);
            $table->dropColumn('gru_tal_cat');
        });
    }
};
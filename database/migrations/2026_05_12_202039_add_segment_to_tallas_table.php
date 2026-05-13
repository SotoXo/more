<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tallas', function (Blueprint $table) {
            $table->string('seg_tal', 20)
                ->default('ADULTO')
                ->after('gru_tal');

            $table->index('seg_tal');
            $table->index(['seg_tal', 'sis_tal', 'gru_tal']);
        });
    }

    public function down(): void
    {
        Schema::table('tallas', function (Blueprint $table) {
            $table->dropIndex(['seg_tal']);
            $table->dropIndex(['seg_tal', 'sis_tal', 'gru_tal']);

            $table->dropColumn('seg_tal');
        });
    }
};
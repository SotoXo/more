<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tallas', function (Blueprint $table) {
            $table->string('sis_tal', 20)->nullable()->after('nom_tal');
            $table->string('gru_tal', 30)->nullable()->after('sis_tal');

            $table->index('sis_tal');
            $table->index('gru_tal');
        });
    }

    public function down(): void
    {
        Schema::table('tallas', function (Blueprint $table) {
            $table->dropIndex(['sis_tal']);
            $table->dropIndex(['gru_tal']);

            $table->dropColumn([
                'sis_tal',
                'gru_tal',
            ]);
        });
    }
};
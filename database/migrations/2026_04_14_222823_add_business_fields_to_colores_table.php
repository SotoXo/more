<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colores', function (Blueprint $table) {
            $table->string('fam_col', 30)->nullable()->after('nom_col');
            $table->char('hex_dom_col', 7)->nullable()->after('hex_col');
            $table->integer('ord_col')->default(0)->after('hex_dom_col');

            $table->index('fam_col');
            $table->index('ord_col');
        });
    }

    public function down(): void
    {
        Schema::table('colores', function (Blueprint $table) {
            $table->dropIndex(['fam_col']);
            $table->dropIndex(['ord_col']);

            $table->dropColumn([
                'fam_col',
                'hex_dom_col',
                'ord_col',
            ]);
        });
    }
};
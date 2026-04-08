<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nom_usu', 100)->after('id');
            $table->string('app_usu', 100)->after('nom_usu');
            $table->string('apm_usu', 100)->nullable()->after('app_usu');
            $table->string('tel_usu', 20)->nullable()->after('apm_usu');
            $table->unsignedBigInteger('cod_roles_usu')->nullable()->after('tel_usu');
            $table->boolean('est_usu')->default(true)->after('password');
            $table->timestamp('fec_usu')->nullable()->after('est_usu');

            $table->foreign('cod_roles_usu')
                ->references('id_rol')
                ->on('roles')
                ->nullOnDelete();

            $table->index('cod_roles_usu');
            $table->index('tel_usu');
            $table->index('app_usu');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cod_roles_usu']);
            $table->dropIndex(['cod_roles_usu']);
            $table->dropIndex(['tel_usu']);
            $table->dropIndex(['app_usu']);

            $table->dropColumn([
                'nom_usu',
                'app_usu',
                'apm_usu',
                'tel_usu',
                'cod_roles_usu',
                'est_usu',
                'fec_usu',
            ]);
        });
    }
};
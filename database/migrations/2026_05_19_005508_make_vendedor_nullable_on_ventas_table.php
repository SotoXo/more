<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ventas', 'cod_usuario_vendedor_ven')) {
            DB::statement('ALTER TABLE ventas ALTER COLUMN cod_usuario_vendedor_ven DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ventas', 'cod_usuario_vendedor_ven')) {
            DB::statement('ALTER TABLE ventas ALTER COLUMN cod_usuario_vendedor_ven SET NOT NULL');
        }
    }
};
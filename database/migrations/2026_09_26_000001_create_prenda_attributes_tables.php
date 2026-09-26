<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrones', function (Blueprint $table) {
            $table->id('id_patron');
            $table->string('nom_pat', 100)->unique();
            $table->string('des_pat', 255)->nullable();
            $table->boolean('est_pat')->default(true);
            $table->unsignedInteger('ord_pat')->default(0);
            $table->timestamps();
        });
        Schema::create('texturas', function (Blueprint $table) {
            $table->id('id_textura');
            $table->string('nom_tex', 100)->unique();
            $table->string('des_tex', 255)->nullable();
            $table->boolean('est_tex')->default(true);
            $table->unsignedInteger('ord_tex')->default(0);
            $table->timestamps();
        });
        Schema::create('materiales', function (Blueprint $table) {
            $table->id('id_material');
            $table->string('nom_mat', 100)->unique();
            $table->string('des_mat', 255)->nullable();
            $table->boolean('est_mat')->default(true);
            $table->timestamps();
        });
        Schema::create('prenda_patrones', function (Blueprint $table) {
            $table->id('id_prenda_patron');
            $table->foreignId('cod_prenda_ppn')->constrained('prendas', 'id_prenda')->cascadeOnDelete();
            $table->foreignId('cod_patron_ppn')->constrained('patrones', 'id_patron')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['cod_prenda_ppn', 'cod_patron_ppn']);
        });
        Schema::create('prenda_texturas', function (Blueprint $table) {
            $table->id('id_prenda_textura');
            $table->foreignId('cod_prenda_ptx')->constrained('prendas', 'id_prenda')->cascadeOnDelete();
            $table->foreignId('cod_textura_ptx')->constrained('texturas', 'id_textura')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['cod_prenda_ptx', 'cod_textura_ptx']);
        });
        Schema::create('prenda_materiales', function (Blueprint $table) {
            $table->id('id_prenda_material');
            $table->foreignId('cod_prenda_pmt')->constrained('prendas', 'id_prenda')->cascadeOnDelete();
            $table->foreignId('cod_material_pmt')->constrained('materiales', 'id_material')->restrictOnDelete();
            $table->decimal('porcentaje_pmt', 5, 2)->nullable();
            $table->unsignedInteger('ord_pmt')->default(0);
            $table->timestamps();
            $table->unique(['cod_prenda_pmt', 'cod_material_pmt']);
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE prenda_materiales ADD CONSTRAINT ck_prenda_material_porcentaje CHECK (porcentaje_pmt IS NULL OR (porcentaje_pmt > 0 AND porcentaje_pmt <= 100))');
        }
        Schema::create('prenda_variante_colores', function (Blueprint $table) {
            $table->id('id_prenda_variante_color');
            $table->foreignId('cod_prenda_variante_pvc')->constrained('prenda_variantes', 'id_prenda_variante')->cascadeOnDelete();
            $table->foreignId('cod_color_pvc')->constrained('colores', 'id_color')->restrictOnDelete();
            $table->boolean('es_principal_pvc')->default(false);
            $table->unsignedInteger('ord_pvc')->default(0);
            $table->timestamps();
            $table->unique(['cod_prenda_variante_pvc', 'cod_color_pvc'], 'uq_variante_color');
        });
        DB::statement('CREATE UNIQUE INDEX uq_variante_color_principal ON prenda_variante_colores (cod_prenda_variante_pvc) WHERE es_principal_pvc = true');
        // Backfill only actual colors; historical materials and patterns are unknown.
        DB::table('prenda_variantes')->orderBy('id_prenda_variante')->chunkById(500, function ($variants) {
            $now = now();
            DB::table('prenda_variante_colores')->insertOrIgnore($variants->map(fn ($variant) => [
                'cod_prenda_variante_pvc' => $variant->id_prenda_variante,
                'cod_color_pvc' => $variant->cod_colores_prv,
                'es_principal_pvc' => true,
                'ord_pvc' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }, 'id_prenda_variante');
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_variante_colores');
        Schema::dropIfExists('prenda_materiales');
        Schema::dropIfExists('prenda_texturas');
        Schema::dropIfExists('prenda_patrones');
        Schema::dropIfExists('materiales');
        Schema::dropIfExists('texturas');
        Schema::dropIfExists('patrones');
    }
};

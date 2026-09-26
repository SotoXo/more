<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Patron;
use App\Models\Textura;
use Illuminate\Database\Seeder;

class PrendaAttributeCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['LISO', 'RAYAS', 'CUADROS', 'ESTAMPADO', 'FLORAL', 'GEOMÉTRICO', 'CAMUFLAJE', 'ANIMAL PRINT', 'DEGRADADO', 'OTRO'] as $order => $name) {
            Patron::firstOrCreate(['nom_pat' => $name], ['est_pat' => true, 'ord_pat' => $order + 1]);
        }
        foreach (['LISA', 'ACANALADA', 'TEJIDA', 'WAFFLE', 'FELPA', 'ATERCIOPELADA', 'DENIM', 'TEXTURIZADA', 'CALADA', 'OTRA'] as $order => $name) {
            Textura::firstOrCreate(['nom_tex' => $name], ['est_tex' => true, 'ord_tex' => $order + 1]);
        }
        foreach (['ALGODÓN', 'POLIÉSTER', 'ELASTANO', 'LANA', 'LINO', 'VISCOSA', 'NYLON', 'ACRÍLICO', 'CUERO', 'CUERO SINTÉTICO', 'SEDA', 'OTRO'] as $name) {
            Material::firstOrCreate(['nom_mat' => $name], ['est_mat' => true]);
        }
    }
}

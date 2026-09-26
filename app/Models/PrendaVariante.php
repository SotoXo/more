<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrendaVariante extends Model
{
    protected $table = 'prenda_variantes';
    protected $primaryKey = 'id_prenda_variante';

    protected $fillable = [
        'cod_prendas_prv',
        'cod_colores_prv',
        'tip_pal_prv',
        'cod_tallas_prv',
        'sku_prv',
        'cob_prv',
        'pve_prv',
        'cos_prv',
        'est_prv',
        'fec_prv',
    ];

    protected $casts = [
        'pve_prv' => 'decimal:2',
        'cos_prv' => 'decimal:2',
        'est_prv' => 'boolean',
        'fec_prv' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $variant): void {
            if (!$variant->wasRecentlyCreated && !$variant->wasChanged('cod_colores_prv')) return;
            // Some isolated legacy tests build only a subset of the schema.
            if (!Schema::hasTable('prenda_variante_colores')) return;
            $previousReference = DB::table('prenda_variante_colores')
                ->where('cod_prenda_variante_pvc', $variant->getKey())
                ->where('es_principal_pvc', true)
                ->where('cod_color_pvc', '!=', $variant->cod_colores_prv);
            if (($variant->tip_pal_prv ?? 'dominante') === 'equilibrada') {
                // A balanced palette keeps the former reference as another visible color.
                $previousReference->update(['es_principal_pvc' => false, 'updated_at' => now()]);
            } else {
                $previousReference->delete();
            }
            DB::table('prenda_variante_colores')->upsert([[
                'cod_prenda_variante_pvc' => $variant->getKey(),
                'cod_color_pvc' => $variant->cod_colores_prv,
                'es_principal_pvc' => true,
                'ord_pvc' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['cod_prenda_variante_pvc', 'cod_color_pvc'], ['es_principal_pvc', 'ord_pvc', 'updated_at']);
        });
    }

    public function colores()
    {
        return $this->belongsToMany(Color::class, 'prenda_variante_colores', 'cod_prenda_variante_pvc', 'cod_color_pvc', 'id_prenda_variante', 'id_color')->withPivot('es_principal_pvc', 'ord_pvc')->withTimestamps()->orderByPivot('ord_pvc');
    }

    public function prenda()
    {
        return $this->belongsTo(Prenda::class, 'cod_prendas_prv', 'id_prenda');
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'cod_colores_prv', 'id_color');
    }

    public function talla()
    {
        return $this->belongsTo(Talla::class, 'cod_tallas_prv', 'id_talla');
    }

    public function inventario()
    {
        return $this->hasOne(Inventario::class, 'cod_prenda_variantes_inv', 'id_prenda_variante');
    }

    public function movimientosInventario()
    {
        return $this->hasMany(InventarioMovimiento::class, 'cod_prenda_variante_inm', 'id_prenda_variante');
    }

    public function imagenes()
    {
        return $this->hasMany(ImagenPrenda::class, 'cod_prenda_variantes_igp', 'id_prenda_variante');
    }
}

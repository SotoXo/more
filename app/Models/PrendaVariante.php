<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrendaVariante extends Model
{
    protected $table = 'prenda_variantes';
    protected $primaryKey = 'id_prenda_variante';

    protected $fillable = [
        'cod_prendas_prv',
        'cod_colores_prv',
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

    public function imagenes()
    {
        return $this->hasMany(ImagenPrenda::class, 'cod_prenda_variantes_igp', 'id_prenda_variante');
    }
}

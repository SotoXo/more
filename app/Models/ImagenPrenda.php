<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagenPrenda extends Model
{
    protected $table = 'imagenes_prenda';
    protected $primaryKey = 'id_imagen_prenda';

    protected $fillable = [
        'cod_prendas_igp',
        'cod_prenda_variantes_igp',
        'rut_igp',
        'url_igp',
        'pri_igp',
        'ord_igp',
        'fue_igp',
        'fec_igp',
    ];

    protected $casts = [
        'pri_igp' => 'boolean',
        'ord_igp' => 'integer',
        'fec_igp' => 'datetime',
    ];

    public function prenda()
    {
        return $this->belongsTo(Prenda::class, 'cod_prendas_igp', 'id_prenda');
    }

    public function variante()
    {
        return $this->belongsTo(PrendaVariante::class, 'cod_prenda_variantes_igp', 'id_prenda_variante');
    }
}

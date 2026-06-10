<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrendaDanioImagen extends Model
{
    protected $table = 'prenda_danio_imagenes';

    protected $fillable = [
        'cod_prenda_danio_reporte_pdi',
        'ruta_pdi',
        'url_pdi',
        'descripcion_pdi',
        'orden_pdi',
    ];

    protected $casts = [
        'orden_pdi' => 'integer',
    ];

    public function reporte()
    {
        return $this->belongsTo(PrendaDanioReporte::class, 'cod_prenda_danio_reporte_pdi', 'id');
    }
}

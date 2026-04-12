<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    protected $table = 'detalle_ventas';
    protected $primaryKey = 'id_detalle_venta';

    protected $fillable = [
        'cod_ventas_dve',
        'cod_prenda_variantes_dve',
        'can_dve',
        'pun_dve',
        'sub_dve',
        'fec_dve',
    ];

    protected $casts = [
        'can_dve' => 'integer',
        'pun_dve' => 'decimal:2',
        'sub_dve' => 'decimal:2',
        'fec_dve' => 'datetime',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'cod_ventas_dve', 'id_venta');
    }

    public function variante()
    {
        return $this->belongsTo(PrendaVariante::class, 'cod_prenda_variantes_dve', 'id_prenda_variante')
            ->with(['prenda.imagenes', 'prenda.marca', 'color', 'talla']);
    }
}

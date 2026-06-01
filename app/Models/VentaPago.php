<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaPago extends Model
{
    protected $table = 'venta_pagos';
    protected $primaryKey = 'id_venta_pago';

    protected $fillable = [
        'cod_ventas_vpg',
        'monto_declarado_vpg',
        'monto_verificado_vpg',
        'metodo_pago_vpg',
        'comprobante_ruta_vpg',
        'comprobante_url_vpg',
        'est_vpg',
        'cod_usuario_verifica_vpg',
        'fec_verificacion_vpg',
        'obs_vpg',
        'fec_vpg',
    ];

    protected $casts = [
        'monto_declarado_vpg' => 'decimal:2',
        'monto_verificado_vpg' => 'decimal:2',
        'fec_verificacion_vpg' => 'datetime',
        'fec_vpg' => 'datetime',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'cod_ventas_vpg', 'id_venta');
    }

    public function usuarioVerifica()
    {
        return $this->belongsTo(User::class, 'cod_usuario_verifica_vpg', 'id');
    }
}

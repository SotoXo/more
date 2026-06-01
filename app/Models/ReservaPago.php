<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservaPago extends Model
{
    protected $table = 'reserva_pagos';
    protected $primaryKey = 'id_reserva_pago';

    protected $fillable = [
        'cod_reservas_rpg',
        'monto_declarado_rpg',
        'monto_confirmado_rpg',
        'porcentaje_rpg',
        'metodo_pago_rpg',
        'comprobante_ruta_rpg',
        'comprobante_url_rpg',
        'est_rpg',
        'cod_usuario_verifica_rpg',
        'fec_verificacion_rpg',
        'obs_rpg',
        'fec_rpg',
    ];

    protected $casts = [
        'monto_declarado_rpg' => 'decimal:2',
        'monto_confirmado_rpg' => 'decimal:2',
        'porcentaje_rpg' => 'decimal:2',
        'fec_verificacion_rpg' => 'datetime',
        'fec_rpg' => 'datetime',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'cod_reservas_rpg', 'id_reserva');
    }

    public function usuarioVerifica()
    {
        return $this->belongsTo(User::class, 'cod_usuario_verifica_rpg', 'id');
    }
}

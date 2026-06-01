<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';
    protected $primaryKey = 'id_reserva';

    protected $fillable = [
        'nre_res',
        'cod_usuario_cliente_res',
        'cod_usuario_responsable_res',
        'est_res',
        'sub_res',
        'des_res',
        'tot_res',
        'monto_pagado_res',
        'saldo_res',
        'est_pago_res',
        'est_logistico_res',
        'ven_res',
        'fec_disponible_recojo_res',
        'fec_limite_recojo_res',
        'fec_recogida_res',
        'punto_recojo_res',
        'origen_stock_res',
        'cod_usuario_entrega_res',
        'cod_usuario_verifica_pago_res',
        'fec_verificacion_pago_res',
        'obs_pago_res',
        'obs_res',
        'fec_res',
    ];

    protected $casts = [
        'sub_res' => 'decimal:2',
        'des_res' => 'decimal:2',
        'tot_res' => 'decimal:2',
        'monto_pagado_res' => 'decimal:2',
        'saldo_res' => 'decimal:2',
        'ven_res' => 'datetime',
        'fec_disponible_recojo_res' => 'datetime',
        'fec_limite_recojo_res' => 'datetime',
        'fec_recogida_res' => 'datetime',
        'fec_verificacion_pago_res' => 'datetime',
        'fec_res' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cod_usuario_cliente_res', 'id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'cod_usuario_responsable_res', 'id');
    }

    public function usuarioVerificaPago()
    {
        return $this->belongsTo(User::class, 'cod_usuario_verifica_pago_res', 'id');
    }

    public function usuarioEntrega()
    {
        return $this->belongsTo(User::class, 'cod_usuario_entrega_res', 'id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleReserva::class, 'cod_reservas_dre', 'id_reserva');
    }

    public function pagos()
    {
        return $this->hasMany(ReservaPago::class, 'cod_reservas_rpg', 'id_reserva');
    }
}

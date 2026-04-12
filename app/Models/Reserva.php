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
        'ven_res',
        'obs_res',
        'fec_res',
    ];

    protected $casts = [
        'ven_res' => 'datetime',
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

    public function detalles()
    {
        return $this->hasMany(DetalleReserva::class, 'cod_reservas_dre', 'id_reserva');
    }
}

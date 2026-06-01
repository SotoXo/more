<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';
    protected $primaryKey = 'id_venta';

    protected $fillable = [
        'nve_ven',
        'cod_usuario_cliente_ven',
        'cod_usuario_vendedor_ven',
        'tiv_ven',
        'sub_ven',
        'des_ven',
        'tot_ven',
        'mpa_ven',
        'est_ven',
        'est_logistico_ven',
        'fec_ven',
        'fec_disponible_recojo_ven',
        'fec_limite_recojo_ven',
        'punto_recojo_ven',
        'origen_stock_ven',
        'fec_entrega_ven',
        'cod_usuario_entrega_ven',
    ];

    protected $casts = [
        'sub_ven' => 'decimal:2',
        'des_ven' => 'decimal:2',
        'tot_ven' => 'decimal:2',
        'fec_ven' => 'datetime',
        'fec_disponible_recojo_ven' => 'datetime',
        'fec_limite_recojo_ven' => 'datetime',
        'fec_entrega_ven' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cod_usuario_cliente_ven', 'id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'cod_usuario_vendedor_ven', 'id');
    }

    public function usuarioEntrega()
    {
        return $this->belongsTo(User::class, 'cod_usuario_entrega_ven', 'id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'cod_ventas_dve', 'id_venta');
    }

    public function pagos()
    {
        return $this->hasMany(VentaPago::class, 'cod_ventas_vpg', 'id_venta');
    }
}

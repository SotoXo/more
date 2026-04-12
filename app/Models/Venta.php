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
        'fec_ven',
    ];

    protected $casts = [
        'sub_ven' => 'decimal:2',
        'des_ven' => 'decimal:2',
        'tot_ven' => 'decimal:2',
        'fec_ven' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cod_usuario_cliente_ven', 'id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'cod_usuario_vendedor_ven', 'id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class, 'cod_ventas_dve', 'id_venta');
    }
}

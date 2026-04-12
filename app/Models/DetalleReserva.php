<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleReserva extends Model
{
    protected $table = 'detalle_reservas';
    protected $primaryKey = 'id_detalle_reserva';

    protected $fillable = [
        'cod_reservas_dre',
        'cod_prenda_variantes_dre',
        'can_dre',
        'fec_dre',
    ];

    protected $casts = [
        'can_dre' => 'integer',
        'fec_dre' => 'datetime',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'cod_reservas_dre', 'id_reserva');
    }

    public function variante()
    {
        return $this->belongsTo(PrendaVariante::class, 'cod_prenda_variantes_dre', 'id_prenda_variante')
            ->with(['prenda.imagenes', 'prenda.marca', 'color', 'talla']);
    }
}

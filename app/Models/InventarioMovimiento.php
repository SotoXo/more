<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioMovimiento extends Model
{
    protected $table = 'inventario_movimientos';

    protected $fillable = [
        'cod_inventario_inm',
        'cod_prenda_variante_inm',
        'tipo_movimiento_inm',
        'cantidad_inm',
        'cdi_antes_inm',
        'cdi_despues_inm',
        'cre_antes_inm',
        'cre_despues_inm',
        'ubicacion_antes_inm',
        'ubicacion_despues_inm',
        'referencia_tipo_inm',
        'referencia_id_inm',
        'cod_usuario_inm',
        'descripcion_inm',
        'metadata_inm',
    ];

    protected $casts = [
        'metadata_inm' => 'array',
        'cantidad_inm' => 'integer',
        'cdi_antes_inm' => 'integer',
        'cdi_despues_inm' => 'integer',
        'cre_antes_inm' => 'integer',
        'cre_despues_inm' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'cod_inventario_inm', 'id_inventario');
    }

    public function variante()
    {
        return $this->belongsTo(PrendaVariante::class, 'cod_prenda_variante_inm', 'id_prenda_variante');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'cod_usuario_inm', 'id');
    }
}

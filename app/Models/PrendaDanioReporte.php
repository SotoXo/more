<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrendaDanioReporte extends Model
{
    protected $table = 'prenda_danio_reportes';

    protected $fillable = [
        'codigo_pdr',
        'nombre_prenda_pdr',
        'descripcion_prenda_pdr',
        'cod_prenda_pdr',
        'cod_prenda_variante_pdr',
        'cod_inventario_pdr',
        'cod_usuario_reporta_pdr',
        'cod_usuario_revisa_pdr',
        'causa_pdr',
        'tipo_danio_pdr',
        'severidad_pdr',
        'cantidad_pdr',
        'estado_pdr',
        'decision_pdr',
        'observacion_pdr',
        'observacion_revision_pdr',
        'fecha_reporte_pdr',
        'fecha_revision_pdr',
        'afecta_inventario_pdr',
        'metadata_pdr',
    ];

    protected $casts = [
        'fecha_reporte_pdr' => 'datetime',
        'fecha_revision_pdr' => 'datetime',
        'metadata_pdr' => 'array',
        'cantidad_pdr' => 'integer',
        'afecta_inventario_pdr' => 'boolean',
    ];

    public function prenda()
    {
        return $this->belongsTo(Prenda::class, 'cod_prenda_pdr', 'id_prenda');
    }

    public function variante()
    {
        return $this->belongsTo(PrendaVariante::class, 'cod_prenda_variante_pdr', 'id_prenda_variante');
    }

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'cod_inventario_pdr', 'id_inventario');
    }

    public function usuarioReporta()
    {
        return $this->belongsTo(User::class, 'cod_usuario_reporta_pdr', 'id');
    }

    public function usuarioRevisa()
    {
        return $this->belongsTo(User::class, 'cod_usuario_revisa_pdr', 'id');
    }

    public function imagenes()
    {
        return $this->hasMany(PrendaDanioImagen::class, 'cod_prenda_danio_reporte_pdi', 'id')
            ->orderBy('orden_pdi')
            ->orderBy('id');
    }
}

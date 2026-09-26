<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $table = 'colores';
    protected $primaryKey = 'id_color';

    protected $fillable = ['nom_col', 'fam_col', 'hex_col', 'hex_dom_col', 'ord_col', 'est_col', 'fec_col'];

    protected $casts = [
        'ord_col' => 'integer',
        'est_col' => 'boolean',
        'fec_col' => 'datetime',
    ];

    public function variantesEnPaleta()
    {
        return $this->belongsToMany(PrendaVariante::class, 'prenda_variante_colores', 'cod_color_pvc', 'cod_prenda_variante_pvc', 'id_color', 'id_prenda_variante')->withPivot('es_principal_pvc', 'ord_pvc')->withTimestamps();
    }

    public function variantes()
    {
        return $this->hasMany(PrendaVariante::class, 'cod_colores_prv', 'id_color');
    }
}

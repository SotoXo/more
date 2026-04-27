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

    public function variantes()
    {
        return $this->hasMany(PrendaVariante::class, 'cod_colores_prv', 'id_color');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $table = 'colores';
    protected $primaryKey = 'id_color';

    protected $fillable = ['nom_col', 'hex_col', 'est_col', 'fec_col'];

    protected $casts = [
        'est_col' => 'boolean',
        'fec_col' => 'datetime',
    ];

    public function variantes()
    {
        return $this->hasMany(PrendaVariante::class, 'cod_colores_prv', 'id_color');
    }
}

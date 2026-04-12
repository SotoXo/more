<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Talla extends Model
{
    protected $table = 'tallas';
    protected $primaryKey = 'id_talla';

    protected $fillable = ['nom_tal', 'ord_tal', 'est_tal', 'fec_tal'];

    protected $casts = [
        'est_tal' => 'boolean',
        'fec_tal' => 'datetime',
    ];

    public function variantes()
    {
        return $this->hasMany(PrendaVariante::class, 'cod_tallas_prv', 'id_talla');
    }
}

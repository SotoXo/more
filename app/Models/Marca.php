<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    protected $table = 'marcas';
    protected $primaryKey = 'id_marca';

    protected $fillable = ['nom_mar', 'des_mar', 'est_mar', 'fec_mar'];

    protected $casts = [
        'est_mar' => 'boolean',
        'fec_mar' => 'datetime',
    ];

    public function prendas()
    {
        return $this->hasMany(Prenda::class, 'cod_marcas_pre', 'id_marca');
    }
}

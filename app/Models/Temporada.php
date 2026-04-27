<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Temporada extends Model
{
    protected $table = 'temporadas';
    protected $primaryKey = 'id_temporada';

    protected $fillable = ['nom_tem', 'cod_tem', 'des_tem', 'ord_tem', 'est_tem', 'fec_tem'];

    protected $casts = [
        'ord_tem' => 'integer',
        'est_tem' => 'boolean',
        'fec_tem' => 'datetime',
    ];

    public function prendas()
    {
        return $this->hasMany(Prenda::class, 'cod_temporadas_pre', 'id_temporada');
    }
}

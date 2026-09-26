<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'id_categoria';

    protected $fillable = ['nom_cat', 'des_cat', 'tip_tal_cat', 'gru_tal_cat', 'cod_categoria_padre_cat', 'est_cat', 'fec_cat'];

    protected $casts = [
        'est_cat' => 'boolean',
        'fec_cat' => 'datetime',
    ];

    public function padre()
    {
        return $this->belongsTo(Categoria::class, 'cod_categoria_padre_cat', 'id_categoria');
    }

    public function hijos()
    {
        return $this->hasMany(Categoria::class, 'cod_categoria_padre_cat', 'id_categoria');
    }

    public function complementarias()
    {
        return $this->belongsToMany(self::class, 'categoria_complementarias', 'cod_categoria_origen_cc', 'cod_categoria_destino_cc', 'id_categoria', 'id_categoria')
            ->withPivot('pri_cc', 'est_cc')
            ->withTimestamps()
            ->orderByPivot('pri_cc');
    }

    public function prendas()
    {
        return $this->hasMany(Prenda::class, 'cod_categorias_pre', 'id_categoria');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorito extends Model
{
    protected $table = 'favoritos';
    protected $primaryKey = 'id_favorito';

    protected $fillable = ['cod_usuarios_fav', 'cod_prendas_fav', 'fec_fav'];

    protected $casts = [
        'fec_fav' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'cod_usuarios_fav', 'id');
    }

    public function prenda()
    {
        return $this->belongsTo(Prenda::class, 'cod_prendas_fav', 'id_prenda')
            ->with(['marca', 'categoria', 'imagenes', 'variantes.color', 'variantes.talla', 'variantes.inventario']);
    }
}

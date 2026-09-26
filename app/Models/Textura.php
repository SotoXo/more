<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Textura extends Model
{
    protected $table = 'texturas';
    protected $primaryKey = 'id_textura';
    protected $fillable = ['nom_tex', 'des_tex', 'est_tex', 'ord_tex'];
    protected $casts = [
        'est_tex' => 'boolean',
        'ord_tex' => 'integer',
    ];

    public function prendas()
    {
        return $this->belongsToMany(Prenda::class, 'prenda_texturas', 'cod_textura_ptx', 'cod_prenda_ptx', 'id_textura', 'id_prenda')->withTimestamps();
    }
}

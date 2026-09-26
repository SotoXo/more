<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prenda extends Model
{
    protected $table = 'prendas';
    protected $primaryKey = 'id_prenda';

    protected $fillable = [
        'nom_pre',
        'des_pre',
        'cod_categorias_pre',
        'cod_marcas_pre',
        'cod_temporadas_pre',
        'gen_pre',
        'prb_pre',
        'est_pre',
        'cod_usuario_registra_pre',
        'fec_pre',
    ];

    protected $casts = [
        'prb_pre' => 'decimal:2',
        'est_pre' => 'boolean',
        'fec_pre' => 'datetime',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'cod_categorias_pre', 'id_categoria');
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class, 'cod_marcas_pre', 'id_marca');
    }

    public function temporada()
    {
        return $this->belongsTo(Temporada::class, 'cod_temporadas_pre', 'id_temporada');
    }

    public function usuarioRegistra()
    {
        return $this->belongsTo(User::class, 'cod_usuario_registra_pre', 'id');
    }

    public function variantes()
    {
        return $this->hasMany(PrendaVariante::class, 'cod_prendas_prv', 'id_prenda');
    }

    public function imagenes()
    {
        return $this->hasMany(ImagenPrenda::class, 'cod_prendas_igp', 'id_prenda')
            ->orderBy('pri_igp', 'desc')
            ->orderBy('ord_igp');
    }

    public function patrones()
    {
        return $this->belongsToMany(Patron::class, 'prenda_patrones', 'cod_prenda_ppn', 'cod_patron_ppn', 'id_prenda', 'id_patron')->withTimestamps();
    }

    public function texturas()
    {
        return $this->belongsToMany(Textura::class, 'prenda_texturas', 'cod_prenda_ptx', 'cod_textura_ptx', 'id_prenda', 'id_textura')->withTimestamps();
    }

    public function materiales()
    {
        return $this->belongsToMany(Material::class, 'prenda_materiales', 'cod_prenda_pmt', 'cod_material_pmt', 'id_prenda', 'id_material')->withPivot('porcentaje_pmt', 'ord_pmt')->withTimestamps()->orderByPivot('ord_pmt');
    }

    public function favoritos()
    {
        return $this->hasMany(Favorito::class, 'cod_prendas_fav', 'id_prenda');
    }
}

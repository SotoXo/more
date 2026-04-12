<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventarios';
    protected $primaryKey = 'id_inventario';

    protected $fillable = ['cod_prenda_variantes_inv', 'cdi_inv', 'cre_inv', 'ubi_inv'];

    protected $casts = [
        'cdi_inv' => 'integer',
        'cre_inv' => 'integer',
    ];

    public function variante()
    {
        return $this->belongsTo(PrendaVariante::class, 'cod_prenda_variantes_inv', 'id_prenda_variante');
    }

    public function stockDisponible(): int
    {
        return max(0, $this->cdi_inv - $this->cre_inv);
    }
}

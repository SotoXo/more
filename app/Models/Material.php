<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table = 'materiales';
    protected $primaryKey = 'id_material';
    protected $fillable = ['nom_mat', 'des_mat', 'est_mat'];
    protected $casts = [
        'est_mat' => 'boolean',
    ];

    public function prendas()
    {
        return $this->belongsToMany(Prenda::class, 'prenda_materiales', 'cod_material_pmt', 'cod_prenda_pmt', 'id_material', 'id_prenda')->withTimestamps()->withPivot('porcentaje_pmt', 'ord_pmt');
    }
}

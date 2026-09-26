<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patron extends Model
{
    protected $table = 'patrones';
    protected $primaryKey = 'id_patron';
    protected $fillable = ['nom_pat', 'des_pat', 'est_pat', 'ord_pat'];
    protected $casts = [
        'est_pat' => 'boolean',
        'ord_pat' => 'integer',
    ];

    public function prendas()
    {
        return $this->belongsToMany(Prenda::class, 'prenda_patrones', 'cod_patron_ppn', 'cod_prenda_ppn', 'id_patron', 'id_prenda')->withTimestamps();
    }
}

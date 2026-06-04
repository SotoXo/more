<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseBackup extends Model
{
    protected $table = 'database_backups';

    protected $fillable = [
        'nombre_bak',
        'archivo_bak',
        'ruta_bak',
        'disco_bak',
        'tamano_bak',
        'estado_bak',
        'mensaje_bak',
        'cod_usuario_bak',
        'fec_bak',
    ];

    protected $casts = [
        'fec_bak' => 'datetime',
        'tamano_bak' => 'integer',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'cod_usuario_bak', 'id');
    }
}

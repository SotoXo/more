<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $table = 'system_logs';

    protected $fillable = [
        'cod_usuario_slg',
        'rol_slg',
        'modulo_slg',
        'accion_slg',
        'entidad_tipo_slg',
        'entidad_id_slg',
        'descripcion_slg',
        'nivel_slg',
        'ip_slg',
        'user_agent_slg',
        'metodo_http_slg',
        'ruta_slg',
        'metadata_slg',
    ];

    protected $casts = [
        'metadata_slg' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'cod_usuario_slg', 'id');
    }
}

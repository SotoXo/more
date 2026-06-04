<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'cod_usuario_aul',
        'tabla_aul',
        'registro_id_aul',
        'accion_aul',
        'modulo_aul',
        'valores_anteriores_aul',
        'valores_nuevos_aul',
        'campos_modificados_aul',
        'descripcion_aul',
        'nivel_aul',
        'ip_aul',
        'user_agent_aul',
        'ruta_aul',
    ];

    protected $casts = [
        'valores_anteriores_aul' => 'array',
        'valores_nuevos_aul' => 'array',
        'campos_modificados_aul' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'cod_usuario_aul', 'id');
    }
}

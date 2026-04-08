<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nom_rol', 'des_rol', 'est_rol', 'fec_rol'])]
class Role extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id_rol';

    /**
     * Get the users for the role.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'cod_roles_usu', 'id_rol');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'est_rol' => 'boolean',
            'fec_rol' => 'datetime',
        ];
    }
}

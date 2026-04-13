<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nom_usu', 'app_usu', 'apm_usu', 'tel_usu', 'email', 'password', 'cod_roles_usu', 'est_usu', 'fec_usu'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function nombreCompleto(): string
    {
        return trim(implode(' ', array_filter([
            $this->nom_usu,
            $this->app_usu,
            $this->apm_usu,
        ]))) ?: ($this->email ?? '');
    }

    public function isSuperadmin(): bool
    {
        $this->loadMissing('rol');

        return strcasecmp((string) $this->rol?->nom_rol, 'superadmin') === 0;
    }

    /**
     * Get the role associated with the user.
     */
    public function rol()
    {
        return $this->belongsTo(Role::class, 'cod_roles_usu', 'id_rol');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'est_usu' => 'boolean',
            'fec_usu' => 'datetime',
        ];
    }
}

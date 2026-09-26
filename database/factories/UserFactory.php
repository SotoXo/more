<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom_usu' => 'QA TEST',
            'app_usu' => 'PEREZ',
            'apm_usu' => 'LOPEZ',
            'tel_usu' => '70000000',
            'est_usu' => true,
            'fec_usu' => now(),
            'cod_roles_usu' => \App\Models\Role::firstOrCreate(['nom_rol' => 'cliente'], ['est_rol' => true, 'fec_rol' => now()])->id_rol,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

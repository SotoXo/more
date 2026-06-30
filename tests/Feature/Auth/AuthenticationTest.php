<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = $this->createUser();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = $this->createUser();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_inactive_users_can_not_authenticate(): void
    {
        $user = $this->createUser(['est_usu' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'login' => 'Esta cuenta está inactiva. Solicita la reactivación a un superadmin.',
        ]);
    }

    public function test_users_without_a_valid_active_role_can_not_authenticate(): void
    {
        $user = $this->createUser(['cod_roles_usu' => null]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'login' => 'Tu usuario no tiene un rol activo o válido. Solicita revisión a un superadmin.',
        ]);
    }

    public function test_users_with_inactive_roles_can_not_authenticate(): void
    {
        $role = $this->createRole('cliente', false);
        $user = $this->createUser(['cod_roles_usu' => $role->id_rol]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'login' => 'Tu usuario no tiene un rol activo o válido. Solicita revisión a un superadmin.',
        ]);
    }

    public function test_superadmin_users_are_redirected_to_superadmin_panel(): void
    {
        $role = $this->createRole('superadmin');
        $user = $this->createUser(['cod_roles_usu' => $role->id_rol]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/superadmin');
    }

    public function test_normal_users_do_not_keep_superadmin_intended_url(): void
    {
        $user = $this->createUser();

        $response = $this
            ->withSession(['url.intended' => 'http://localhost/superadmin'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
        $response->assertSessionHas(
            'error',
            'No tienes permisos para acceder al panel superadmin.',
        );
    }

    public function test_users_can_logout(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    private function createUser(array $overrides = []): User
    {
        $roleId = array_key_exists('cod_roles_usu', $overrides)
            ? $overrides['cod_roles_usu']
            : $this->createRole('cliente')->id_rol;

        return User::create(array_merge([
            'nom_usu' => 'Cliente',
            'app_usu' => 'Demo',
            'apm_usu' => null,
            'tel_usu' => '70000000',
            'email' => 'cliente'.uniqid().'@matiboutique.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'cod_roles_usu' => $roleId,
            'est_usu' => true,
            'fec_usu' => now(),
        ], $overrides));
    }

    private function createRole(string $name, bool $active = true): Role
    {
        return Role::firstOrCreate(
            ['nom_rol' => $name],
            [
                'des_rol' => 'Rol de prueba',
                'est_rol' => $active,
                'fec_rol' => now(),
            ],
        );
    }
}

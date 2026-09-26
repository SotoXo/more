<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        \App\Models\Role::create(['nom_rol' => 'cliente', 'est_rol' => true, 'fec_rol' => now()]);
        $response = $this->post('/register', [
            'nom_usu' => '   juan   josé  ',
            'app_usu' => 'PEREZ',
            'apm_usu' => 'LOPEZ',
            'tel_usu' => '70000000',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['nom_usu' => 'JUAN JOSÉ']);
        $response->assertRedirect('/');
    }
}

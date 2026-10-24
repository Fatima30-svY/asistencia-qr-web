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
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'rol' => 'alumno',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'rol' => 'alumno']);
    }

    public function test_teachers_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'rol' => 'docente',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'teacher@example.com', 'rol' => 'docente']);
        $this->get(route('docente.clases'))->assertForbidden();
        $this->get(route('dashboard'))->assertSee('pendiente de aprobación');
    }

    public function test_directors_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Director',
            'email' => 'director@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'rol' => 'admin',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'director@example.com', 'rol' => 'admin']);
        $this->get(route('docente.crear'))->assertOk();
    }

    public function test_registration_requires_a_public_role(): void
    {
        foreach ([null, 'director', 'otro'] as $role) {
            $response = $this->post('/register', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'rol' => $role,
            ]);

            $response->assertSessionHasErrors('rol');
            $this->assertGuest();
            $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        }
    }
}

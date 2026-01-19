<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function usuario_puede_registrarse_con_datos_validos()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'telefono' => '+52 55 1234 5678',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'telefono'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
        ]);
    }

    /** @test */
    public function registro_falla_con_email_duplicado()
    {
        User::factory()->create([
            'email' => 'juan@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function registro_falla_con_password_corto()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function registro_falla_con_passwords_no_coinciden()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function usuario_puede_hacer_login_con_credenciales_validas()
    {
        $user = User::factory()->create([
            'email' => 'juan@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'juan@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'telefono'],
                'token',
            ]);

        // Verificar que el token fue creado
        $this->assertCount(1, $user->fresh()->tokens);
    }

    /** @test */
    public function login_falla_con_credenciales_invalidas()
    {
        User::factory()->create([
            'email' => 'juan@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'juan@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function login_elimina_tokens_previos()
    {
        $user = User::factory()->create([
            'email' => 'juan@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Crear tokens previos
        $user->createToken('mobile-app');
        $user->createToken('mobile-app');

        $this->assertCount(2, $user->tokens);

        // Hacer login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'juan@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        // Solo debe existir 1 token (el nuevo)
        $this->assertCount(1, $user->fresh()->tokens);
    }

    /** @test */
    public function usuario_puede_hacer_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logout exitoso',
            ]);

        // Token debe haber sido eliminado
        $this->assertCount(0, $user->fresh()->tokens);
    }

    /** @test */
    public function usuario_autenticado_puede_ver_su_perfil()
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'telefono', 'email_verified_at'],
            ])
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }
}

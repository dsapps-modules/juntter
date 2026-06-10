<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_screen_redirects_to_the_spa(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_users_can_register_from_the_spa(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Nova Conta',
            'email' => 'nova.conta@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'redirect' => '/app/login?registered=1',
            ])
            ->assertJsonStructure(['message', 'redirect']);

        $this->assertDatabaseHas('users', [
            'name' => 'Nova Conta',
            'email' => 'nova.conta@example.com',
            'nivel_acesso' => 'vendedor',
        ]);

        $this->assertGuest();
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create([
            'email' => 'nova.conta@example.com',
        ]);

        $response = $this->postJson('/register', [
            'name' => 'Nova Conta',
            'email' => 'nova.conta@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}

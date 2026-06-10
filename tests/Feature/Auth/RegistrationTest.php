<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_screen_is_not_available_anymore(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_users_cannot_register_from_the_public_route_anymore(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Nova Conta',
            'email' => 'nova.conta@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertNotFound();

        $this->assertDatabaseMissing('users', [
            'email' => 'nova.conta@example.com',
        ]);
    }

    public function test_registration_requires_unique_email_is_no_longer_exposed_publicly(): void
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

        $response->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaProfileOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_overview_returns_authenticated_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/perfil');

        $response
            ->assertOk()
            ->assertJsonPath('profile.name', 'Maria Silva')
            ->assertJsonPath('profile.email', 'maria@example.com')
            ->assertJsonPath('profile.nivel_acesso', 'vendedor');
    }
}

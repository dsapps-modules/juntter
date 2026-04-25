<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaEstablishmentOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_establishment_overview_payload(): void
    {
        $admin = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/spa/estabelecimentos');

        $response->assertOk();
        $response->assertJsonStructure([
            'summary' => [
                'total_establishments',
                'active_establishments',
                'blocked_establishments',
                'total_revenue',
            ],
            'filters',
            'rows',
            'selected' => [
                'id',
                'name',
                'status',
                'email',
                'revenue',
                'active_tasks',
                'segment',
                'owner',
                'phone',
                'city',
                'timeline',
            ],
            'recent_transactions',
        ]);
    }
}

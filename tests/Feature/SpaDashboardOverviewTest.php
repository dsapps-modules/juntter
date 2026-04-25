<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaDashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_overview_returns_summary_data(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        PaytimeEstablishment::create([
            'id' => 1001,
            'fantasy_name' => 'Acme Corp',
            'email' => 'acme@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 1520.50,
            'address_json' => ['city' => 'São Paulo'],
            'responsible_json' => ['name' => 'Ana Souza'],
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-1',
            'establishment_id' => '1001',
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 35000,
            'original_amount' => 35000,
            'fees' => 0,
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/dashboard');

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_establishments', 1)
            ->assertJsonPath('summary.active_establishments', 1)
            ->assertJsonPath('summary.total_transactions', 1)
            ->assertJsonPath('rows.0.name', 'Acme Corp');
    }
}

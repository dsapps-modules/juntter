<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaEstablishmentOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_paginated_establishment_data(): void
    {
        $admin = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        foreach (range(1, 21) as $index) {
            PaytimeEstablishment::create([
                'id' => 5000 + $index,
                'fantasy_name' => "Estabelecimento {$index}",
                'email' => "estabelecimento{$index}@example.com",
                'phone_number' => '(83) 99999-0000',
                'active' => $index % 3 !== 0,
                'status' => $index % 5 === 0 ? 'BLOCKED' : 'APPROVED',
                'category' => 'Geral',
                'revenue' => 1000.00 * $index,
                'address_json' => [
                    'city' => 'João Pessoa',
                ],
                'responsible_json' => [
                    'name' => "Responsável {$index}",
                ],
            ]);
        }

        $response = $this->actingAs($admin)->getJson('/api/spa/estabelecimentos');

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_establishments', 21)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 20)
            ->assertJsonPath('pagination.total', 21)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonCount(20, 'rows');

        $secondPageResponse = $this->actingAs($admin)->getJson('/api/spa/estabelecimentos?page=2');

        $secondPageResponse
            ->assertOk()
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonCount(1, 'rows');
    }

    public function test_it_orders_establishments_by_display_name_ascending(): void
    {
        $admin = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        PaytimeEstablishment::create([
            'id' => 6001,
            'fantasy_name' => 'Zeta Comércio',
            'email' => 'zeta@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 1000.00,
        ]);

        PaytimeEstablishment::create([
            'id' => 6002,
            'fantasy_name' => 'Alfa Comércio',
            'email' => 'alfa@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 1000.00,
        ]);

        PaytimeEstablishment::create([
            'id' => 6003,
            'fantasy_name' => 'Meio Comércio',
            'email' => 'meio@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 1000.00,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/spa/estabelecimentos');

        $response
            ->assertOk()
            ->assertJsonPath('rows.0.name', 'Alfa Comércio')
            ->assertJsonPath('rows.1.name', 'Meio Comércio')
            ->assertJsonPath('rows.2.name', 'Zeta Comércio');
    }
}

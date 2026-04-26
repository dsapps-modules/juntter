<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaEstabelecimentoDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_establishment_details_for_admin(): void
    {
        $admin = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        PaytimeEstablishment::create([
            'id' => 9001,
            'type' => 'ACQUIRER',
            'first_name' => 'Enok',
            'last_name' => 'Motos',
            'fantasy_name' => 'Enok Motos',
            'document' => '04692879000130',
            'email' => 'enokmotos@gmail.com',
            'phone_number' => '69992374662',
            'active' => true,
            'status' => 'APPROVED',
            'risk' => 'LOW',
            'category' => 'Lojas de Motocicletas e Acessórios',
            'revenue' => 1000000.00,
            'address_json' => [
                'street' => 'MIGUEL CALMON',
                'number' => '123',
                'neighborhood' => 'CALADINHO',
                'city' => 'Porto Velho',
                'state' => 'RO',
                'zip_code' => '76808126',
                'complement' => 'Sala 2',
            ],
            'responsible_json' => [
                'name' => 'REGINALDO EDUARDO DA SILVA PEREIRA',
            ],
        ]);

        PaytimeTransaction::create([
            'external_id' => 'tx-1',
            'establishment_id' => 9001,
            'type' => 'CREDIT',
            'status' => 'PAID',
            'amount' => 12345,
            'original_amount' => 12345,
            'fees' => 0,
            'installments' => 1,
            'customer_name' => 'Cliente Exemplo',
            'customer_document' => '12345678900',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/spa/estabelecimentos/9001');

        $response
            ->assertOk()
            ->assertJsonPath('establishment.id', 9001)
            ->assertJsonPath('establishment.display_name', 'Enok Motos')
            ->assertJsonPath('establishment.status', 'APPROVED')
            ->assertJsonPath('establishment.status_label', 'Ativo')
            ->assertJsonPath('establishment.risk', 'LOW')
            ->assertJsonPath('establishment.risk_label', 'Baixo')
            ->assertJsonPath('establishment.address.city', 'Porto Velho')
            ->assertJsonPath('recent_transactions.0.type', 'Crédito')
            ->assertJsonPath('recent_transactions.0.status', 'Pago');
    }

    public function test_it_forbids_non_admins_from_viewing_establishment_details(): void
    {
        $vendor = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        PaytimeEstablishment::create([
            'id' => 9002,
            'type' => 'ACQUIRER',
            'document' => '00000000000199',
            'fantasy_name' => 'Loja Restrita',
            'email' => 'loja@example.com',
            'phone_number' => '69999999999',
            'active' => true,
            'status' => 'APPROVED',
            'risk' => 'LOW',
            'revenue' => 1000.00,
        ]);

        $response = $this->actingAs($vendor)->getJson('/api/spa/estabelecimentos/9002');

        $response->assertForbidden();
    }
}

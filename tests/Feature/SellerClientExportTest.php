<?php

namespace Tests\Feature;

use App\Models\PaytimeTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerClientExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_can_download_historical_clients_excel_file(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
        ]);

        PaytimeTransaction::query()->create([
            'external_id' => 'tx-001',
            'establishment_id' => '5001',
            'type' => 'CREDIT',
            'status' => 'PAID',
            'amount' => 12500,
            'original_amount' => 13000,
            'fees' => 500,
            'installments' => 1,
            'customer_name' => 'Maria Silva',
            'customer_document' => '12345678909',
            'created_at' => now()->subMonths(2),
        ]);

        PaytimeTransaction::query()->create([
            'external_id' => 'tx-002',
            'establishment_id' => '5001',
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 7000,
            'original_amount' => 7000,
            'fees' => 0,
            'installments' => 1,
            'customer_name' => 'Maria Silva',
            'customer_document' => '12345678909',
            'created_at' => now()->subDay(),
        ]);

        PaytimeTransaction::query()->create([
            'external_id' => 'tx-003',
            'establishment_id' => '5001',
            'type' => 'DEBIT',
            'status' => 'PAID',
            'amount' => 9000,
            'original_amount' => 9000,
            'fees' => 0,
            'installments' => 1,
            'customer_name' => 'João Pereira',
            'customer_document' => '98765432100',
            'created_at' => now()->subMonths(4),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/seller/clients/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertHeader(
            'content-disposition',
            'attachment; filename="clientes-vendedor-'.now()->format('Y-m-d').'.xls"'
        );
        $response->assertSee('Maria Silva', false);
        $response->assertSee('João Pereira', false);
        $response->assertSee('12345678909', false);
        $response->assertSee('98765432100', false);
        $response->assertSee('2', false);
        $response->assertSee('R$ 195,00', false);
        $response->assertSee('mso-number-format: "\\@"', false);
    }
}

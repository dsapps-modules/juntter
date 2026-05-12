<?php

namespace Tests\Feature;

use App\Models\LinkPagamento;
use App\Models\PaytimeTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaCobrancaPixLinkedTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cobranca_pix_overview_hides_link_when_link_already_has_transaction(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        $linkedPixLink = LinkPagamento::query()->create([
            'estabelecimento_id' => '127700',
            'codigo_unico' => 'link_30a13578',
            'descricao' => 'Link Pix pago',
            'valor' => 1.05,
            'valor_centavos' => 105,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
            'created_at' => Carbon::now()->setTime(12, 0),
            'updated_at' => now(),
        ]);

        LinkPagamento::query()->create([
            'estabelecimento_id' => '127700',
            'codigo_unico' => 'link_pix_aberto',
            'descricao' => 'Link Pix em aberto',
            'valor' => 2.00,
            'valor_centavos' => 200,
            'parcelas' => [1],
            'juros' => 'CLIENT',
            'status' => 'ATIVO',
            'tipo_pagamento' => 'PIX',
            'created_at' => Carbon::now()->setTime(11, 0),
            'updated_at' => now(),
        ]);

        PaytimeTransaction::query()->create([
            'external_id' => '6a035353e77609f30ddd31cf',
            'establishment_id' => '127700',
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 105,
            'original_amount' => 106,
            'fees' => 1,
            'customer_name' => 'Sonia Prado',
            'metadata' => [
                'event' => 'updated-sub-transaction',
                'data' => [
                    'info_additional' => [
                        ['key' => 'link_pagamento_id', 'value' => (string) $linkedPixLink->id],
                        ['key' => 'codigo_unico', 'value' => 'link_30a13578'],
                    ],
                ],
            ],
            'created_at' => Carbon::now()->setTime(13, 0),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca?period='.Carbon::now()->format('Y-m'));

        $response
            ->assertOk()
            ->assertJsonPath('rows.0.customer', 'Sonia Prado')
            ->assertJsonPath('link_rows.0.code', 'link_pix_aberto')
            ->assertJsonCount(1, 'link_rows')
            ->assertJsonMissingPath('link_rows.1');
    }
}

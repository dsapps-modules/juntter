<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Services\EstabelecimentoService;
use App\Services\TransacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SyncPaytimePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_the_establishment_pricing_snapshot_and_contracted_plan_snapshot(): void
    {
        $this->mock(EstabelecimentoService::class, function ($mock): void {
            $mock->shouldReceive('buscarEstabelecimento')
                ->once()
                ->with('5001')
                ->andReturn([
                    'id' => 5001,
                    'type' => 'INDIVIDUAL',
                    'first_name' => 'Isadora',
                    'last_name' => 'Prado',
                    'fantasy_name' => 'Loja Sincronizada',
                    'document' => '40400554895',
                    'email' => 'isadora@example.com',
                    'active' => true,
                    'status' => 'APPROVED',
                    'updated_at' => '2026-07-14T23:45:47.000Z',
                    'plans' => [
                        [
                            'id' => 23025,
                            'active' => true,
                            'modality' => 'ONLINE',
                            'name' => 'Plano Economico D1 Online',
                        ],
                    ],
                    'fees_banking' => [
                        [
                            'fees' => [
                                'pix' => 100,
                            ],
                        ],
                    ],
                ]);
        });

        $this->mock(TransacaoService::class, function ($mock): void {
            $mock->shouldReceive('detalhesPlanoComercial')
                ->once()
                ->with(23025)
                ->andReturn([
                    'id' => 23025,
                    'name' => 'Plano Economico D1 Online',
                    'active' => true,
                    'gateway_id' => 4,
                    'description' => 'Plano Economico Checkout Online nao antecipado D30',
                    'type' => 'COMMERCIAL',
                    'modality' => 'ONLINE',
                    'allow_anticipation' => false,
                    'created_at' => '2026-07-14T23:45:47.000Z',
                    'updated_at' => '2026-07-14T23:45:47.000Z',
                    'categories' => [],
                    'flags' => [
                        [
                            'id' => 1,
                            'name' => 'MASTERCARD',
                            'active' => true,
                            'fees' => [
                                'credit' => [
                                    '1x' => 3.49,
                                    '2x' => 3.75,
                                ],
                            ],
                        ],
                    ],
                ]);
        });

        Artisan::call('paytime:sync-pricing', [
            '--establishment-id' => '5001',
        ]);

        $establishment = PaytimeEstablishment::query()->findOrFail(5001);

        $this->assertSame(100, $establishment->fees_banking_json[0]['fees']['pix']);
        $this->assertSame(23025, $establishment->contracted_plan_json['id']);
        $this->assertSame('Plano Economico D1 Online', $establishment->contracted_plan_json['name']);
        $this->assertSame(3.49, $establishment->contracted_plan_json['flags'][0]['fees']['credit']['1x']);
        $this->assertNotNull($establishment->pricing_synced_at);
        $this->assertNotNull($establishment->contracted_plan_synced_at);
    }
}

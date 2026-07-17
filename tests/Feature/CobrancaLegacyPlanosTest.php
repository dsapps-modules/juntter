<?php

namespace Tests\Feature;

use App\Http\Controllers\CobrancaController;
use App\Models\PaytimeEstablishment;
use App\Models\User;
use App\Services\BoletoService;
use App\Services\CreditoService;
use App\Services\EstabelecimentoService;
use App\Services\PaytimePricingCacheService;
use App\Services\PixService;
use App\Services\TransacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CobrancaLegacyPlanosTest extends TestCase
{
    use RefreshDatabase;

    public function test_listar_planos_uses_the_cached_contracted_plan_snapshot(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        PaytimeEstablishment::query()->create([
            'id' => 5001,
            'type' => 'INDIVIDUAL',
            'first_name' => 'Isadora',
            'last_name' => 'Prado',
            'fantasy_name' => 'Loja Cache',
            'document' => '40400554895',
            'email' => 'isadora@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'plans_json' => [
                [
                    'id' => 23025,
                    'active' => true,
                    'modality' => 'ONLINE',
                ],
            ],
            'contracted_plan_json' => [
                'id' => 23025,
                'name' => 'Plano Economico D1 Online',
                'description' => 'Plano Economico Checkout Online nao antecipado D30',
                'gateway_id' => 4,
                'type' => 'COMMERCIAL',
                'modality' => 'ONLINE',
                'active' => true,
                'allow_anticipation' => false,
                'created_at' => '2026-07-14T23:45:47.000Z',
                'updated_at' => '2026-07-14T23:45:47.000Z',
                'categories' => [],
                'flags' => [
                    [
                        'id' => 1,
                        'name' => 'MASTERCARD',
                        'active' => true,
                        'standard' => [
                            'credit' => [
                                '1x' => 2.28,
                            ],
                        ],
                        'markup' => [
                            'credit' => [
                                '1x' => 1.21,
                            ],
                        ],
                        'fees' => [
                            'credit' => [
                                '1x' => 3.49,
                                '2x' => 3.75,
                            ],
                        ],
                    ],
                ],
            ],
            'contracted_plan_snapshot_hash' => sha1('cached-plan'),
            'contracted_plan_synced_at' => now(),
            'pricing_snapshot_json' => [
                'plans' => [
                    [
                        'id' => 23025,
                        'active' => true,
                        'modality' => 'ONLINE',
                    ],
                ],
            ],
            'pricing_snapshot_hash' => sha1('cached-pricing'),
            'pricing_synced_at' => now(),
        ]);

        $this->mock(EstabelecimentoService::class, function ($mock): void {
            $mock->shouldNotReceive('buscarEstabelecimento');
        });

        $this->mock(TransacaoService::class, function ($mock): void {
            $mock->shouldNotReceive('detalhesPlanoComercial');
        });

        $controller = $this->makeLegacyController();

        $this->actingAs($user);
        $result = $controller->listarPlanos(Request::create('/cobranca/planos', 'GET'));

        $this->assertSame('cobranca.planos', $result->name());
        $this->assertSame('Plano Economico D1 Online', $result->getData()['planoContratado']['name']);
        $this->assertSame(3.49, $result->getData()['planoContratado']['flags'][0]['fees']['credit']['1x']);
    }

    public function test_detalhes_plano_uses_the_cached_contracted_plan_snapshot(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        PaytimeEstablishment::query()->create([
            'id' => 5001,
            'type' => 'INDIVIDUAL',
            'first_name' => 'Isadora',
            'last_name' => 'Prado',
            'fantasy_name' => 'Loja Cache',
            'document' => '40400554895',
            'email' => 'isadora@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'contracted_plan_json' => [
                'id' => 23025,
                'name' => 'Plano Economico D1 Online',
                'description' => 'Plano Economico Checkout Online nao antecipado D30',
                'gateway_id' => 4,
                'type' => 'COMMERCIAL',
                'modality' => 'ONLINE',
                'active' => true,
                'allow_anticipation' => false,
                'created_at' => '2026-07-14T23:45:47.000Z',
                'updated_at' => '2026-07-14T23:45:47.000Z',
                'categories' => [],
                'flags' => [
                    [
                        'id' => 1,
                        'name' => 'MASTERCARD',
                        'active' => true,
                        'standard' => [
                            'credit' => [
                                '1x' => 2.28,
                            ],
                        ],
                        'markup' => [
                            'credit' => [
                                '1x' => 1.21,
                            ],
                        ],
                        'fees' => [
                            'credit' => [
                                '1x' => 3.49,
                                '2x' => 3.75,
                            ],
                        ],
                    ],
                ],
            ],
            'contracted_plan_snapshot_hash' => sha1('cached-plan'),
            'contracted_plan_synced_at' => now(),
        ]);

        $this->mock(EstabelecimentoService::class, function ($mock): void {
            $mock->shouldNotReceive('buscarEstabelecimento');
        });

        $this->mock(TransacaoService::class, function ($mock): void {
            $mock->shouldNotReceive('detalhesPlanoComercial');
        });

        $controller = $this->makeLegacyController();

        $this->actingAs($user);
        $result = $controller->detalhesPlano(23025);

        $this->assertSame('cobranca.plano-detalhes', $result->name());
        $this->assertSame('Plano Economico D1 Online', $result->getData()['plano']['name']);
        $this->assertSame('1x', $result->getData()['plano']['flags'][0]['parcelas_compactadas'][0]['parcela']);
        $this->assertSame(3.49, $result->getData()['plano']['flags'][0]['parcelas_compactadas'][0]['taxa']);
    }

    public function test_mostrar_simulacao_uses_the_cached_flags_for_the_form_options(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        PaytimeEstablishment::query()->create([
            'id' => 5001,
            'type' => 'INDIVIDUAL',
            'first_name' => 'Isadora',
            'last_name' => 'Prado',
            'fantasy_name' => 'Loja Simulação',
            'document' => '40400554895',
            'email' => 'isadora@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'contracted_plan_json' => [
                'id' => 23025,
                'name' => 'Plano Economico D1 Online',
                'modality' => 'ONLINE',
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
                    [
                        'id' => 8,
                        'name' => 'BACEN',
                        'active' => true,
                        'fees' => [
                            'credit' => [
                                '1x' => 0,
                            ],
                        ],
                    ],
                ],
            ],
            'contracted_plan_snapshot_hash' => sha1('cached-plan'),
            'contracted_plan_synced_at' => now(),
        ]);

        $this->mock(TransacaoService::class, function ($mock): void {
            $mock->shouldNotReceive('simularTransacao');
            $mock->shouldNotReceive('detalhesPlanoComercial');
        });

        $controller = $this->makeLegacyController();

        $this->actingAs($user);
        $view = $controller->mostrarSimulacao();

        $this->assertSame('cobranca.simular', $view->name());
        $this->assertSame('Plano Economico D1 Online', $view->getData()['planoContratado']['name']);
        $this->assertCount(1, $view->getData()['simulationFlags']);
        $this->assertSame('MASTERCARD', $view->getData()['simulationFlags'][0]['name']);
        $this->assertSame('Mastercard', $view->getData()['simulationFlagOptions'][0]['label']);
    }

    public function test_simular_transacao_rejects_flag_ids_not_present_in_the_cached_snapshot(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '5001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        PaytimeEstablishment::query()->create([
            'id' => 5001,
            'type' => 'INDIVIDUAL',
            'first_name' => 'Isadora',
            'last_name' => 'Prado',
            'fantasy_name' => 'Loja Simulação',
            'document' => '40400554895',
            'email' => 'isadora@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'contracted_plan_json' => [
                'id' => 23025,
                'name' => 'Plano Economico D1 Online',
                'modality' => 'ONLINE',
                'flags' => [
                    [
                        'id' => 1,
                        'name' => 'MASTERCARD',
                        'active' => true,
                        'fees' => [
                            'credit' => [
                                '1x' => 3.49,
                            ],
                        ],
                    ],
                ],
            ],
            'contracted_plan_snapshot_hash' => sha1('cached-plan'),
            'contracted_plan_synced_at' => now(),
        ]);

        $this->mock(TransacaoService::class, function ($mock): void {
            $mock->shouldNotReceive('simularTransacao');
        });

        $controller = $this->makeLegacyController();

        $this->actingAs($user);
        $response = $controller->simularTransacao(Request::create('/cobranca/simular', 'POST', [
            'amount' => 'R$ 10,00',
            'flag_id' => 9999,
            'interest' => 'CLIENT',
        ]));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/cobranca/simular', $response->headers->get('Location'));
    }

    private function makeLegacyController(): CobrancaController
    {
        return new CobrancaController(
            $this->app->make(TransacaoService::class),
            $this->app->make(CreditoService::class),
            $this->app->make(PixService::class),
            $this->app->make(BoletoService::class),
            $this->app->make(EstabelecimentoService::class),
            $this->app->make(PaytimePricingCacheService::class),
        );
    }
}

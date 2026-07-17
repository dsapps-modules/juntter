<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EstabelecimentoService;
use App\Services\TransacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SpaCobrancaPlanosTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_spa_plan_page_is_available(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->get('/app/cobranca/planos');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_spa_plan_detail_route_is_available(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->get('/app/cobranca/planos/123');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_spa_plan_page_contains_the_new_content_sections(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaPlanoContratadoPage.jsx'));

        $this->assertStringContainsString('Resumo do plano', $pageSource);
        $this->assertStringContainsString('Informações do plano', $pageSource);
        $this->assertStringNotContainsString('Resumo do plano comercial ativo da empresa na interface SPA.', $pageSource);
        $this->assertStringContainsString("navigate('/home')", $pageSource);
        $this->assertStringContainsString('HomeOutlined', $pageSource);
        $this->assertStringNotContainsString('Ver detalhes', $pageSource);
        $this->assertStringNotContainsString('Voltar', $pageSource);
        $this->assertStringNotContainsString('Atalhos', $pageSource);
        $this->assertStringNotContainsString('Pontos de atenção', $pageSource);
        $this->assertStringContainsString('Nenhum plano localizado', $pageSource);
        $this->assertStringNotContainsString('ComingSoonPage', $pageSource);
    }

    public function test_the_plan_overview_api_returns_the_contracted_plan(): void
    {
        Log::spy();

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

        $this->mock(EstabelecimentoService::class, function ($mock): void {
            $mock->shouldReceive('buscarEstabelecimento')
                ->once()
                ->with('5001')
                ->andReturn([
                    'fantasy_name' => 'Loja Teste',
                    'plans' => [
                        [
                            'id' => 66,
                            'active' => true,
                            'modality' => 'OFFLINE',
                        ],
                        [
                            'id' => 77,
                            'active' => true,
                            'modality' => 'ONLINE',
                        ],
                    ],
                ]);
        });

        $this->mock(TransacaoService::class, function ($mock): void {
            $mock->shouldReceive('detalhesPlanoComercial')
                ->once()
                ->with(77)
                ->andReturn([
                    'id' => 77,
                    'name' => 'Plano Flex',
                    'description' => 'Plano comercial com antecipação.',
                    'gateway_id' => 4,
                    'type' => 'Comercial',
                    'modality' => 'ONLINE',
                    'active' => true,
                    'allow_anticipation' => true,
                    'created_at' => '2026-05-01 10:30:00',
                ]);
        });

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/planos');

        $response
            ->assertOk()
            ->assertJsonPath('establishment.name', 'Loja Teste')
            ->assertJsonPath('establishment.plans_count', 1)
            ->assertJsonPath('plan.id', 77)
            ->assertJsonPath('plan.name', 'Plano Flex')
            ->assertJsonPath('plan.modality_label', 'Online')
            ->assertJsonPath('plan.allow_anticipation_label', 'Sim')
            ->assertJsonPath('actions.0.href', '/cobranca')
            ->assertJsonPath('actions.1.href', '/cobranca/planos/77');

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) use ($user): bool {
                return $message === 'Requisição recebida em /api/spa/cobranca/planos'
                    && $context['user_id'] === $user->id
                    && $context['plano_id'] === null
                    && $context['estabelecimento_id'] === '5001';
            })
            ->once();

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context): bool {
                return $message === 'Dados resolvidos em /api/spa/cobranca/planos'
                    && $context['plans_count'] === 1
                    && $context['selected_plan_id'] === 77
                    && $context['plan_id'] === 77;
            })
            ->once();
    }

    public function test_the_plan_overview_api_ignores_offline_plans_when_only_offline_plans_exist(): void
    {
        Log::spy();

        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '6001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        $this->mock(EstabelecimentoService::class, function ($mock): void {
            $mock->shouldReceive('buscarEstabelecimento')
                ->once()
                ->with('6001')
                ->andReturn([
                    'fantasy_name' => 'Loja Offline',
                    'plans' => [
                        [
                            'id' => 90,
                            'active' => true,
                            'modality' => 'OFFLINE',
                        ],
                    ],
                ]);
        });

        $this->mock(TransacaoService::class, function ($mock): void {
            $mock->shouldNotReceive('detalhesPlanoComercial');
        });

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/planos');

        $response
            ->assertOk()
            ->assertJsonPath('plan', null)
            ->assertJsonPath('establishment.name', 'Loja Offline')
            ->assertJsonPath('establishment.plans_count', 0)
            ->assertJsonPath('establishment.has_active_plan', false)
            ->assertJsonPath('actions.0.href', '/cobranca');

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context): bool {
                return $message === 'Dados resolvidos em /api/spa/cobranca/planos'
                    && $context['plans_count'] === 0
                    && $context['selected_plan_id'] === null
                    && $context['plan_id'] === null;
            })
            ->once();
    }

    public function test_the_plan_overview_api_logs_the_request_for_establishment_19103(): void
    {
        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $user->vendedor()->create([
            'estabelecimento_id' => '19103',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        $this->mock(EstabelecimentoService::class, function ($mock): void {
            $mock->shouldReceive('buscarEstabelecimento')
                ->once()
                ->with('19103')
                ->andReturn([
                    'fantasy_name' => 'Loja 19103',
                    'plans' => [
                        [
                            'id' => 88,
                            'active' => true,
                            'modality' => 'ONLINE',
                        ],
                    ],
                ]);
        });

        $this->mock(TransacaoService::class, function ($mock): void {
            $mock->shouldReceive('detalhesPlanoComercial')
                ->once()
                ->with(88)
                ->andReturn([
                    'id' => 88,
                    'name' => 'Plano Online',
                    'description' => 'Plano com modalidade online.',
                    'gateway_id' => 4,
                    'type' => 'Comercial',
                    'modality' => 'ONLINE',
                    'active' => true,
                    'allow_anticipation' => false,
                    'created_at' => '2026-05-02 11:00:00',
                ]);
        });

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/planos');

        Log::info('Resposta da solicitação em /api/spa/cobranca/planos', [
            'estabelecimento_id' => '19103',
            'response' => $response->json(),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('establishment.id', '19103')
            ->assertJsonPath('establishment.name', 'Loja 19103')
            ->assertJsonPath('plan.id', 88)
            ->assertJsonPath('plan.modality', 'ONLINE');
    }

    public function test_the_plan_overview_api_returns_an_empty_state_when_the_user_has_no_establishment(): void
    {
        Log::spy();

        $user = User::factory()->create([
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/cobranca/planos');

        $response
            ->assertOk()
            ->assertJsonPath('plan', null)
            ->assertJsonPath('establishment', null)
            ->assertJsonPath('actions.0.href', '/cobranca');

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) use ($user): bool {
                return $message === 'Requisição recebida em /api/spa/cobranca/planos'
                    && $context['user_id'] === $user->id
                    && $context['plano_id'] === null
                    && $context['estabelecimento_id'] === null;
            })
            ->once();
    }
}

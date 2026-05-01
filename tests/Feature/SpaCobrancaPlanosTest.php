<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EstabelecimentoService;
use App\Services\TransacaoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaCobrancaPlanosTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_spa_plan_page_is_available(): void
    {
        $response = $this->get('/app/cobranca/planos');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_spa_plan_detail_route_is_available(): void
    {
        $response = $this->get('/app/cobranca/planos/123');

        $response->assertOk();
        $response->assertSee('id="app"', false);
    }

    public function test_the_spa_plan_page_contains_the_new_content_sections(): void
    {
        $pageSource = file_get_contents(base_path('resources/js/spa/pages/cobranca/CobrancaPlanoContratadoPage.jsx'));

        $this->assertStringContainsString('Plano contratado', $pageSource);
        $this->assertStringContainsString('Resumo do plano', $pageSource);
        $this->assertStringContainsString('Informações do plano', $pageSource);
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
                            'id' => 77,
                            'active' => true,
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
    }

    public function test_the_plan_overview_api_returns_an_empty_state_when_the_user_has_no_establishment(): void
    {
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
    }
}

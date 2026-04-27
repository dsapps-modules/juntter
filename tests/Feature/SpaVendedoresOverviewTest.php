<?php

namespace Tests\Feature;

use App\Models\PaytimeEstablishment;
use App\Models\PaytimeTransaction;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaVendedoresOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedores_overview_returns_vendor_data(): void
    {
        $user = User::factory()->create([
            'name' => 'João Vendedor',
            'email' => 'joao@example.com',
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        PaytimeEstablishment::create([
            'id' => 9001,
            'fantasy_name' => 'Loja Teste',
            'email' => 'loja@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 4520.20,
        ]);

        Vendedor::create([
            'user_id' => $user->id,
            'estabelecimento_id' => '9001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'comissao' => 4.5,
            'meta_vendas' => 10000,
            'must_change_password' => true,
        ]);

        PaytimeTransaction::create([
            'external_id' => 'trx-v1',
            'establishment_id' => '9001',
            'type' => 'PIX',
            'status' => 'PAID',
            'amount' => 20000,
            'original_amount' => 20000,
            'fees' => 0,
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/vendedores');

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_vendors', 1)
            ->assertJsonPath('summary.active_vendors', 1)
            ->assertJsonPath('rows.0.name', 'João Vendedor')
            ->assertJsonPath('rows.0.establishment', 'Loja Teste');
    }

    public function test_vendedores_overview_formats_soft_deleted_establishment_data_as_document_and_city_state(): void
    {
        $user = User::factory()->create([
            'name' => 'Herika Wanessa Nóbrega de Araujo Lima',
            'email' => 'herika.lima.bee1@gmail.com',
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        $establishment = PaytimeEstablishment::create([
            'id' => 155161,
            'fantasy_name' => 'DS Aplicativos',
            'document' => '05851325690',
            'email' => 'ds@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 1000.00,
            'address_json' => [
                'city' => 'Junco do Seridó',
                'state' => 'PB',
            ],
        ]);

        $establishment->delete();

        Vendedor::create([
            'user_id' => $user->id,
            'estabelecimento_id' => '155161',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/api/spa/vendedores');

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_vendors', 1)
            ->assertJsonPath('rows.0.document', '05851325690')
            ->assertJsonPath('rows.0.location', 'Junco do Seridó - PB');
    }

    public function test_vendedores_overview_quick_view_does_not_render_revenue_and_active_tasks_stats(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/spa/pages/EstablishmentsPage.jsx'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString('spa-toolbar-top-row', $componentSource);
        $this->assertStringContainsString('spa-toolbar-metric', $componentSource);
        $this->assertStringContainsString('spa-toolbar-filter', $componentSource);
        $this->assertStringContainsString('spa-toolbar-search', $componentSource);
        $this->assertStringNotContainsString('title="Receita"', $componentSource);
        $this->assertStringNotContainsString('Statistic title="Revenue"', $componentSource);
        $this->assertStringNotContainsString('Statistic title="Active Tasks"', $componentSource);
    }

    public function test_vendedores_overview_paginates_results_in_pages_of_twenty(): void
    {
        $admin = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        foreach (range(1, 25) as $index) {
            $user = User::factory()->create([
                'name' => "Vendedor {$index}",
                'email' => "vendedor{$index}@example.com",
                'nivel_acesso' => 'vendedor',
                'email_verified_at' => now(),
            ]);

            PaytimeEstablishment::create([
                'id' => 8100 + $index,
                'fantasy_name' => "Loja {$index}",
                'email' => "loja{$index}@example.com",
                'active' => true,
                'status' => 'APPROVED',
                'revenue' => 1000,
            ]);

            Vendedor::create([
                'user_id' => $user->id,
                'estabelecimento_id' => (string) (8100 + $index),
                'sub_nivel' => $index % 2 === 0 ? 'admin_loja' : 'vendedor_loja',
                'status' => $index % 2 === 0 ? 'ativo' : 'inativo',
                'must_change_password' => $index === 1,
            ]);
        }

        $response = $this->actingAs($admin)->getJson('/api/spa/vendedores');

        $response
            ->assertOk()
            ->assertJsonPath('summary.total_vendors', 25)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 20)
            ->assertJsonPath('pagination.total', 25);

        $secondPageResponse = $this->actingAs($admin)->getJson('/api/spa/vendedores?page=2');

        $secondPageResponse
            ->assertOk()
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonCount(5, 'rows');
    }

    public function test_vendedores_overview_searches_across_all_vendors(): void
    {
        $admin = User::factory()->create([
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);

        foreach (range(1, 25) as $index) {
            $user = User::factory()->create([
                'name' => "Vendedor {$index}",
                'email' => "vendedor{$index}@example.com",
                'nivel_acesso' => 'vendedor',
                'email_verified_at' => now(),
            ]);

            PaytimeEstablishment::create([
                'id' => 8200 + $index,
                'fantasy_name' => "Loja {$index}",
                'email' => "loja{$index}@example.com",
                'active' => true,
                'status' => 'APPROVED',
                'revenue' => 1000,
            ]);

            Vendedor::create([
                'user_id' => $user->id,
                'estabelecimento_id' => (string) (8200 + $index),
                'sub_nivel' => 'vendedor_loja',
                'status' => 'ativo',
            ]);
        }

        $response = $this->actingAs($admin)->getJson('/api/spa/vendedores?search=Vendedor%2025');

        $response
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('rows.0.name', 'Vendedor 25');
    }
}

<?php

namespace Tests\Browser;

use App\Models\PaytimeEstablishment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SpaVendedoresAcessoTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function createAdmin(string $name = 'Admin Test'): User
    {
        return User::create([
            'name' => $name,
            'email' => 'admin.acesso@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function test_the_access_table_uses_icons_without_redundant_labels(): void
    {
        $user = $this->createAdmin('Admin Teste Muito Muito Longo Para Sidebar');

        PaytimeEstablishment::create([
            'id' => 9001,
            'fantasy_name' => 'Loja Teste',
            'email' => 'loja@example.com',
            'active' => true,
            'status' => 'APPROVED',
            'revenue' => 1000.00,
        ]);

        User::create([
            'name' => 'Joao Vendedor',
            'email' => 'joao.vendedor@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ])->vendedor()->create([
            'estabelecimento_id' => '9001',
            'sub_nivel' => 'admin_loja',
            'status' => 'ativo',
        ]);

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/app/vendedores/acesso')
                ->waitForText('Vendedores com acesso: 1', 10)
                ->waitFor('.spa-brand', 10)
                ->assertSeeIn('.spa-brand .ant-avatar', 'AT')
                ->assertAttribute('.spa-brand-name', 'title', 'Admin Teste Muito Muito Longo Para Sidebar')
                ->assertSeeIn('.spa-brand-kicker', 'Admin')
                ->waitFor('.spa-table-card .ant-table', 10)
                ->with('.spa-table-card', function (Browser $table): void {
                    $table->assertSee('Joao Vendedor')
                        ->assertSee('joao.vendedor@test.com')
                        ->assertDontSee('Gestão de acesso')
                        ->assertDontSee('Perfil')
                        ->assertDontSee('Status')
                        ->assertDontSee('Estabelecimento')
                        ->assertDontSee('Ações')
                        ->assertDontSee('Editar')
                        ->assertDontSee('Senha')
                        ->assertMissing('.spa-row-avatar')
                        ->assertPresent('button[aria-label="Editar acesso"]')
                        ->assertPresent('button[aria-label="Alterar senha"]');
                });

            $browser->with('.spa-quick-view-card', function (Browser $card): void {
                $card->assertSee('Joao Vendedor')
                    ->assertSee('Comissão')
                    ->assertSee('Admin de loja')
                    ->assertDontSee('â€¢')
                    ->assertDontSee('Quick View:');
            });

            $browser->with('.spa-quick-link-item', function (Browser $item): void {
                $item->click();
            });

            $browser->with('.spa-quick-view-card', function (Browser $card): void {
                $card->assertSee('Joao Vendedor');
            });
        });
    }
}

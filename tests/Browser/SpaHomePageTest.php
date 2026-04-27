<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SpaHomePageTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin Test',
            'email' => 'admin.home@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    private function createVendor(): User
    {
        $user = User::create([
            'name' => 'Vendedor Test',
            'email' => 'vendor.home@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);

        Vendedor::create([
            'user_id' => $user->id,
            'estabelecimento_id' => '155161',
            'sub_nivel' => 'admin_loja',
            'comissao' => 5.00,
            'meta_vendas' => 10000.00,
            'telefone' => '(11) 99999-9999',
            'endereco' => 'Rua Teste, 123',
            'status' => 'ativo',
        ]);

        return $user;
    }

    public function test_admin_home_page_hides_bank_account_link_and_filter_button(): void
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/app/home')
                ->waitForText('Visão geral de transações', 10)
                ->assertPathIs('/app/home')
                ->assertDontSee('Acessar Conta Bancária')
                ->assertMissing('.spa-toolbar-filter-button');
        });
    }

    public function test_vendor_home_page_still_shows_bank_account_link(): void
    {
        $user = $this->createVendor();

        $this->browse(function (Browser $browser) use ($user): void {
            $browser->loginAs($user)
                ->visit('/app/home')
                ->waitForText('Visão geral de transações', 10)
                ->assertPathIs('/app/home')
                ->assertSee('Acessar Conta Bancária');
        });
    }
}

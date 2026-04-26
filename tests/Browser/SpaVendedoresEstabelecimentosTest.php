<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SpaVendedoresEstabelecimentosTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin Test',
            'email' => 'admin.estabelecimentos@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function test_o_menu_vendedores_exibe_estabelecimentos_no_spa(): void
    {
        $user = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/app/vendedores')
                ->waitForText('Estabelecimentos', 10)
                ->assertPathIs('/app/vendedores')
                ->assertSee('Estabelecimentos')
                ->assertSee('Nenhum estabelecimento encontrado');
        });
    }
}

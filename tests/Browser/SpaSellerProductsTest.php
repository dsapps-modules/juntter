<?php

namespace Tests\Browser;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SpaSellerProductsTest extends DuskTestCase
{
    use DatabaseMigrations;

    private function createSeller(): User
    {
        return User::create([
            'name' => 'Seller Test',
            'email' => 'seller.products@test.com',
            'password' => Hash::make('senha123456'),
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);
    }

    public function test_o_formulario_de_edicao_exibe_preco_com_mascara_brl(): void
    {
        $seller = $this->createSeller();

        $product = Product::create([
            'seller_id' => $seller->id,
            'name' => 'Produto Teste',
            'slug' => Str::slug('Produto Teste'),
            'description' => 'Descrição do produto de teste.',
            'short_description' => 'Resumo do produto.',
            'sku' => 'SKU-123',
            'image_path' => null,
            'price' => 1234.56,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($product, $seller): void {
            $browser->loginAs($seller)
                ->visit('/app/seller/products/'.$product->id.'/editar')
                ->waitFor('[aria-label="Preço do produto"]', 10)
                ->assertPathIs('/app/seller/products/'.$product->id.'/editar');

            $labels = $browser->script('return Array.from(document.querySelectorAll(".product-form-inline-row .ant-form-item-label label")).map((label) => label.textContent.trim().replace("*", "").trim());');
            $columnCount = $browser->script('return document.querySelectorAll(".product-form-inline-row > div").length;');

            self::assertSame(['Preço', 'SKU', 'Status'], $labels[0] ?? []);
            self::assertSame(3, $columnCount[0] ?? 0);

            $browser->assertInputValue('[aria-label="Preço do produto"]', 'R$ 1.234,56');
        });
    }
}

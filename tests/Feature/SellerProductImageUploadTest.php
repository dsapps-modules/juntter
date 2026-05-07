<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SellerProductImageUploadTest extends TestCase
{
    use DatabaseMigrations;

    private function createSeller(): User
    {
        return User::create([
            'name' => 'Seller Test',
            'email' => 'seller.image@test.com',
            'password' => 'senha123456',
            'nivel_acesso' => 'vendedor',
            'email_verified_at' => now(),
        ]);
    }

    public function test_store_uploads_a_png_image_into_public_storage(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $response = $this->actingAs($seller)->post('/seller/products', [
            'name' => 'Produto com imagem',
            'description' => 'Descrição',
            'short_description' => 'Resumo',
            'sku' => 'SKU-001',
            'image' => UploadedFile::fake()->image('produto.png'),
            'price' => 123.45,
            'status' => 'active',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertCreated();
        $storedSlug = $response->json('product.slug');
        $storedImagePath = $response->json('product.image_path');

        $this->assertSame('produto-com-imagem', $storedSlug);
        $this->assertIsString($storedImagePath);
        $this->assertTrue(Str::startsWith($storedImagePath, 'products/'));

        $product = Product::query()->first();

        $this->assertNotNull($product);
        $this->assertNotEmpty($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_update_replaces_previous_image_and_deletes_old_file(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $initialUpload = UploadedFile::fake()->image('produto-original.png');
        $initialPath = $initialUpload->store('products', 'public');

        $product = Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Produto Teste',
            'slug' => Str::slug('Produto Teste'),
            'description' => 'Descrição inicial',
            'short_description' => 'Resumo inicial',
            'sku' => 'SKU-123',
            'image_path' => $initialPath,
            'price' => 99.90,
            'status' => 'active',
        ]);

        $response = $this->actingAs($seller)->post('/seller/products/'.$product->id, [
            'name' => 'Produto Teste Atualizado',
            'description' => 'Descrição atualizada',
            'short_description' => 'Resumo atualizado',
            'sku' => 'SKU-456',
            'image' => UploadedFile::fake()->image('produto-novo.jpg'),
            'price' => 149.90,
            'status' => 'inactive',
            '_method' => 'PUT',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();

        $product->refresh();

        $this->assertSame('produto-teste-atualizado', $product->slug);
        $this->assertNotSame($initialPath, $product->image_path);
        Storage::disk('public')->assertMissing($initialPath);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertSame('inactive', $product->status);
    }

    public function test_store_rejects_files_that_are_not_jpg_or_png(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $response = $this->actingAs($seller)->post('/seller/products', [
            'name' => 'Produto inválido',
            'description' => 'Descrição',
            'short_description' => 'Resumo',
            'sku' => 'SKU-999',
            'image' => UploadedFile::fake()->create('documento.gif', 10, 'image/gif'),
            'price' => 50.00,
            'status' => 'active',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['image']);
    }

    public function test_the_edit_form_uses_a_fixed_square_preview_area(): void
    {
        $componentSource = file_get_contents(base_path('resources/js/spa/pages/checkout/CheckoutProductFormPage.jsx'));

        $this->assertIsString($componentSource);
        $this->assertStringContainsString("aspectRatio: '1 / 1'", $componentSource);
        $this->assertStringContainsString("maxWidth: '260px'", $componentSource);
        $this->assertStringContainsString('mx-auto mt-4 flex items-center justify-center', $componentSource);
        $this->assertStringContainsString('object-contain p-4', $componentSource);
    }
}

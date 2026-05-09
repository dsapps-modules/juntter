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
        $storedProductId = $response->json('product.id');
        $storedImagePath = $response->json('product.image_path');
        $storedImageUrl = $response->json('product.image_url');

        $this->assertSame('produto-com-imagem', $storedSlug);
        $this->assertIsString($storedImagePath);
        $this->assertSame(route('seller.products.image', ['product' => $storedProductId], false), $storedImageUrl);
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

    public function test_product_exposes_a_public_image_url_attribute(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $product = Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Produto Teste',
            'slug' => Str::slug('Produto Teste'),
            'description' => 'DescriÃ§Ã£o',
            'short_description' => 'Resumo',
            'sku' => 'SKU-789',
            'image_path' => 'products/produto-teste.png',
            'price' => 79.90,
            'status' => 'active',
        ]);

        $this->assertSame(route('seller.products.image', $product, false), $product->image_url);
    }

    public function test_the_products_index_view_renders_image_thumbnails(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $product = Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Produto com miniatura',
            'slug' => Str::slug('Produto com miniatura'),
            'description' => 'Descri��o',
            'short_description' => 'Resumo',
            'sku' => 'SKU-555',
            'image_path' => 'products/produto-miniatura.png',
            'price' => 45.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($seller)->get('/seller/products');

        $response->assertOk();
        $response->assertSee('Miniatura de Produto com miniatura', false);
        $response->assertSee(route('seller.products.image', $product, false), false);
    }

    public function test_image_route_serves_the_product_image_file(): void
    {
        Storage::fake('public');

        $seller = $this->createSeller();

        $upload = UploadedFile::fake()->image('produto.png');
        $path = $upload->store('products', 'public');

        $product = Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Produto com imagem',
            'slug' => Str::slug('Produto com imagem'),
            'description' => 'Descrição',
            'short_description' => 'Resumo',
            'sku' => 'SKU-777',
            'image_path' => $path,
            'price' => 45.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($seller)->get(route('seller.products.image', $product, false));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/png');
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
        $this->assertStringContainsString('const [currentImageUrl, setCurrentImageUrl] = useState(\'\');', $componentSource);
        $this->assertStringContainsString('setCurrentImageUrl(data.product.image_url ?? \'\');', $componentSource);
        $this->assertStringContainsString('if (currentImageUrl) {', $componentSource);
        $this->assertStringContainsString('setImagePreviewUrl(getProductImageUrl(currentImagePath));', $componentSource);
    }
}

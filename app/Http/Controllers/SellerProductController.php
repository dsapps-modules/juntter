<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SellerProductController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $products = Product::query()
            ->where('seller_id', $request->user()->id)
            ->latest()
            ->get();

        if ($request->expectsJson()) {
            return response()->json(['products' => $products]);
        }

        return view('seller.products.index', compact('products'));
    }

    public function store(StoreProductRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::query()->create([
            'seller_id' => $request->user()->id,
            'name' => $request->string('name')->toString(),
            'slug' => $this->generateUniqueSlug($request->string('name')->toString()),
            'description' => $request->input('description'),
            'short_description' => $request->input('short_description'),
            'sku' => $request->input('sku'),
            'image_path' => $this->resolveImagePath($request),
            'price' => $request->input('price'),
            'status' => $request->string('status')->toString(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Produto criado com sucesso.',
                'product' => $product,
            ], 201, [], JSON_INVALID_UTF8_SUBSTITUTE);
        }

        return redirect()->route('spa', ['any' => 'seller/products'])
            ->with('success', 'Produto criado com sucesso.');
    }

    public function show(Request $request, Product $product): JsonResponse|View
    {
        $this->authorize('view', $product);

        if ($request->expectsJson()) {
            return response()->json(['product' => $product]);
        }

        return view('seller.products.show', compact('product'));
    }

    public function image(Request $request, Product $product): BinaryFileResponse
    {
        $this->authorize('view', $product);

        abort_unless(filled($product->image_path) && Storage::disk('public')->exists($product->image_path), 404);

        return response()->file(Storage::disk('public')->path($product->image_path));
    }

    public function update(StoreProductRequest $request, Product $product): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $product);

        $product->update([
            'name' => $request->string('name')->toString(),
            'slug' => $this->generateUniqueSlug($request->string('name')->toString(), $product->id),
            'description' => $request->input('description'),
            'short_description' => $request->input('short_description'),
            'sku' => $request->input('sku'),
            'image_path' => $this->resolveImagePath($request, $product),
            'price' => $request->input('price'),
            'status' => $request->string('status')->toString(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Produto atualizado com sucesso.',
                'product' => $product->fresh(),
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
        }

        return redirect()->route('spa', ['any' => 'seller/products'])
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Produto excluído com sucesso.',
            ]);
        }

        return redirect()->route('spa', ['any' => 'seller/products'])
            ->with('success', 'Produto excluído com sucesso.');
    }

    private function generateUniqueSlug(string $baseName, ?int $ignoreId = null): string
    {
        $slug = Str::slug($baseName);
        $candidate = $slug;
        $counter = 1;

        while (Product::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $slug.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function resolveImagePath(StoreProductRequest $request, ?Product $product = null): ?string
    {
        if ($request->hasFile('image')) {
            if ($product !== null && filled($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }

            return $request->file('image')->store('products', 'public');
        }

        $imagePath = $request->input('image_path');

        if (is_string($imagePath) && $imagePath !== '') {
            return $imagePath;
        }

        return $product?->image_path;
    }
}

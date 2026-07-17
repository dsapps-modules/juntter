<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutShippingOptionRequest;
use App\Models\CheckoutShippingOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SellerCheckoutShippingController extends Controller
{
    public function index(Request $request): JsonResponse|RedirectResponse
    {
        $shippingOptions = $this->resolveShippingOptions($request->user()->id);

        if ($request->expectsJson()) {
            return response()->json([
                'shipping_options' => $shippingOptions,
            ]);
        }

        return redirect()->route('spa', ['any' => 'seller/checkout-links/frete']);
    }

    public function store(CheckoutShippingOptionRequest $request): JsonResponse|RedirectResponse
    {
        $shippingOption = CheckoutShippingOption::query()->create([
            'seller_id' => $request->user()->id,
            'name' => $request->string('name')->toString(),
            'price' => $request->input('price'),
            'eta_days' => $request->input('eta_days'),
            'is_default' => $request->boolean('is_default') || ! CheckoutShippingOption::query()->where('seller_id', $request->user()->id)->exists(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->applyDefaultShippingOption($shippingOption);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Frete salvo com sucesso.',
                'shipping_option' => $shippingOption->fresh(),
                'shipping_options' => $this->resolveShippingOptions($request->user()->id),
            ], 201);
        }

        return redirect()->route('spa', ['any' => 'seller/checkout-links/frete'])
            ->with('success', 'Frete salvo com sucesso.');
    }

    public function update(CheckoutShippingOptionRequest $request, CheckoutShippingOption $shippingOption): JsonResponse|RedirectResponse
    {
        $this->authorizeShippingOption($request, $shippingOption);

        $shippingOption->update([
            'name' => $request->string('name')->toString(),
            'price' => $request->input('price'),
            'eta_days' => $request->input('eta_days'),
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->applyDefaultShippingOption($shippingOption->fresh());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Frete atualizado com sucesso.',
                'shipping_option' => $shippingOption->fresh(),
                'shipping_options' => $this->resolveShippingOptions($request->user()->id),
            ]);
        }

        return redirect()->route('spa', ['any' => 'seller/checkout-links/frete'])
            ->with('success', 'Frete atualizado com sucesso.');
    }

    public function destroy(Request $request, CheckoutShippingOption $shippingOption): JsonResponse|RedirectResponse
    {
        $this->authorizeShippingOption($request, $shippingOption);

        $sellerId = $shippingOption->seller_id;
        $wasDefault = $shippingOption->is_default;
        $shippingOption->delete();

        if ($wasDefault) {
            $this->ensureDefaultShippingOption($sellerId);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Frete excluído com sucesso.',
                'shipping_options' => $this->resolveShippingOptions($sellerId),
            ]);
        }

        return redirect()->route('spa', ['any' => 'seller/checkout-links/frete'])
            ->with('success', 'Frete excluído com sucesso.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveShippingOptions(int $sellerId): array
    {
        $options = CheckoutShippingOption::query()
            ->where('seller_id', $sellerId)
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        if ($options->isEmpty()) {
            return [[
                'id' => null,
                'seller_id' => $sellerId,
                'name' => 'Frete padrão',
                'price' => 0,
                'eta_days' => 5,
                'is_default' => true,
                'is_active' => true,
            ]];
        }

        return $options->map(static function (CheckoutShippingOption $shippingOption): array {
            return [
                'id' => $shippingOption->id,
                'seller_id' => $shippingOption->seller_id,
                'name' => $shippingOption->name,
                'price' => (float) $shippingOption->price,
                'eta_days' => $shippingOption->eta_days,
                'is_default' => $shippingOption->is_default,
                'is_active' => $shippingOption->is_active,
            ];
        })->all();
    }

    private function applyDefaultShippingOption(CheckoutShippingOption $shippingOption): void
    {
        if ($shippingOption->is_default) {
            CheckoutShippingOption::query()
                ->where('seller_id', $shippingOption->seller_id)
                ->where('id', '!=', $shippingOption->id)
                ->update(['is_default' => false]);
        }

        $this->ensureDefaultShippingOption($shippingOption->seller_id);
    }

    private function ensureDefaultShippingOption(int $sellerId): void
    {
        $defaultOptionExists = CheckoutShippingOption::query()
            ->where('seller_id', $sellerId)
            ->where('is_default', true)
            ->exists();

        if ($defaultOptionExists) {
            return;
        }

        $firstOption = CheckoutShippingOption::query()
            ->where('seller_id', $sellerId)
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();

        if ($firstOption) {
            $firstOption->update(['is_default' => true]);
        }
    }

    private function authorizeShippingOption(Request $request, CheckoutShippingOption $shippingOption): void
    {
        abort_unless($request->user()?->id === $shippingOption->seller_id, 403);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutLinkRequest;
use App\Models\CheckoutLink;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerCheckoutLinkController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $links = CheckoutLink::query()
            ->where('seller_id', $request->user()->id)
            ->with(['product', 'orders'])
            ->latest()
            ->get();

        if ($request->expectsJson()) {
            return response()->json(['checkout_links' => $links]);
        }

        return view('seller.checkout-links.index', compact('links'));
    }

    public function store(StoreCheckoutLinkRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', CheckoutLink::class);

        $product = Product::query()->where('seller_id', $request->user()->id)->findOrFail($request->integer('product_id'));

        $unitPrice = (float) ($request->input('unit_price') ?? $product->price);
        $quantity = max(1, $request->integer('quantity'));

        $checkoutLink = CheckoutLink::query()->create([
            'seller_id' => $request->user()->id,
            'product_id' => $product->id,
            'public_token' => CheckoutLink::generatePublicToken(),
            'name' => $request->string('name')->toString(),
            'status' => $request->string('status')->toString(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($unitPrice * $quantity, 2),
            'allow_pix' => $request->boolean('allow_pix', true),
            'allow_boleto' => $request->boolean('allow_boleto', true),
            'allow_credit_card' => $request->boolean('allow_credit_card', true),
            'pix_discount_type' => $request->input('pix_discount_type', 'none'),
            'pix_discount_value' => $request->input('pix_discount_value', 0),
            'boleto_discount_type' => $request->input('boleto_discount_type', 'none'),
            'boleto_discount_value' => $request->input('boleto_discount_value', 0),
            'free_shipping' => $request->boolean('free_shipping', true),
            'success_url' => $request->input('success_url'),
            'failure_url' => $request->input('failure_url'),
            'expires_at' => $request->input('expires_at'),
            'visual_config' => $request->input('visual_config'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Link de checkout criado com sucesso.',
                'checkout_link' => $checkoutLink,
                'public_url' => route('checkout.public.show', $checkoutLink->public_token),
            ], 201);
        }

        return redirect()->route('spa', ['any' => 'seller/checkout-links'])
            ->with('success', 'Link de checkout criado com sucesso.');
    }

    public function show(Request $request, CheckoutLink $checkoutLink): JsonResponse|View
    {
        $this->authorize('view', $checkoutLink);

        $checkoutLink->load(['product', 'orders']);

        if ($request->expectsJson()) {
            return response()->json(['checkout_link' => $checkoutLink]);
        }

        return view('seller.checkout-links.show', compact('checkoutLink'));
    }

    public function update(StoreCheckoutLinkRequest $request, CheckoutLink $checkoutLink): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $checkoutLink);

        $product = Product::query()->where('seller_id', $request->user()->id)->findOrFail($request->integer('product_id'));
        $unitPrice = (float) ($request->input('unit_price') ?? $product->price);
        $quantity = max(1, $request->integer('quantity'));

        $checkoutLink->update([
            'product_id' => $product->id,
            'name' => $request->string('name')->toString(),
            'status' => $request->string('status')->toString(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => round($unitPrice * $quantity, 2),
            'allow_pix' => $request->boolean('allow_pix', true),
            'allow_boleto' => $request->boolean('allow_boleto', true),
            'allow_credit_card' => $request->boolean('allow_credit_card', true),
            'pix_discount_type' => $request->input('pix_discount_type', 'none'),
            'pix_discount_value' => $request->input('pix_discount_value', 0),
            'boleto_discount_type' => $request->input('boleto_discount_type', 'none'),
            'boleto_discount_value' => $request->input('boleto_discount_value', 0),
            'free_shipping' => $request->boolean('free_shipping', true),
            'success_url' => $request->input('success_url'),
            'failure_url' => $request->input('failure_url'),
            'expires_at' => $request->input('expires_at'),
            'visual_config' => $request->input('visual_config'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Link de checkout atualizado com sucesso.',
                'checkout_link' => $checkoutLink->fresh(),
            ]);
        }

        return redirect()->route('spa', ['any' => 'seller/checkout-links'])
            ->with('success', 'Link de checkout atualizado com sucesso.');
    }

    public function destroy(Request $request, CheckoutLink $checkoutLink): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $checkoutLink);

        $checkoutLink->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Link de checkout excluído com sucesso.',
            ]);
        }

        return redirect()->route('spa', ['any' => 'seller/checkout-links'])
            ->with('success', 'Link de checkout excluído com sucesso.');
    }

    public function activate(Request $request, CheckoutLink $checkoutLink): JsonResponse
    {
        $this->authorize('update', $checkoutLink);

        $checkoutLink->update(['status' => 'active']);

        return response()->json(['message' => 'Link ativado com sucesso.']);
    }

    public function deactivate(Request $request, CheckoutLink $checkoutLink): JsonResponse
    {
        $this->authorize('update', $checkoutLink);

        $checkoutLink->update(['status' => 'inactive']);

        return response()->json(['message' => 'Link desativado com sucesso.']);
    }

    public function sales(Request $request, CheckoutLink $checkoutLink): JsonResponse|View
    {
        $this->authorize('view', $checkoutLink);

        $orders = Order::query()->where('checkout_link_id', $checkoutLink->id)->get();

        if ($request->expectsJson()) {
            return response()->json([
                'orders' => $orders,
                'total_sales' => $orders->where('status', 'paid')->sum('total'),
            ]);
        }

        return view('seller.checkout-links.sales', compact('checkoutLink', 'orders'));
    }
}

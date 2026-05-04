<?php

namespace App\Http\Controllers;

use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCheckoutController extends Controller
{
    public function show(Request $request, string $publicToken): View|\Illuminate\Http\Response|RedirectResponse
    {
        $checkoutLink = CheckoutLink::query()
            ->with(['product', 'seller'])
            ->where('public_token', $publicToken)
            ->first();

        if (! $checkoutLink || ! $checkoutLink->isActive() || ! $checkoutLink->product?->isActive()) {
            return response()->view('checkout.unavailable', [
                'message' => 'Este checkout não está disponível no momento.',
            ], 410);
        }

        $checkoutSession = $this->resolveSession($request, $checkoutLink);
        $order = Order::query()
            ->with(['paymentTransaction'])
            ->where('checkout_session_id', $checkoutSession->id)
            ->latest()
            ->first();

        if ($order?->status === 'paid') {
            return redirect()->route('checkout.public.thank-you', $checkoutSession->session_token);
        }

        return view('checkout.public', [
            'checkoutLink' => $checkoutLink,
            'checkoutSession' => $checkoutSession,
            'order' => $order,
            'paymentTransaction' => $order?->paymentTransaction,
        ]);
    }

    public function thankYou(string $sessionToken): View|\Illuminate\Http\Response
    {
        $checkoutSession = CheckoutSession::query()
            ->with(['orders.product', 'checkoutLink'])
            ->where('session_token', $sessionToken)
            ->firstOrFail();

        $order = $checkoutSession->orders()->latest()->first();

        return view('checkout.thank-you', [
            'checkoutSession' => $checkoutSession,
            'order' => $order,
        ]);
    }

    private function resolveSession(Request $request, CheckoutLink $checkoutLink): CheckoutSession
    {
        $cookieKey = 'checkout_session_token.'.$checkoutLink->public_token;
        $sessionToken = $request->session()->get($cookieKey);

        if (is_string($sessionToken) && $sessionToken !== '') {
            $existingSession = CheckoutSession::query()
                ->where('session_token', $sessionToken)
                ->where('checkout_link_id', $checkoutLink->id)
                ->first();

            if ($existingSession) {
                return $existingSession;
            }
        }

        $checkoutSession = CheckoutSession::query()->create([
            'checkout_link_id' => $checkoutLink->id,
            'seller_id' => $checkoutLink->seller_id,
            'product_id' => $checkoutLink->product_id,
            'session_token' => CheckoutSession::generateSessionToken(),
            'status' => 'started',
            'current_step' => 'identification',
            'subtotal' => $checkoutLink->total_price,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => $checkoutLink->total_price,
            'last_activity_at' => now(),
        ]);

        $request->session()->put($cookieKey, $checkoutSession->session_token);

        return $checkoutSession;
    }
}

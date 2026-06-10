<?php

namespace App\Http\Controllers;

use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicCheckoutController extends Controller
{
    public function show(Request $request, string $publicToken): View|\Illuminate\Http\Response|RedirectResponse
    {
        return $this->renderCheckoutPage($request, $publicToken, 'details');
    }

    public function paymentPage(string $sessionToken): View|\Illuminate\Http\Response|RedirectResponse
    {
        $checkoutSession = CheckoutSession::query()
            ->with(['checkoutLink.product', 'checkoutLink.seller', 'orders.paymentTransaction'])
            ->where('session_token', $sessionToken)
            ->firstOrFail();

        $checkoutLink = $checkoutSession->checkoutLink;

        if (! $checkoutLink || ! $checkoutLink->isActive() || ! $checkoutLink->product?->isActive()) {
            return response()->view('checkout.unavailable', [
                'message' => 'Este checkout não está disponível no momento.',
            ], 410);
        }

        $order = Order::query()
            ->with(['paymentTransaction'])
            ->where('checkout_session_id', $checkoutSession->id)
            ->latest()
            ->first();

        if (
            in_array($order?->status, ['paid', 'authorized'], true)
            || in_array(strtolower((string) ($order?->paymentTransaction?->internal_status ?? '')), ['authorized', 'paid'], true)
        ) {
            return redirect()->route('checkout.public.thank-you', $checkoutSession->session_token);
        }

        if ($checkoutSession->current_step !== 'payment' && blank($checkoutSession->payment_method)) {
            return redirect()->route('checkout.public.show', $checkoutLink->public_token);
        }

        return view('checkout.public', $this->buildCheckoutViewData(
            checkoutLink: $checkoutLink,
            checkoutSession: $checkoutSession,
            order: $order,
            paymentTransaction: $order?->paymentTransaction,
            sellerLogoUrl: $this->resolveSellerLogoUrl($checkoutLink),
            checkoutPageMode: 'payment-selector',
        ));
    }

    public function paymentDetailsPage(string $sessionToken): View|\Illuminate\Http\Response|RedirectResponse
    {
        $checkoutSession = CheckoutSession::query()
            ->with(['checkoutLink.product', 'checkoutLink.seller', 'orders.paymentTransaction'])
            ->where('session_token', $sessionToken)
            ->firstOrFail();

        $checkoutLink = $checkoutSession->checkoutLink;

        if (! $checkoutLink || ! $checkoutLink->isActive() || ! $checkoutLink->product?->isActive()) {
            return response()->view('checkout.unavailable', [
                'message' => 'Este checkout não está disponível no momento.',
            ], 410);
        }

        $order = Order::query()
            ->with(['paymentTransaction'])
            ->where('checkout_session_id', $checkoutSession->id)
            ->latest()
            ->first();

        if (
            in_array($order?->status, ['paid', 'authorized'], true)
            || in_array(strtolower((string) ($order?->paymentTransaction?->internal_status ?? '')), ['authorized', 'paid'], true)
        ) {
            return redirect()->route('checkout.public.thank-you', $checkoutSession->session_token);
        }

        if (blank($checkoutSession->payment_method)) {
            return redirect()->route('checkout.public.payment.page', $checkoutSession->session_token);
        }

        return view('checkout.public', $this->buildCheckoutViewData(
            checkoutLink: $checkoutLink,
            checkoutSession: $checkoutSession,
            order: $order,
            paymentTransaction: $order?->paymentTransaction,
            sellerLogoUrl: $this->resolveSellerLogoUrl($checkoutLink),
            checkoutPageMode: 'payment-details',
        ));
    }

    private function renderCheckoutPage(Request $request, string $publicToken, string $checkoutPageMode): View|\Illuminate\Http\Response|RedirectResponse
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

        if (
            in_array($order?->status, ['paid', 'authorized'], true)
            || in_array(strtolower((string) ($order?->paymentTransaction?->internal_status ?? '')), ['authorized', 'paid'], true)
        ) {
            return redirect()->route('checkout.public.thank-you', $checkoutSession->session_token);
        }

        return view('checkout.public', $this->buildCheckoutViewData(
            checkoutLink: $checkoutLink,
            checkoutSession: $checkoutSession,
            order: $order,
            paymentTransaction: $order?->paymentTransaction,
            sellerLogoUrl: $this->resolveSellerLogoUrl($checkoutLink),
            checkoutPageMode: $checkoutPageMode,
        ));
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
                return $this->syncSessionPricing($existingSession, $checkoutLink);
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

    private function syncSessionPricing(CheckoutSession $checkoutSession, CheckoutLink $checkoutLink): CheckoutSession
    {
        if (Order::query()->where('checkout_session_id', $checkoutSession->id)->exists()) {
            return $checkoutSession;
        }

        $expectedTotal = round((float) $checkoutLink->total_price, 2);

        if (
            (float) $checkoutSession->subtotal === $expectedTotal
            && (float) $checkoutSession->total === $expectedTotal
            && (int) $checkoutSession->product_id === (int) $checkoutLink->product_id
        ) {
            return $checkoutSession;
        }

        $checkoutSession->forceFill([
            'product_id' => $checkoutLink->product_id,
            'subtotal' => $expectedTotal,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => $expectedTotal,
        ])->save();

        return $checkoutSession->fresh();
    }

    private function resolveSellerLogoUrl(CheckoutLink $checkoutLink): string
    {
        $companyLogoPath = $checkoutLink->seller?->company_logo_path;

        if (filled($companyLogoPath) && Storage::disk('public')->exists($companyLogoPath)) {
            return '/company-logo?path='.rawurlencode($companyLogoPath);
        }

        return '/img/logo/juntter_webp_640_174.webp';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCheckoutViewData(
        CheckoutLink $checkoutLink,
        CheckoutSession $checkoutSession,
        ?Order $order,
        ?\App\Models\PaymentTransaction $paymentTransaction,
        string $sellerLogoUrl,
        string $checkoutPageMode,
    ): array {
        return [
            'checkoutLink' => $checkoutLink,
            'checkoutSession' => $checkoutSession,
            'order' => $order,
            'paymentTransaction' => $paymentTransaction,
            'sellerLogoUrl' => $sellerLogoUrl,
            'checkoutPageMode' => $checkoutPageMode,
        ];
    }
}

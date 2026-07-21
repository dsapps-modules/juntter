<?php

namespace App\Http\Controllers;

use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\CheckoutShippingOption;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicCheckoutController extends Controller
{
    public function show(Request $request, string $publicToken): View|\Illuminate\Http\Response|RedirectResponse
    {
        return redirect()->route('checkout.public.spa.show', $publicToken);
    }

    public function showSpa(Request $request, string $publicToken): View|\Illuminate\Http\Response|RedirectResponse
    {
        return $this->renderCheckoutSpaPage($request, $publicToken);
    }

    public function recover(Request $request, string $sessionToken): View|\Illuminate\Http\Response|RedirectResponse
    {
        $checkoutSession = CheckoutSession::query()
            ->with(['checkoutLink.product', 'checkoutLink.seller', 'orders.paymentTransaction'])
            ->where('session_token', $sessionToken)
            ->firstOrFail();

        $checkoutLink = $checkoutSession->checkoutLink;

        if (! $checkoutLink || ! $checkoutLink->isActive() || ! $checkoutLink->product?->isActive()) {
            return response()->view('checkout.unavailable', [
                'message' => 'Este checkout nÃ£o estÃ¡ disponÃ­vel no momento.',
                'sellerBrand' => $checkoutLink ? $this->resolveSellerBrand($checkoutLink) : $this->resolveFallbackSellerBrand(),
            ], 410);
        }

        [$order, $paymentTransaction] = $this->resolvePaymentContext($checkoutSession);

        if (
            in_array($order?->status, ['paid', 'authorized'], true)
            || in_array(strtolower((string) ($paymentTransaction?->internal_status ?? '')), ['authorized', 'paid'], true)
        ) {
            return redirect()->route('checkout.public.thank-you', $checkoutSession->session_token);
        }

        $request->session()->put('checkout_session_token.'.$checkoutLink->public_token, $checkoutSession->session_token);
        $checkoutSession->touchActivity();

        return redirect()->route('checkout.public.spa.show', $checkoutLink->public_token);
    }

    public function deliveryPage(string $sessionToken): View|\Illuminate\Http\Response|RedirectResponse
    {
        $checkoutSession = CheckoutSession::query()
            ->with(['checkoutLink.product', 'checkoutLink.seller', 'orders.paymentTransaction'])
            ->where('session_token', $sessionToken)
            ->firstOrFail();

        $checkoutLink = $checkoutSession->checkoutLink;

        if (! $checkoutLink || ! $checkoutLink->isActive() || ! $checkoutLink->product?->isActive()) {
            return response()->view('checkout.unavailable', [
                'message' => 'Este checkout não está disponível no momento.',
                'sellerBrand' => $checkoutLink ? $this->resolveSellerBrand($checkoutLink) : $this->resolveFallbackSellerBrand(),
            ], 410);
        }

        [$order, $paymentTransaction] = $this->resolvePaymentContext($checkoutSession);

        if (
            in_array($order?->status, ['paid', 'authorized'], true)
            || in_array(strtolower((string) ($paymentTransaction?->internal_status ?? '')), ['authorized', 'paid'], true)
        ) {
            return redirect()->route('checkout.public.thank-you', $checkoutSession->session_token);
        }

        if ($checkoutSession->current_step === 'identification') {
            return redirect()->route('checkout.public.spa.show', $checkoutLink->public_token);
        }

        return redirect()->route('checkout.public.spa.show', $checkoutLink->public_token);
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
                'sellerBrand' => $checkoutLink ? $this->resolveSellerBrand($checkoutLink) : $this->resolveFallbackSellerBrand(),
            ], 410);
        }

        [$order, $paymentTransaction] = $this->resolvePaymentContext($checkoutSession);

        if (
            in_array($order?->status, ['paid', 'authorized'], true)
            || in_array(strtolower((string) ($paymentTransaction?->internal_status ?? '')), ['authorized', 'paid'], true)
        ) {
            return redirect()->route('checkout.public.thank-you', $checkoutSession->session_token);
        }

        if ($checkoutSession->current_step === 'identification') {
            return redirect()->route('checkout.public.spa.show', $checkoutLink->public_token);
        }

        if ($checkoutSession->current_step === 'delivery') {
            return redirect()->route('checkout.public.delivery.page', $checkoutSession->session_token);
        }

        return redirect()->route('checkout.public.spa.show', $checkoutLink->public_token);
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
                'sellerBrand' => $checkoutLink ? $this->resolveSellerBrand($checkoutLink) : $this->resolveFallbackSellerBrand(),
            ], 410);
        }

        [$order, $paymentTransaction] = $this->resolvePaymentContext($checkoutSession);

        if (
            in_array($order?->status, ['paid', 'authorized'], true)
            || in_array(strtolower((string) ($paymentTransaction?->internal_status ?? '')), ['authorized', 'paid'], true)
        ) {
            return redirect()->route('checkout.public.thank-you', $checkoutSession->session_token);
        }

        if (blank($checkoutSession->payment_method)) {
            return redirect()->route('checkout.public.payment.page', $checkoutSession->session_token);
        }

        return redirect()->route('checkout.public.spa.show', $checkoutLink->public_token);
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
                'sellerBrand' => $checkoutLink ? $this->resolveSellerBrand($checkoutLink) : $this->resolveFallbackSellerBrand(),
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

        if ($checkoutPageMode === 'details' && $checkoutSession->current_step === 'delivery') {
            return redirect()->route('checkout.public.delivery.page', $checkoutSession->session_token);
        }

        if ($checkoutPageMode === 'details' && $checkoutSession->current_step === 'payment') {
            return filled($checkoutSession->payment_method)
                ? redirect()->route('checkout.public.payment.details', $checkoutSession->session_token)
                : redirect()->route('checkout.public.payment.page', $checkoutSession->session_token);
        }

        return view('checkout.public', $this->buildCheckoutViewData(
            checkoutLink: $checkoutLink,
            checkoutSession: $checkoutSession,
            order: $order,
            paymentTransaction: $order?->paymentTransaction,
            sellerBrand: $this->resolveSellerBrand($checkoutLink),
            checkoutPageMode: $checkoutPageMode,
        ));
    }

    private function renderCheckoutSpaPage(Request $request, string $publicToken): View|\Illuminate\Http\Response|RedirectResponse
    {
        $checkoutLink = CheckoutLink::query()
            ->with(['product', 'seller'])
            ->where('public_token', $publicToken)
            ->first();

        if (! $checkoutLink || ! $checkoutLink->isActive() || ! $checkoutLink->product?->isActive()) {
            return response()->view('checkout.unavailable', [
                'message' => 'Este checkout não está disponível no momento.',
                'sellerBrand' => $checkoutLink ? $this->resolveSellerBrand($checkoutLink) : $this->resolveFallbackSellerBrand(),
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

        return view('checkout.spa', $this->buildCheckoutViewData(
            checkoutLink: $checkoutLink,
            checkoutSession: $checkoutSession,
            order: $order,
            paymentTransaction: $order?->paymentTransaction,
            sellerBrand: $this->resolveSellerBrand($checkoutLink),
            checkoutPageMode: 'spa',
            extraData: [
                'checkoutSpaAssets' => $this->resolveCheckoutSpaAssets(),
            ],
        ));
    }

    private function resolveCheckoutSpaAssets(): array
    {
        $manifestPath = public_path('build/manifest.json');

        if (! is_file($manifestPath)) {
            return [];
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);

        if (! is_array($manifest) || ! isset($manifest['resources/js/checkout-spa.jsx'])) {
            return [];
        }

        $entry = $manifest['resources/js/checkout-spa.jsx'];
        $assets = [
            'js' => asset('build/'.$entry['file']),
            'css' => array_map(
                fn (string $file): string => asset('build/'.$file),
                $entry['css'] ?? []
            ),
        ];

        return $assets;
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
            'sellerBrand' => $this->resolveSellerBrand($checkoutSession->checkoutLink),
        ]);
    }

    public function productImage(string $publicToken): BinaryFileResponse
    {
        $checkoutLink = CheckoutLink::query()
            ->with('product')
            ->where('public_token', $publicToken)
            ->firstOrFail();

        $productImagePath = filled($checkoutLink->product_image_path)
            ? $checkoutLink->product_image_path
            : $checkoutLink->product?->image_path;

        abort_unless(filled($productImagePath) && Storage::disk('public')->exists($productImagePath), 404);

        return response()->file(Storage::disk('public')->path($productImagePath));
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
            'quantity' => max(1, (int) $checkoutLink->quantity),
            'session_token' => CheckoutSession::generateSessionToken(),
            'status' => 'started',
            'current_step' => 'identification',
            'subtotal' => round(max(1, (int) $checkoutLink->quantity) * (float) $checkoutLink->unit_price, 2),
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => round(max(1, (int) $checkoutLink->quantity) * (float) $checkoutLink->unit_price, 2),
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

        $quantity = max(1, (int) ($checkoutSession->quantity ?? $checkoutLink->quantity));
        $expectedTotal = round($quantity * (float) $checkoutLink->unit_price, 2);

        if (
            (int) $checkoutSession->quantity === $quantity
            && (float) $checkoutSession->subtotal === $expectedTotal
            && (float) $checkoutSession->total === $expectedTotal
            && (int) $checkoutSession->product_id === (int) $checkoutLink->product_id
        ) {
            return $checkoutSession;
        }

        $checkoutSession->forceFill([
            'product_id' => $checkoutLink->product_id,
            'quantity' => $quantity,
            'subtotal' => $expectedTotal,
            'discount_total' => 0,
            'shipping_total' => 0,
            'total' => $expectedTotal,
        ])->save();

        return $checkoutSession->fresh();
    }

    /**
     * @return array{mode: 'logo'|'text', label: string, logoUrl: ?string}
     */
    private function resolveSellerBrand(CheckoutLink $checkoutLink): array
    {
        $seller = $checkoutLink->seller;
        $tradeName = filled($seller?->trade_name) ? trim((string) $seller->trade_name) : '';
        $companyLogoPath = $seller?->company_logo_path;

        if (filled($companyLogoPath) && Storage::disk('public')->exists($companyLogoPath)) {
            return [
                'mode' => 'logo',
                'label' => $tradeName !== '' ? $tradeName : ($seller?->name ?: 'Juntter'),
                'logoUrl' => '/company-logo?path='.rawurlencode($companyLogoPath),
            ];
        }

        if ($tradeName !== '') {
            return [
                'mode' => 'text',
                'label' => $tradeName,
                'logoUrl' => null,
            ];
        }

        return $this->resolveFallbackSellerBrand();
    }

    /**
     * @return array{mode: 'logo', label: string, logoUrl: string}
     */
    private function resolveFallbackSellerBrand(): array
    {
        return [
            'mode' => 'logo',
            'label' => 'Juntter',
            'logoUrl' => '/img/logo/juntter_webp_640_174.webp',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCheckoutViewData(
        CheckoutLink $checkoutLink,
        CheckoutSession $checkoutSession,
        ?Order $order,
        ?\App\Models\PaymentTransaction $paymentTransaction,
        array $sellerBrand,
        string $checkoutPageMode,
        array $extraData = [],
    ): array {
        return array_merge([
            'checkoutLink' => $checkoutLink,
            'checkoutSession' => $checkoutSession,
            'order' => $order,
            'paymentTransaction' => $paymentTransaction,
            'sellerBrand' => $sellerBrand,
            'checkoutPageMode' => $checkoutPageMode,
            'shippingOptions' => $this->resolveShippingOptions($checkoutLink->seller_id),
        ], $extraData);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolveShippingOptions(int $sellerId): array
    {
        $shippingOptions = CheckoutShippingOption::query()
            ->where('seller_id', $sellerId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        if ($shippingOptions->isEmpty()) {
            return [[
                'id' => null,
                'name' => 'Frete padrão',
                'price' => 0,
                'eta_days' => 5,
                'is_default' => true,
                'is_active' => true,
            ]];
        }

        return $shippingOptions->map(static function (CheckoutShippingOption $shippingOption): array {
            return [
                'id' => $shippingOption->id,
                'name' => $shippingOption->name,
                'price' => (float) $shippingOption->price,
                'eta_days' => $shippingOption->eta_days,
                'is_default' => $shippingOption->is_default,
                'is_active' => $shippingOption->is_active,
            ];
        })->all();
    }

    /**
     * @return array{0: ?Order, 1: ?\App\Models\PaymentTransaction}
     */
    private function resolvePaymentContext(CheckoutSession $checkoutSession): array
    {
        $selectedPaymentMethod = $checkoutSession->payment_method;

        $ordersQuery = Order::query()
            ->with(['paymentTransaction'])
            ->where('checkout_session_id', $checkoutSession->id);

        if (filled($selectedPaymentMethod)) {
            $selectedOrder = $ordersQuery
                ->where('payment_method', $selectedPaymentMethod)
                ->latest()
                ->first();

            return [$selectedOrder, $selectedOrder?->paymentTransaction];
        }

        $latestOrder = $ordersQuery->latest()->first();

        return [$latestOrder, $latestOrder?->paymentTransaction];
    }
}

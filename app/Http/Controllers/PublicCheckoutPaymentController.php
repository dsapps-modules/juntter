<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartCheckoutPaymentRequest;
use App\Models\CheckoutEvent;
use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Checkout\CheckoutPricingService;
use App\Services\Payments\Paytime\PaytimePaymentService;
use Illuminate\Http\JsonResponse;

class PublicCheckoutPaymentController extends Controller
{
    public function __construct(
        private readonly CheckoutPricingService $pricingService,
        private readonly PaytimePaymentService $paymentService,
    ) {}

    public function startPayment(StartCheckoutPaymentRequest $request, string $sessionToken): JsonResponse
    {
        $checkoutSession = $this->findSession($sessionToken);
        $checkoutLink = CheckoutLink::query()->findOrFail($checkoutSession->checkout_link_id);
        $paymentMethod = $request->input('payment_method');

        abort_unless($this->paymentMethodIsAllowed($checkoutLink, $paymentMethod), 422, 'Método de pagamento indisponível para este checkout.');

        $pricing = $this->pricingService->calculate($checkoutLink, $paymentMethod);

        $checkoutSession->update([
            'payment_method' => $paymentMethod,
            'subtotal' => $pricing['subtotal'],
            'discount_total' => $pricing['discount_total'],
            'shipping_total' => $pricing['shipping_total'],
            'total' => $pricing['total'],
            'status' => 'payment_started',
            'current_step' => 'payment',
            'last_activity_at' => now(),
        ]);

        CheckoutEvent::query()->create([
            'checkout_session_id' => $checkoutSession->id,
            'checkout_link_id' => $checkoutLink->id,
            'seller_id' => $checkoutLink->seller_id,
            'event_type' => 'payment_started',
            'step' => 'payment',
            'metadata' => ['payment_method' => $paymentMethod],
        ]);

        $order = Order::query()->firstOrCreate([
            'checkout_session_id' => $checkoutSession->id,
            'payment_method' => $paymentMethod,
        ], [
            'seller_id' => $checkoutLink->seller_id,
            'checkout_link_id' => $checkoutLink->id,
            'product_id' => $checkoutLink->product_id,
            'order_number' => $this->generateOrderNumber(),
            'status' => 'pending',
            'customer_name' => (string) $checkoutSession->customer_name,
            'customer_email' => (string) $checkoutSession->customer_email,
            'customer_document' => (string) $checkoutSession->customer_document,
            'customer_phone' => $checkoutSession->customer_phone,
            'quantity' => $pricing['quantity'],
            'unit_price' => $pricing['unit_price'],
            'subtotal' => $pricing['subtotal'],
            'discount_total' => $pricing['discount_total'],
            'shipping_total' => $pricing['shipping_total'],
            'total' => $pricing['total'],
            'success_url_used' => $checkoutLink->success_url,
        ]);

        $gatewayResponse = match ($paymentMethod) {
            'pix' => $this->paymentService->createPixPayment($order),
            'boleto' => $this->paymentService->createBoletoPayment($order),
            default => $this->paymentService->createCreditCardPayment($order, $request->validated()),
        };

        $paymentTransaction = PaymentTransaction::query()->updateOrCreate([
            'order_id' => $order->id,
        ], [
            'seller_id' => $checkoutLink->seller_id,
            'gateway' => 'paytime',
            'gateway_transaction_id' => $gatewayResponse['gateway_transaction_id'],
            'gateway_status' => $gatewayResponse['gateway_status'],
            'internal_status' => $gatewayResponse['internal_status'],
            'payment_method' => $paymentMethod,
            'amount' => $order->total,
            'pix_qr_code' => $gatewayResponse['pix_qr_code'] ?? null,
            'pix_copy_paste' => $gatewayResponse['pix_copy_paste'] ?? null,
            'pix_expires_at' => $gatewayResponse['pix_expires_at'] ?? null,
            'boleto_url' => $gatewayResponse['boleto_url'] ?? null,
            'boleto_barcode' => $gatewayResponse['boleto_barcode'] ?? null,
            'boleto_digitable_line' => $gatewayResponse['boleto_digitable_line'] ?? null,
            'card_last_four' => $gatewayResponse['card_last_four'] ?? null,
            'card_brand' => $gatewayResponse['card_brand'] ?? null,
            'installments' => $request->integer('installments') ?: null,
            'request_payload' => $request->validated(),
            'response_payload' => $gatewayResponse,
        ]);

        $checkoutSession->update([
            'status' => $paymentMethod === 'credit_card' ? 'payment_pending' : 'payment_pending',
        ]);

        CheckoutEvent::query()->create([
            'checkout_session_id' => $checkoutSession->id,
            'checkout_link_id' => $checkoutLink->id,
            'seller_id' => $checkoutLink->seller_id,
            'event_type' => $paymentMethod === 'pix'
                ? 'pix_generated'
                : ($paymentMethod === 'boleto' ? 'boleto_generated' : 'card_payment_submitted'),
            'step' => 'payment',
            'metadata' => [
                'order_id' => $order->id,
                'payment_transaction_id' => $paymentTransaction->id,
            ],
        ]);

        return response()->json([
            'message' => 'Pagamento iniciado com sucesso.',
            'order' => $order->fresh(),
            'payment_transaction' => $paymentTransaction->fresh(),
            'pricing' => $pricing,
            'thank_you_url' => route('checkout.public.thank-you', $checkoutSession->session_token),
        ]);
    }

    public function status(string $sessionToken): JsonResponse
    {
        $checkoutSession = $this->findSession($sessionToken);
        $order = Order::query()->where('checkout_session_id', $checkoutSession->id)->latest()->first();
        $transaction = $order ? PaymentTransaction::query()->where('order_id', $order->id)->latest()->first() : null;

        return response()->json([
            'checkout_session' => $checkoutSession,
            'order' => $order,
            'payment_transaction' => $transaction,
            'thank_you_url' => route('checkout.public.thank-you', $checkoutSession->session_token),
        ]);
    }

    private function findSession(string $sessionToken): CheckoutSession
    {
        return CheckoutSession::query()->where('session_token', $sessionToken)->firstOrFail();
    }

    private function paymentMethodIsAllowed(CheckoutLink $checkoutLink, string $paymentMethod): bool
    {
        return match ($paymentMethod) {
            'pix' => $checkoutLink->allow_pix,
            'boleto' => $checkoutLink->allow_boleto,
            'credit_card' => $checkoutLink->allow_credit_card,
            default => false,
        };
    }

    private function generateOrderNumber(): string
    {
        $year = now()->year;
        $next = (Order::query()->count() + 1);

        return 'JNT-'.$year.'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}

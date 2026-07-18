<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmCheckoutAntifraudAuthRequest;
use App\Http\Requests\SelectCheckoutPaymentMethodRequest;
use App\Http\Requests\StartCheckoutPaymentRequest;
use App\Models\CheckoutEvent;
use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Checkout\CheckoutPricingService;
use App\Services\Payments\Paytime\PaytimePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicCheckoutPaymentController extends Controller
{
    private const MINIMUM_BOLETO_AMOUNT = 10.00;

    public function __construct(
        private readonly CheckoutPricingService $pricingService,
        private readonly PaytimePaymentService $paymentService,
    ) {}

    public function choosePaymentMethod(SelectCheckoutPaymentMethodRequest $request, string $sessionToken): JsonResponse|RedirectResponse
    {
        $checkoutSession = $this->findSession($sessionToken);
        $checkoutLink = CheckoutLink::query()->findOrFail($checkoutSession->checkout_link_id);
        $paymentMethod = $request->input('payment_method');

        if (! $this->paymentMethodIsAllowed($checkoutLink, $paymentMethod)) {
            return $this->respondToPaymentError(
                request: $request,
                message: 'Método de pagamento indisponível para este checkout.',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $checkoutSession->update([
            'payment_method' => $paymentMethod,
            'status' => 'payment_started',
            'current_step' => 'payment',
            'last_activity_at' => now(),
        ]);

        CheckoutEvent::query()->create([
            'checkout_session_id' => $checkoutSession->id,
            'checkout_link_id' => $checkoutLink->id,
            'seller_id' => $checkoutLink->seller_id,
            'event_type' => 'payment_method_selected',
            'step' => 'payment',
            'metadata' => ['payment_method' => $paymentMethod],
        ]);

        $detailsUrl = route('checkout.public.payment.details', $checkoutSession->session_token);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Método de pagamento selecionado com sucesso.',
                'checkout_session' => $checkoutSession->fresh(),
                'payment_details_url' => $detailsUrl,
            ]);
        }

        return redirect()->route('checkout.public.payment.details', $checkoutSession->session_token);
    }

    public function startPayment(StartCheckoutPaymentRequest $request, string $sessionToken): JsonResponse|RedirectResponse
    {
        $checkoutSession = $this->findSession($sessionToken);
        $checkoutLink = CheckoutLink::query()->findOrFail($checkoutSession->checkout_link_id);
        $paymentMethod = $checkoutSession->payment_method ?: $request->input('payment_method');

        if (
            $paymentMethod === 'boleto'
            && ! $this->isValidCheckoutDocument(
                (string) $checkoutSession->customer_document,
                (string) $checkoutSession->customer_document_type
            )
        ) {
            return $this->respondToPaymentError(
                request: $request,
                message: 'O documento do pagador é inválido.',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if (! $this->paymentMethodIsAllowed($checkoutLink, $paymentMethod)) {
            return $this->respondToPaymentError(
                request: $request,
                message: 'Método de pagamento indisponível para este checkout.',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $pricing = $this->pricingService->calculate(
            $checkoutLink,
            $paymentMethod,
            $checkoutSession->quantity,
            (float) $checkoutSession->shipping_total
        );

        if (
            $paymentMethod === 'boleto'
            && ! $this->isBoletoAmountAllowed((float) $pricing['total'])
        ) {
            return $this->respondToPaymentError(
                request: $request,
                message: 'O valor mínimo permitido para o boleto é de dez reais.',
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $validatedRequest = $request->validated();

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

        $order = Order::query()->updateOrCreate([
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
            'billing_zipcode' => $checkoutSession->zipcode,
            'billing_street' => $checkoutSession->street,
            'billing_number' => $checkoutSession->number,
            'billing_complement' => $checkoutSession->complement,
            'billing_neighborhood' => $checkoutSession->neighborhood,
            'billing_city' => $checkoutSession->city,
            'billing_state' => $checkoutSession->state,
            'delivery_zipcode' => $checkoutSession->delivery_zipcode ?: $checkoutSession->zipcode,
            'delivery_street' => $checkoutSession->delivery_street ?: $checkoutSession->street,
            'delivery_number' => $checkoutSession->delivery_number ?: $checkoutSession->number,
            'delivery_complement' => $checkoutSession->delivery_complement ?: $checkoutSession->complement,
            'delivery_neighborhood' => $checkoutSession->delivery_neighborhood ?: $checkoutSession->neighborhood,
            'delivery_city' => $checkoutSession->delivery_city ?: $checkoutSession->city,
            'delivery_state' => $checkoutSession->delivery_state ?: $checkoutSession->state,
            'quantity' => $pricing['quantity'],
            'unit_price' => $pricing['unit_price'],
            'subtotal' => $pricing['subtotal'],
            'discount_total' => $pricing['discount_total'],
            'shipping_total' => $pricing['shipping_total'],
            'total' => $pricing['total'],
            'shipping_option_id' => $checkoutSession->shipping_option_id,
            'shipping_option_name' => $checkoutSession->shipping_option_name,
            'success_url_used' => $checkoutLink->success_url,
        ]);

        try {
            $gatewayResponse = match ($paymentMethod) {
                'pix' => $this->paymentService->createPixPayment($order),
                'boleto' => $this->paymentService->createBoletoPayment($order),
                default => $this->paymentService->createCreditCardPayment($order, $request->validated()),
            };
        } catch (\Throwable $throwable) {
            Log::error('Public checkout payment gateway call failed', [
                'session_token' => $checkoutSession->session_token,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_method' => $paymentMethod,
                'message' => $throwable->getMessage(),
            ]);

            return $this->respondToPaymentFailure(
                request: $request,
                checkoutSession: $checkoutSession,
                order: $order,
                message: $throwable->getMessage() !== '' ? $throwable->getMessage() : 'Não foi possível iniciar o pagamento.',
                gatewayResponse: [],
                statusCode: Response::HTTP_BAD_GATEWAY
            );
        }

        Log::info('Public checkout gateway response received', [
            'session_token' => $checkoutSession->session_token,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_method' => $paymentMethod,
            'gateway_response' => $this->sanitizeGatewayResponseForLog($gatewayResponse),
        ]);

        $gatewayTransactionId = $this->resolveGatewayTransactionId($gatewayResponse);

        if ($gatewayTransactionId === null) {
            $gatewayErrorMessage = $this->resolveGatewayErrorMessage($gatewayResponse);
            $boletoIsUsableWithoutTransactionId = $paymentMethod === 'boleto'
                && (
                    filled($gatewayResponse['boleto_url'] ?? null)
                    || filled($gatewayResponse['boleto_barcode'] ?? null)
                    || filled($gatewayResponse['boleto_digitable_line'] ?? null)
                );

            if (! $boletoIsUsableWithoutTransactionId) {
                $message = $gatewayErrorMessage ?? 'A resposta do gateway não retornou o identificador da transação.';

                Log::warning('Public checkout gateway error response received', [
                    'session_token' => $checkoutSession->session_token,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_method' => $paymentMethod,
                    'message' => $message,
                    'gateway_response' => $this->sanitizeGatewayResponseForLog($gatewayResponse),
                ]);

                return $this->respondToPaymentFailure(
                    request: $request,
                    checkoutSession: $checkoutSession,
                    order: $order,
                    message: $message,
                    gatewayResponse: $gatewayResponse,
                    statusCode: $gatewayErrorMessage !== null ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_BAD_GATEWAY
                );
            }

            Log::warning('Public checkout boleto response is usable but missing transaction id', [
                'session_token' => $checkoutSession->session_token,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_method' => $paymentMethod,
                'gateway_response' => $this->sanitizeGatewayResponseForLog($gatewayResponse),
            ]);
        }

        $paymentTransaction = PaymentTransaction::query()->updateOrCreate([
            'order_id' => $order->id,
        ], [
            'seller_id' => $checkoutLink->seller_id,
            'gateway' => 'paytime',
            'gateway_transaction_id' => $gatewayTransactionId,
            'gateway_status' => $gatewayResponse['gateway_status'] ?? null,
            'internal_status' => $gatewayResponse['internal_status'] ?? 'pending',
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
            'installments' => $paymentMethod === 'credit_card' ? $request->integer('installments') : null,
            'request_payload' => $this->sanitizePaymentRequestPayload($validatedRequest),
            'response_payload' => $gatewayResponse,
        ]);

        if (
            $paymentMethod === 'boleto'
            && blank($paymentTransaction->boleto_url)
            && filled($paymentTransaction->gateway_transaction_id)
        ) {
            $refreshedBoleto = $this->paymentService->refreshBoletoPayment((string) $paymentTransaction->gateway_transaction_id);

            $gatewayResponse = array_replace($gatewayResponse, $refreshedBoleto);

            $paymentTransaction->fill([
                'gateway_status' => $refreshedBoleto['gateway_status'] ?? $paymentTransaction->gateway_status,
                'internal_status' => $refreshedBoleto['internal_status'] ?? $paymentTransaction->internal_status,
                'boleto_url' => $refreshedBoleto['boleto_url'] ?? $paymentTransaction->boleto_url,
                'boleto_barcode' => $refreshedBoleto['boleto_barcode'] ?? $paymentTransaction->boleto_barcode,
                'boleto_digitable_line' => $refreshedBoleto['boleto_digitable_line'] ?? $paymentTransaction->boleto_digitable_line,
                'response_payload' => $refreshedBoleto,
            ])->save();

            $paymentTransaction = $paymentTransaction->fresh();
        }

        $nextCheckoutStatus = in_array(strtolower((string) ($gatewayResponse['internal_status'] ?? 'pending')), ['authorized', 'paid'], true)
            ? 'paid'
            : 'payment_pending';

        $checkoutSession->update([
            'status' => $nextCheckoutStatus,
        ]);

        $order->update([
            'status' => $nextCheckoutStatus === 'paid' ? 'paid' : 'pending',
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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Boleto gerado com sucesso',
                'order' => $order->fresh(),
                'payment_transaction' => $paymentTransaction->fresh(),
                'pricing' => $pricing,
                'requires_3ds' => (bool) ($gatewayResponse['requires_3ds'] ?? false),
                'session_id' => $gatewayResponse['session_id'] ?? null,
                'transaction_id' => $gatewayResponse['transaction_id'] ?? $gatewayTransactionId,
                'thank_you_url' => route('checkout.public.thank-you', $checkoutSession->session_token),
            ]);
        }

        if (
            $nextCheckoutStatus === 'paid'
            || in_array(strtolower((string) ($paymentTransaction->internal_status ?? '')), ['authorized', 'paid'], true)
        ) {
            return redirect()->route('checkout.public.thank-you', $checkoutSession->session_token);
        }

        return redirect()->route('checkout.public.payment.details', $checkoutSession->session_token);
    }

    public function confirmAntifraudAuth(
        ConfirmCheckoutAntifraudAuthRequest $request,
        string $sessionToken,
        string $transactionId
    ): JsonResponse {
        $checkoutSession = $this->findSession($sessionToken);
        $order = Order::query()
            ->where('checkout_session_id', $checkoutSession->id)
            ->latest()
            ->firstOrFail();
        $paymentTransaction = PaymentTransaction::query()
            ->where('order_id', $order->id)
            ->where('gateway_transaction_id', $transactionId)
            ->firstOrFail();

        abort_unless($paymentTransaction->payment_method === 'credit_card', 422, 'A autenticação 3DS só se aplica a cartão de crédito.');

        $validated = $request->validated();
        $gatewayResponse = $this->paymentService->confirmCreditCard3ds($transactionId, $validated);

        $paymentTransaction->fill([
            'gateway_status' => $gatewayResponse['gateway_status'] ?? $paymentTransaction->gateway_status,
            'internal_status' => $gatewayResponse['internal_status'] ?? $paymentTransaction->internal_status,
            'response_payload' => $gatewayResponse,
        ])->save();

        if (strtolower((string) ($gatewayResponse['internal_status'] ?? '')) === 'paid') {
            $order->update([
                'status' => 'paid',
            ]);
            $checkoutSession->update([
                'status' => 'paid',
            ]);
        }

        CheckoutEvent::query()->create([
            'checkout_session_id' => $checkoutSession->id,
            'checkout_link_id' => $checkoutSession->checkout_link_id,
            'seller_id' => $checkoutSession->seller_id,
            'event_type' => 'payment_auth_completed',
            'step' => 'payment',
            'metadata' => $validated,
        ]);

        return response()->json([
            'message' => 'Autenticação 3DS processada com sucesso.',
            'checkout_session' => $checkoutSession->fresh(),
            'order' => $order->fresh(),
            'payment_transaction' => $paymentTransaction->fresh(),
            'thank_you_url' => route('checkout.public.thank-you', $checkoutSession->session_token),
        ]);
    }

    public function status(string $sessionToken): JsonResponse
    {
        $checkoutSession = $this->findSession($sessionToken);
        [$order, $transaction] = $this->resolvePaymentContext($checkoutSession);

        if (
            $order !== null
            && $transaction !== null
            && $transaction->payment_method === 'boleto'
            && $transaction->internal_status !== 'failed'
            && blank($transaction->boleto_url)
            && filled($transaction->gateway_transaction_id)
        ) {
            $refresh = $this->paymentService->refreshBoletoPayment((string) $transaction->gateway_transaction_id);

            $transaction->fill([
                'gateway_status' => $refresh['gateway_status'] ?? $transaction->gateway_status,
                'internal_status' => $refresh['internal_status'] ?? $transaction->internal_status,
                'boleto_url' => $refresh['boleto_url'] ?? null,
                'boleto_barcode' => $refresh['boleto_barcode'] ?? null,
                'boleto_digitable_line' => $refresh['boleto_digitable_line'] ?? null,
                'response_payload' => $refresh,
            ])->save();

            $transaction = $transaction->fresh();
        }

        return response()->json([
            'checkout_session' => $checkoutSession,
            'order' => $order,
            'payment_transaction' => $transaction,
            'thank_you_url' => route('checkout.public.thank-you', $checkoutSession->session_token),
        ]);
    }

    private function respondToPaymentFailure(
        StartCheckoutPaymentRequest $request,
        CheckoutSession $checkoutSession,
        Order $order,
        string $message,
        array $gatewayResponse,
        int $statusCode
    ): JsonResponse|RedirectResponse {
        $checkoutSession->update([
            'status' => 'failed',
        ]);

        $order->update([
            'status' => 'failed',
        ]);

        CheckoutEvent::query()->create([
            'checkout_session_id' => $checkoutSession->id,
            'checkout_link_id' => $checkoutSession->checkout_link_id,
            'seller_id' => $checkoutSession->seller_id,
            'event_type' => 'payment_failed',
            'step' => 'payment',
            'metadata' => [
                'payment_method' => $request->input('payment_method'),
                'message' => $message,
            ],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'paytime_response' => $gatewayResponse,
            ], $statusCode);
        }

        return back()
            ->withInput()
            ->with('error', $message);
    }

    private function respondToPaymentError(
        StartCheckoutPaymentRequest $request,
        string $message,
        int $statusCode
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], $statusCode);
        }

        return back()
            ->withInput()
            ->with('error', $message);
    }

    private function findSession(string $sessionToken): CheckoutSession
    {
        return CheckoutSession::query()->where('session_token', $sessionToken)->firstOrFail();
    }

    /**
     * @return array{0: ?Order, 1: ?PaymentTransaction}
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

    private function paymentMethodIsAllowed(CheckoutLink $checkoutLink, string $paymentMethod): bool
    {
        return match ($paymentMethod) {
            'pix' => $checkoutLink->allow_pix,
            'boleto' => $checkoutLink->allow_boleto,
            'credit_card' => $checkoutLink->allow_credit_card,
            default => false,
        };
    }

    private function isBoletoAmountAllowed(float $amount): bool
    {
        return $amount >= self::MINIMUM_BOLETO_AMOUNT;
    }

    private function generateOrderNumber(): string
    {
        $year = now()->year;

        do {
            $orderNumber = sprintf(
                'JNT-%s-%s-%s',
                $year,
                now()->format('YmdHisv'),
                Str::upper(Str::random(4))
            );
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    private function resolveGatewayTransactionId(array $gatewayResponse): ?string
    {
        $transactionId = $gatewayResponse['gateway_transaction_id']
            ?? data_get($gatewayResponse, 'api_transaction._id')
            ?? data_get($gatewayResponse, 'api_transaction.id')
            ?? data_get($gatewayResponse, 'api_boleto._id')
            ?? data_get($gatewayResponse, 'api_boleto.id')
            ?? data_get($gatewayResponse, 'transaction_id')
            ?? data_get($gatewayResponse, 'id')
            ?? null;

        if (! is_scalar($transactionId) || trim((string) $transactionId) === '') {
            return null;
        }

        return (string) $transactionId;
    }

    private function resolveGatewayErrorMessage(array $gatewayResponse): ?string
    {
        $message = data_get($gatewayResponse, 'message')
            ?? data_get($gatewayResponse, 'error')
            ?? data_get($gatewayResponse, 'errors.0.message')
            ?? data_get($gatewayResponse, 'api_boleto.message')
            ?? data_get($gatewayResponse, 'api_transaction.message');

        if (is_array($message)) {
            $message = $this->resolveGatewayMessageFromArray($message);
        }

        if (! is_scalar($message)) {
            return null;
        }

        $normalizedMessage = trim((string) $message);

        return $normalizedMessage !== '' ? $normalizedMessage : null;
    }

    private function resolveGatewayMessageFromArray(array $messages): ?string
    {
        $flattenedMessages = [];

        array_walk_recursive($messages, function (mixed $value) use (&$flattenedMessages): void {
            if (is_scalar($value)) {
                $normalizedValue = trim((string) $value);

                if ($normalizedValue !== '') {
                    $flattenedMessages[] = $normalizedValue;
                }
            }
        });

        return $flattenedMessages[0] ?? null;
    }

    private function sanitizePaymentRequestPayload(array $payload): array
    {
        if (! isset($payload['card']) || ! is_array($payload['card'])) {
            return $payload;
        }

        unset($payload['card']['card_number'], $payload['card']['security_code'], $payload['card']['holder_document']);

        return $payload;
    }

    private function sanitizeGatewayResponseForLog(array $gatewayResponse): array
    {
        if (isset($gatewayResponse['api_transaction']) && is_array($gatewayResponse['api_transaction'])) {
            unset(
                $gatewayResponse['api_transaction']['card_number'],
                $gatewayResponse['api_transaction']['security_code'],
                $gatewayResponse['api_transaction']['holder_document']
            );
        }

        if (isset($gatewayResponse['api_boleto']) && is_array($gatewayResponse['api_boleto'])) {
            unset(
                $gatewayResponse['api_boleto']['card_number'],
                $gatewayResponse['api_boleto']['security_code'],
                $gatewayResponse['api_boleto']['holder_document']
            );
        }

        return $gatewayResponse;
    }

    private function isValidCheckoutDocument(string $document, string $documentType): bool
    {
        $digits = preg_replace('/\D+/', '', $document) ?? '';
        $documentType = strtolower(trim($documentType));

        return match ($documentType) {
            'cnpj' => $this->isValidCnpj($digits),
            default => $this->isValidCpf($digits),
        };
    }

    private function isValidCpf(string $digits): bool
    {
        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        for ($factor = 10; $factor <= 11; $factor++) {
            $sum = 0;

            for ($index = 0; $index < $factor - 1; $index++) {
                $sum += (int) $digits[$index] * ($factor - $index);
            }

            $digit = ((($sum * 10) % 11) % 10);

            if ((int) $digits[$factor - 1] !== $digit) {
                return false;
            }
        }

        return true;
    }

    private function isValidCnpj(string $digits): bool
    {
        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $weights = [
            [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($weights as $index => $weightSet) {
            $sum = 0;

            foreach ($weightSet as $position => $weight) {
                $sum += (int) $digits[$position] * $weight;
            }

            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $digits[12 + $index] !== $digit) {
                return false;
            }
        }

        return true;
    }
}

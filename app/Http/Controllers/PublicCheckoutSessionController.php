<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutDeliveryRequest;
use App\Http\Requests\StoreCheckoutIdentificationRequest;
use App\Models\CheckoutEvent;
use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCheckoutSessionController extends Controller
{
    public function createOrResume(Request $request, string $publicToken): JsonResponse
    {
        $checkoutLink = CheckoutLink::query()
            ->with(['product', 'seller'])
            ->where('public_token', $publicToken)
            ->firstOrFail();

        abort_unless($checkoutLink->isActive() && $checkoutLink->product?->isActive(), 410, 'Este checkout não está disponível no momento.');

        $sessionToken = $request->input('session_token');
        $checkoutSession = null;

        if (is_string($sessionToken) && $sessionToken !== '') {
            $checkoutSession = CheckoutSession::query()
                ->where('session_token', $sessionToken)
                ->where('checkout_link_id', $checkoutLink->id)
                ->first();
        }

        if (! $checkoutSession) {
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
        }

        CheckoutEvent::query()->firstOrCreate([
            'checkout_session_id' => $checkoutSession->id,
            'event_type' => 'checkout_opened',
        ], [
            'checkout_link_id' => $checkoutLink->id,
            'seller_id' => $checkoutLink->seller_id,
            'step' => $checkoutSession->current_step,
            'metadata' => ['public_token' => $publicToken],
        ]);

        $request->session()->put('checkout_session_token.'.$publicToken, $checkoutSession->session_token);

        return response()->json([
            'message' => 'Checkout session pronta.',
            'checkout_session' => $checkoutSession,
            'checkout_link' => $checkoutLink,
        ]);
    }

    public function saveIdentification(StoreCheckoutIdentificationRequest $request, string $sessionToken): JsonResponse
    {
        $checkoutSession = $this->findSession($sessionToken);

        $checkoutSession->update([
            'customer_name' => $request->input('customer_name'),
            'customer_email' => $request->input('customer_email'),
            'customer_document' => $request->input('customer_document'),
            'customer_document_type' => $request->input('customer_document_type'),
            'customer_phone' => $request->input('customer_phone'),
            'customer_birth_date' => $request->input('customer_birth_date'),
            'customer_company_name' => $request->input('customer_company_name'),
            'customer_state_registration' => $request->input('customer_state_registration'),
            'customer_is_state_registration_exempt' => $request->boolean('customer_is_state_registration_exempt'),
            'status' => 'identification_completed',
            'current_step' => 'delivery',
            'last_activity_at' => now(),
        ]);

        CheckoutEvent::query()->create([
            'checkout_session_id' => $checkoutSession->id,
            'checkout_link_id' => $checkoutSession->checkout_link_id,
            'seller_id' => $checkoutSession->seller_id,
            'event_type' => 'identification_completed',
            'step' => 'identification',
            'metadata' => $request->validated(),
        ]);

        return response()->json([
            'message' => 'Identificação salva com sucesso.',
            'checkout_session' => $checkoutSession->fresh(),
        ]);
    }

    public function saveDelivery(StoreCheckoutDeliveryRequest $request, string $sessionToken): JsonResponse
    {
        $checkoutSession = $this->findSession($sessionToken);

        $checkoutSession->update([
            'zipcode' => $request->input('zipcode'),
            'street' => $request->input('street'),
            'number' => $request->input('number'),
            'complement' => $request->input('complement'),
            'neighborhood' => $request->input('neighborhood'),
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'recipient_name' => $request->input('recipient_name'),
            'status' => 'delivery_completed',
            'current_step' => 'payment',
            'last_activity_at' => now(),
        ]);

        CheckoutEvent::query()->create([
            'checkout_session_id' => $checkoutSession->id,
            'checkout_link_id' => $checkoutSession->checkout_link_id,
            'seller_id' => $checkoutSession->seller_id,
            'event_type' => 'delivery_completed',
            'step' => 'delivery',
            'metadata' => $request->validated(),
        ]);

        return response()->json([
            'message' => 'Entrega salva com sucesso.',
            'checkout_session' => $checkoutSession->fresh(),
        ]);
    }

    private function findSession(string $sessionToken): CheckoutSession
    {
        return CheckoutSession::query()->where('session_token', $sessionToken)->firstOrFail();
    }
}

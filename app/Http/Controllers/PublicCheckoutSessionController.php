<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutDeliveryRequest;
use App\Http\Requests\StoreCheckoutIdentificationRequest;
use App\Models\CheckoutEvent;
use App\Models\CheckoutLink;
use App\Models\CheckoutSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

    public function lookupCompanyByCnpj(string $cnpj): JsonResponse
    {
        $digits = preg_replace('/\D+/', '', $cnpj) ?? '';

        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits) === 1) {
            return response()->json([
                'message' => 'CNPJ inválido.',
            ], 422);
        }

        $response = Http::timeout(8)
            ->acceptJson()
            ->get('https://brasilapi.com.br/api/cnpj/v1/'.$digits);

        if ($response->status() === 404) {
            return response()->json([
                'message' => 'CNPJ não encontrado.',
            ], 404);
        }

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Não foi possível consultar o CNPJ.',
            ], 502);
        }

        $payload = $response->json();
        $companyName = $this->resolveCompanyNameFromLookup($payload);

        if ($companyName === null) {
            return response()->json([
                'message' => 'Não foi possível identificar os dados da empresa.',
            ], 502);
        }

        $responsible = $this->resolveResponsibleFromLookup($payload);

        return response()->json([
            'cnpj' => $digits,
            'company_name' => $companyName,
            'email' => $this->resolveEmailFromLookup($payload),
            'phone' => $this->resolvePhoneFromLookup($payload),
            'responsible_name' => data_get($responsible, 'name'),
            'responsible_document' => data_get($responsible, 'document'),
            'trade_name' => data_get($payload, 'nome_fantasia'),
        ]);
    }

    public function saveIdentification(StoreCheckoutIdentificationRequest $request, string $sessionToken): JsonResponse
    {
        $checkoutSession = $this->findSession($sessionToken);
        $recipientName = $checkoutSession->recipient_name;

        if (blank($recipientName) || $recipientName === $checkoutSession->customer_name) {
            $recipientName = $request->input('customer_name');
        }

        $checkoutSession->update([
            'customer_name' => $request->input('customer_name'),
            'customer_email' => $request->input('customer_email'),
            'customer_document' => $request->input('customer_document'),
            'customer_document_type' => $request->input('customer_document_type'),
            'customer_phone' => $request->input('customer_phone'),
            'customer_birth_date' => $request->input('customer_birth_date'),
            'customer_company_name' => $request->input('customer_company_name'),
            'customer_responsible_document' => $request->input('customer_responsible_document'),
            'customer_responsible_birth_date' => $request->input('customer_responsible_birth_date'),
            'recipient_name' => $recipientName,
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

    private function resolveCompanyNameFromLookup(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        foreach (['razao_social', 'nome_fantasia'] as $key) {
            $value = data_get($payload, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function resolveEmailFromLookup(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        $candidates = [
            data_get($payload, 'email'),
            data_get($payload, 'emails.0'),
            data_get($payload, 'contato.email'),
            data_get($payload, 'contact.email'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $email = trim($candidate);

            if ($email !== '') {
                return $email;
            }
        }

        return null;
    }

    private function resolvePhoneFromLookup(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        foreach (['ddd_telefone_1', 'ddd_telefone_2'] as $key) {
            $phone = data_get($payload, $key);

            if (! is_string($phone)) {
                continue;
            }

            $digits = preg_replace('/\D+/', '', $phone) ?? '';

            if (strlen($digits) === 10 || strlen($digits) === 11) {
                return $digits;
            }
        }

        return null;
    }

    private function resolveResponsibleFromLookup(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $firstPartner = data_get($payload, 'qsa.0');

        if (! is_array($firstPartner)) {
            return [];
        }

        $name = data_get($firstPartner, 'nome_socio') ?? data_get($firstPartner, 'nome_representante_legal');
        $document = data_get($firstPartner, 'cnpj_cpf_do_socio') ?? data_get($firstPartner, 'cpf_representante_legal');
        $documentDigits = is_string($document) ? preg_replace('/\D+/', '', $document) ?? '' : '';

        return [
            'name' => is_string($name) && trim($name) !== '' ? trim($name) : null,
            'document' => strlen($documentDigits) === 11 ? $documentDigits : null,
        ];
    }
}

<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Services\BoletoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CobrancaBoletoDetailController extends Controller
{
    public function __construct(
        private readonly BoletoService $boletoService,
    ) {}

    public function __invoke(Request $request, string $boleto): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('vendedor');

        $rawBoleto = $this->boletoService->consultarBoleto($boleto);
        $boletoData = $this->boletoService->normalizarResposta($this->extractBoleto($rawBoleto));

        if ($this->resolveBoletoId($boletoData) === '') {
            return response()->json(['message' => 'Boleto não encontrado.'], 404);
        }

        $establishmentId = $user->getEstabelecimentoId();

        if ($establishmentId !== null && ! $this->matchesEstablishment($boletoData, $establishmentId)) {
            abort(403, 'Acesso negado');
        }

        $customerName = $this->resolveCustomerName($boletoData);
        [$firstName, $lastName] = $this->splitName($customerName);

        return response()->json([
            'boleto' => [
                'id' => $this->resolveBoletoId($boletoData),
                'external_id' => $this->resolveBoletoId($boletoData),
                'establishment_id' => data_get($boletoData, 'establishment.id')
                    ?? data_get($boletoData, 'establishment_id')
                    ?? data_get($boletoData, 'extra_headers.establishment_id'),
                'status' => $this->normalizeStatus($boletoData['status'] ?? null),
                'status_label' => $this->formatStatus($boletoData['status'] ?? null),
                'amount' => (int) ($boletoData['amount'] ?? 0),
                'original_amount' => (int) ($boletoData['original_amount'] ?? ($boletoData['amount'] ?? 0)),
                'fees' => (int) ($boletoData['fees'] ?? 0),
                'gateway_key' => $boletoData['gateway_key'] ?? null,
                'authorization_code' => $boletoData['authorization_code'] ?? null,
                'created_at' => $this->parseDate($boletoData['created_at'] ?? null)?->format('Y-m-d H:i:s'),
                'updated_at' => $this->parseDate($boletoData['updated_at'] ?? null)?->format('Y-m-d H:i:s'),
                'scheduled_at' => $this->parseDate($boletoData['scheduled_at'] ?? null)?->format('Y-m-d H:i:s'),
                'expiration_at' => $this->parseDate($boletoData['expiration_at'] ?? null)?->format('Y-m-d H:i:s'),
                'paid_at' => $this->parseDate($boletoData['paid_at'] ?? null)?->format('Y-m-d H:i:s'),
                'payment_limit_date' => data_get($boletoData, 'payment_limit_date'),
                'boleto_url' => data_get($boletoData, 'boleto_url'),
                'boleto_barcode' => data_get($boletoData, 'boleto_barcode'),
                'boleto_digitable_line' => data_get($boletoData, 'boleto_digitable_line'),
                'pix_emv' => data_get($boletoData, 'pix_emv') ?? data_get($boletoData, 'emv'),
                'billing_instructions' => $this->resolveBillingInstructions($boletoData),
                'fees_banking' => data_get($boletoData, 'fees_banking', []),
                'customer' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'document' => data_get($boletoData, 'client.document') ?? data_get($boletoData, 'customer_document'),
                    'email' => data_get($boletoData, 'client.email') ?? data_get($boletoData, 'customer.email'),
                    'phone' => data_get($boletoData, 'client.phone') ?? data_get($boletoData, 'customer.phone'),
                ],
                'establishment' => [
                    'id' => data_get($boletoData, 'establishment.id')
                        ?? data_get($boletoData, 'establishment_id')
                        ?? data_get($boletoData, 'extra_headers.establishment_id'),
                    'name' => data_get($boletoData, 'establishment.name')
                        ?? data_get($boletoData, 'establishment.display_name')
                        ?? data_get($boletoData, 'establishment.fantasy_name')
                        ?? data_get($boletoData, 'gateway_key'),
                ],
                'metadata' => $boletoData,
            ],
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    private function resolveCustomerName(array $boleto): string
    {
        $customerName = trim((string) data_get($boleto, 'client.name', data_get($boleto, 'customer_name', '')));

        if ($customerName !== '') {
            return $customerName;
        }

        $firstName = trim((string) data_get($boleto, 'client.first_name', data_get($boleto, 'customer.first_name', '')));
        $lastName = trim((string) data_get($boleto, 'client.last_name', data_get($boleto, 'customer.last_name', '')));

        return trim($firstName.' '.$lastName);
    }

    private function formatStatus(?string $status): string
    {
        return match ($status) {
            'PAID' => 'Pago',
            'APPROVED' => 'Aprovado',
            'PENDING' => 'Pendente',
            'PROCESSING' => 'Processando',
            'FAILED' => 'Falha',
            'CANCELED' => 'Cancelado',
            'REFUNDED' => 'Estornado',
            default => $status ?? 'Desconhecido',
        };
    }

    private function resolveBillingInstructions(array $metadata): array
    {
        $instructions = data_get($metadata, 'billing_instructions');

        if (is_array($instructions) && array_is_list($instructions)) {
            return $instructions;
        }

        if (is_array($instructions) && ! array_is_list($instructions)) {
            return array_values($instructions);
        }

        $instruction = data_get($metadata, 'instruction');

        if (! is_array($instruction)) {
            return [];
        }

        $result = [];

        foreach (['late_fee', 'interest', 'discount'] as $name) {
            if (! isset($instruction[$name]) || ! is_array($instruction[$name])) {
                continue;
            }

            $item = $instruction[$name];

            $result[] = [
                'name' => $name,
                'mode' => $item['mode'] ?? null,
                'amount' => $item['amount'] ?? null,
                'limit_date' => $item['limit_date'] ?? null,
            ];
        }

        return $result;
    }

    private function resolveBoletoId(array $boleto): string
    {
        $id = $boleto['_id']
            ?? $boleto['id']
            ?? $boleto['external_id']
            ?? data_get($boleto, 'boleto._id')
            ?? data_get($boleto, 'boleto.id')
            ?? data_get($boleto, 'boleto.external_id');

        return is_scalar($id) ? (string) $id : '';
    }

    private function extractBoleto(array $rawBoleto): array
    {
        $nested = data_get($rawBoleto, 'data');

        if (is_array($nested)) {
            return $nested;
        }

        return $rawBoleto;
    }

    private function matchesEstablishment(array $boleto, string|int|null $establishmentId): bool
    {
        if ($establishmentId === null || (string) $establishmentId === '') {
            return true;
        }

        $boletoEstablishmentId = data_get($boleto, 'establishment.id')
            ?? data_get($boleto, 'establishment_id')
            ?? data_get($boleto, 'extra_headers.establishment_id');

        return (string) $boletoEstablishmentId === (string) $establishmentId;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeStatus(?string $status): ?string
    {
        if (! is_string($status) || trim($status) === '') {
            return null;
        }

        return strtoupper($status);
    }
}

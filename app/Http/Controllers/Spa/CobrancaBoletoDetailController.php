<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Models\PaytimeTransaction;
use App\Services\BoletoService;
use App\Services\PaytimePricingCacheService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CobrancaBoletoDetailController extends Controller
{
    public function __construct(
        private readonly BoletoService $boletoService,
        private readonly PaytimePricingCacheService $pricingCacheService,
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
        $localTransaction = PaytimeTransaction::query()
            ->where('external_id', $this->resolveBoletoId($boletoData))
            ->first();

        if ($localTransaction !== null) {
            $boletoData = $this->mergeLocalTransactionMetadata($boletoData, $localTransaction);
        }

        if ($this->resolveBoletoId($boletoData) === '') {
            return response()->json(['message' => 'Boleto não encontrado.'], 404);
        }

        $establishmentId = $user->getEstabelecimentoId();

        if ($establishmentId !== null && ! $this->matchesEstablishment($boletoData, $establishmentId)) {
            abort(403, 'Acesso negado');
        }

        $customerName = $this->resolveCustomerName($boletoData);
        [$firstName, $lastName] = $this->splitName($customerName);

        $resolvedAmounts = $this->resolveDisplayedAmounts($boletoData);

        return response()->json([
            'boleto' => [
                'id' => $this->resolveBoletoId($boletoData),
                'external_id' => $this->resolveBoletoId($boletoData),
                'establishment_id' => data_get($boletoData, 'establishment.id')
                    ?? data_get($boletoData, 'establishment_id')
                    ?? data_get($boletoData, 'extra_headers.establishment_id'),
                'status' => $this->normalizeStatus($boletoData['status'] ?? null),
                'status_label' => $this->formatStatus($boletoData['status'] ?? null),
                'amount' => $resolvedAmounts['amount'],
                'original_amount' => $resolvedAmounts['original_amount'],
                'fees' => $resolvedAmounts['fees'],
                'gateway_key' => $boletoData['gateway_key'] ?? null,
                'authorization_code' => $boletoData['authorization_code'] ?? null,
                'created_at' => $this->parseDate($boletoData['created_at'] ?? null)?->format('Y-m-d H:i:s'),
                'updated_at' => $this->parseDate($boletoData['updated_at'] ?? null)?->format('Y-m-d H:i:s'),
                'scheduled_at' => $this->parseDate($boletoData['scheduled_at'] ?? null)?->format('Y-m-d H:i:s'),
                'expiration_at' => $this->parseDate($boletoData['expiration_at'] ?? null)?->format('Y-m-d H:i:s'),
                'paid_at' => $this->parseDate($boletoData['paid_at'] ?? null)?->format('Y-m-d H:i:s'),
                'payment_limit_date' => data_get($boletoData, 'payment_limit_date'),
                'juros' => $this->resolveTaxPayer($boletoData),
                'boleto_url' => data_get($boletoData, 'boleto_url'),
                'boleto_barcode' => data_get($boletoData, 'boleto_barcode'),
                'boleto_digitable_line' => data_get($boletoData, 'boleto_digitable_line'),
                'pix_emv' => data_get($boletoData, 'pix_emv') ?? data_get($boletoData, 'emv'),
                'billing_instructions' => $this->resolveBillingInstructions($boletoData),
                'fees_banking' => $this->resolveFeesBanking($boletoData, $establishmentId),
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

    /**
     * @return array<int, mixed>
     */
    private function resolveFeesBanking(array $boletoData, string|int|null $establishmentId): array
    {
        if ($establishmentId !== null && (string) $establishmentId !== '') {
            $cachedEstablishment = $this->pricingCacheService->cachedEstablishment((string) $establishmentId);

            if ($cachedEstablishment?->fees_banking_json !== null) {
                return $cachedEstablishment->fees_banking_json;
            }
        }

        $feesBanking = data_get($boletoData, 'fees_banking', []);

        return is_array($feesBanking) ? $feesBanking : [];
    }

    private function resolveTaxPayer(array $boleto): string
    {
        $explicitTaxPayer = strtoupper((string) (
            data_get($boleto, 'juros')
            ?? data_get($boleto, 'interest')
            ?? data_get($boleto, 'metadata.juros')
            ?? data_get($boleto, 'metadata.interest')
            ?? data_get($boleto, 'metadata.request.juros')
            ?? data_get($boleto, 'metadata.request.interest')
        ));

        if (in_array($explicitTaxPayer, ['CLIENT', 'ESTABLISHMENT'], true)) {
            return $explicitTaxPayer;
        }

        $amount = (int) ($boleto['amount'] ?? 0);
        $originalAmount = (int) ($boleto['original_amount'] ?? $amount);
        $fees = (int) ($boleto['fees'] ?? 0);

        if ($originalAmount > $amount && $fees > 0) {
            return 'CLIENT';
        }

        return 'ESTABLISHMENT';
    }

    private function mergeLocalTransactionMetadata(array $boletoData, PaytimeTransaction $transaction): array
    {
        $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];
        $requestMetadata = is_array($metadata['request'] ?? null) ? $metadata['request'] : [];

        if ($requestMetadata !== []) {
            $boletoData['metadata']['request'] = array_replace(
                is_array(data_get($boletoData, 'metadata.request')) ? data_get($boletoData, 'metadata.request') : [],
                $requestMetadata,
            );
        }

        $explicitTaxPayer = strtoupper((string) (
            $requestMetadata['juros'] ?? $requestMetadata['interest'] ?? data_get($boletoData, 'juros')
        ));

        if (in_array($explicitTaxPayer, ['CLIENT', 'ESTABLISHMENT'], true)) {
            $boletoData['juros'] = $explicitTaxPayer;
        }

        if (! isset($boletoData['amount']) && $transaction->amount !== null) {
            $boletoData['amount'] = (int) $transaction->amount;
        }

        if (! isset($boletoData['original_amount']) && $transaction->original_amount !== null) {
            $boletoData['original_amount'] = (int) $transaction->original_amount;
        }

        if (! isset($boletoData['fees']) && $transaction->fees !== null) {
            $boletoData['fees'] = (int) $transaction->fees;
        }

        return $boletoData;
    }

    /**
     * @return array{amount: int, original_amount: int, fees: int}
     */
    private function resolveDisplayedAmounts(array $boletoData): array
    {
        $requestMetadata = data_get($boletoData, 'metadata.request', []);
        $baseAmountCents = (int) (
            data_get($requestMetadata, 'base_amount_cents')
            ?? data_get($boletoData, 'original_amount')
            ?? data_get($boletoData, 'amount')
            ?? 0
        );
        $taxAmountCents = (int) (
            data_get($requestMetadata, 'tax_amount_cents')
            ?? data_get($boletoData, 'fees')
            ?? 0
        );
        $taxPayer = $this->resolveTaxPayer($boletoData);

        if ($taxPayer === 'CLIENT') {
            return [
                'amount' => $baseAmountCents,
                'original_amount' => $baseAmountCents + $taxAmountCents,
                'fees' => $taxAmountCents,
            ];
        }

        return [
            'amount' => max(0, $baseAmountCents - $taxAmountCents),
            'original_amount' => $baseAmountCents,
            'fees' => $taxAmountCents,
        ];
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

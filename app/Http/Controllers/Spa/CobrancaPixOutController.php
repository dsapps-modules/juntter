<?php

namespace App\Http\Controllers\Spa;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmElectronicSignatureCodeRequest;
use App\Http\Requests\ConfirmPixPayoutCodeRequest;
use App\Http\Requests\StoreElectronicSignatureRequest;
use App\Http\Requests\StorePixPayoutRequest;
use App\Mail\SecurityCodeMail;
use App\Models\PixPayoutRequest;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\PixPayoutService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CobrancaPixOutController extends Controller
{
    public function __construct(
        private readonly BalanceService $balanceService,
        private readonly PixPayoutService $pixPayoutService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('vendedor.estabelecimento');

        $establishmentId = $user->getEstabelecimentoId();

        if ($establishmentId === null || $establishmentId === '') {
            return response()->json([
                'seller_name' => $this->resolveSellerName($user),
                'establishment' => null,
                'balance' => $this->emptyBalance(),
                'fee' => $this->formatFee(0),
                'available_after_fee' => $this->formatFee(0),
                'requests' => [],
                'pix_key_types' => $this->pixKeyTypeOptions(),
                'electronic_signature' => $this->normalizeElectronicSignatureState($user),
                'message' => 'Nenhum estabelecimento foi vinculado ao usuário autenticado.',
            ]);
        }

        $warnings = [];
        $balance = $this->emptyBalance();

        try {
            $balance = $this->normalizeBalance(
                $this->balanceService->saldoAtual([
                    'extra_headers' => [
                        'establishment_id' => (string) $establishmentId,
                    ],
                ])
            );
        } catch (Throwable $throwable) {
            $warnings[] = 'Não foi possível carregar o saldo disponível: '.$throwable->getMessage();
        }

        $feeCents = $this->resolvePayoutFeeCents();
        $availableAfterFee = max(0, $balance['available'] - $feeCents);

        $requests = PixPayoutRequest::query()
            ->where('seller_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (PixPayoutRequest $request): array => $this->normalizeRequest($request));

        return response()->json([
            'seller_name' => $this->resolveSellerName($user),
            'establishment' => [
                'id' => (string) $establishmentId,
                'name' => $this->resolveEstablishmentName($user),
            ],
            'balance' => $balance,
            'fee' => $this->formatFee($feeCents),
            'available_after_fee' => $this->formatFee($availableAfterFee),
            'requests' => $requests->values(),
            'pix_key_types' => $this->pixKeyTypeOptions(),
            'electronic_signature' => $this->normalizeElectronicSignatureState($user),
            'message' => $warnings !== [] ? implode(' ', $warnings) : null,
        ]);
    }

    public function requestElectronicSignatureCode(StoreElectronicSignatureRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $newSignature = $request->string('electronic_signature')->toString();
        $confirmedSignature = $request->string('electronic_signature_confirmation')->toString();

        if ($newSignature !== $confirmedSignature) {
            return response()->json([
                'message' => 'A confirmação da assinatura eletrônica não confere.',
            ], 422);
        }

        $currentSignatureHash = is_string($user->electronic_signature_hash) ? $user->electronic_signature_hash : null;
        $code = $this->generateVerificationCode();
        $expiresAt = now()->addMinutes(10);

        $user->forceFill([
            'electronic_signature_pending_hash' => Hash::make($newSignature),
            'electronic_signature_code_hash' => Hash::make($code),
            'electronic_signature_code_attempts' => 0,
            'electronic_signature_code_sent_at' => now(),
            'electronic_signature_code_expires_at' => $expiresAt,
        ])->save();

        try {
            Mail::to((string) $user->email)->send(new SecurityCodeMail($code, 'assinatura eletrônica'));
        } catch (Throwable $throwable) {
            $user->forceFill([
                'electronic_signature_pending_hash' => null,
                'electronic_signature_code_hash' => null,
                'electronic_signature_code_attempts' => 0,
                'electronic_signature_code_sent_at' => null,
                'electronic_signature_code_expires_at' => null,
            ])->save();

            Log::warning('Falha ao enviar código de assinatura eletrônica', [
                'seller_id' => $user->id,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'message' => 'Não foi possível enviar o código de verificação.',
            ], 400);
        }

        return response()->json([
            'message' => $currentSignatureHash ? 'Código enviado para atualizar a assinatura eletrônica.' : 'Código enviado para cadastrar a assinatura eletrônica.',
            'requires_code' => true,
            'electronic_signature' => $this->normalizeElectronicSignatureState($user->fresh()),
        ]);
    }

    public function confirmElectronicSignatureCode(ConfirmElectronicSignatureCodeRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->hasPendingElectronicSignature($user)) {
            return response()->json([
                'message' => 'Não há assinatura eletrônica pendente para confirmação.',
            ], 422);
        }

        if ($this->isElectronicSignatureCodeExpired($user)) {
            $this->clearElectronicSignatureCode($user, true);

            return response()->json([
                'message' => 'O código de verificação expirou. Solicite um novo código.',
            ], 422);
        }

        if ($this->electronicSignatureCodeAttempts($user) >= 3) {
            return response()->json([
                'message' => 'Limite de tentativas do código atingido.',
            ], 423);
        }

        $submittedCode = $request->string('verification_code')->toString();

        if (! $this->matchesElectronicSignatureCode($user, $submittedCode)) {
            $attempts = $this->electronicSignatureCodeAttempts($user) + 1;
            $user->forceFill([
                'electronic_signature_code_attempts' => $attempts,
            ])->save();

            return response()->json([
                'message' => 'Código de verificação inválido.',
                'remaining_attempts' => max(0, 3 - $attempts),
            ], 422);
        }

        $user->forceFill([
            'electronic_signature_hash' => $user->electronic_signature_pending_hash,
            'electronic_signature_pending_hash' => null,
            'electronic_signature_code_hash' => null,
            'electronic_signature_code_attempts' => 0,
            'electronic_signature_code_sent_at' => null,
            'electronic_signature_code_expires_at' => null,
            'electronic_signature_verified_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Assinatura eletrônica validada com sucesso.',
            'electronic_signature' => $this->normalizeElectronicSignatureState($user->fresh()),
        ]);
    }

    public function store(StorePixPayoutRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('vendedor.estabelecimento');

        $establishmentId = $user->getEstabelecimentoId();

        if ($establishmentId === null || $establishmentId === '') {
            return response()->json([
                'message' => 'Nenhum estabelecimento foi vinculado ao usuário autenticado.',
            ], 422);
        }

        $storedSignatureHash = is_string($user->electronic_signature_hash) ? $user->electronic_signature_hash : null;
        if (! is_string($storedSignatureHash) || $storedSignatureHash === '') {
            return response()->json([
                'message' => 'Cadastre uma assinatura eletrônica antes de iniciar a transação.',
            ], 422);
        }

        $submittedSignature = $request->string('electronic_signature')->toString();
        if (! Hash::check($submittedSignature, $storedSignatureHash)) {
            return response()->json([
                'message' => 'Assinatura eletrônica inválida.',
            ], 422);
        }

        $amount = $this->convertAmountToCents($request->string('amount')->toString());
        $balance = $this->resolveAvailableBalance((string) $establishmentId);
        $feeCents = $this->resolvePayoutFeeCents();
        $availableAfterFee = $balance !== null ? max(0, $balance - $feeCents) : null;

        if ($availableAfterFee !== null && $amount > $availableAfterFee) {
            return response()->json([
                'message' => 'O valor solicitado é maior que o saldo disponível para envio.',
                'available_balance' => $this->formatMoney($availableAfterFee),
            ], 422);
        }

        $pixKeyType = $request->string('pix_key_type')->toString();
        $pixKey = $this->resolvePixKey($request, $pixKeyType);

        $pixPayoutRequest = PixPayoutRequest::query()->create([
            'seller_id' => $user->id,
            'establishment_id' => (string) $establishmentId,
            'amount' => $amount,
            'pix_key_type' => $pixKeyType,
            'pix_key' => $pixKey['pix_key'],
            'description' => $request->string('description')->toString(),
            'status' => 'initiating',
            'init_payload' => $this->buildInitPayload((string) $establishmentId, $pixKeyType, $pixKey),
            'confirmation_code_attempts' => 0,
        ]);

        try {
            $response = $this->pixPayoutService->initiate($pixPayoutRequest->init_payload ?? []);
        } catch (Throwable $throwable) {
            $pixPayoutRequest->update([
                'status' => 'failed',
                'last_error' => $throwable->getMessage(),
            ]);

            Log::warning('Falha ao iniciar payout PIX', [
                'payout_request_id' => $pixPayoutRequest->id,
                'seller_id' => $pixPayoutRequest->seller_id,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'message' => 'Não foi possível iniciar o envio do PIX.',
            ], 400);
        }

        $initId = $this->resolveInitId($response);

        if ($initId === null) {
            $pixPayoutRequest->update([
                'status' => 'failed',
                'init_response' => $response,
                'last_error' => 'Resposta da Paytime sem identificador de init.',
            ]);

            return response()->json([
                'message' => 'A Paytime não retornou o identificador de início da transferência.',
            ], 400);
        }

        $expiresAt = $this->resolveExpiresAt($response) ?? now()->addMinutes(5);
        $confirmationCode = $this->generateVerificationCode();
        $confirmationCodeExpiresAt = now()->addMinutes(10);

        $pixPayoutRequest->update([
            'status' => 'awaiting_confirmation',
            'init_id' => $initId,
            'gateway_authorization' => data_get($response, 'gateway_authorization'),
            'init_response' => $response,
            'expires_at' => $expiresAt,
            'confirmation_code_hash' => Hash::make($confirmationCode),
            'confirmation_code_attempts' => 0,
            'confirmation_code_sent_at' => now(),
            'confirmation_code_expires_at' => $confirmationCodeExpiresAt,
            'last_error' => null,
        ]);

        try {
            Mail::to((string) $user->email)->send(new SecurityCodeMail($confirmationCode, 'confirmação do envio PIX'));
        } catch (Throwable $throwable) {
            $pixPayoutRequest->update([
                'status' => 'failed',
                'last_error' => $throwable->getMessage(),
            ]);

            Log::warning('Falha ao enviar código de confirmação do PIX', [
                'payout_request_id' => $pixPayoutRequest->id,
                'seller_id' => $pixPayoutRequest->seller_id,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'message' => 'A transação foi iniciada, mas não foi possível enviar o código de confirmação.',
            ], 400);
        }

        $freshRequest = $pixPayoutRequest->fresh();

        return response()->json([
            'message' => 'Solicitação iniciada. Agora confirme com o código recebido por e-mail.',
            'payout_request' => $this->normalizeRequest($freshRequest),
            'review' => $this->buildTransactionReview($user, $freshRequest, $response, $availableAfterFee),
        ]);
    }

    public function confirm(ConfirmPixPayoutCodeRequest $request, PixPayoutRequest $pixPayoutRequest): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User || (int) $pixPayoutRequest->seller_id !== (int) $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($pixPayoutRequest->status !== 'awaiting_confirmation') {
            return response()->json([
                'message' => 'A solicitação não está pronta para confirmação.',
            ], 422);
        }

        if ($pixPayoutRequest->expires_at !== null && Carbon::parse($pixPayoutRequest->expires_at)->isPast()) {
            $pixPayoutRequest->update([
                'status' => 'expired',
                'last_error' => 'A solicitação expirou antes da confirmação.',
            ]);

            return response()->json([
                'message' => 'A solicitação expirou.',
            ], 422);
        }

        if ($pixPayoutRequest->confirmation_code_attempts >= 3) {
            $pixPayoutRequest->update([
                'status' => 'blocked',
                'last_error' => 'Limite de tentativas do código atingido.',
            ]);

            return response()->json([
                'message' => 'Limite de tentativas do código atingido.',
            ], 423);
        }

        if ($this->isConfirmationCodeExpired($pixPayoutRequest)) {
            $pixPayoutRequest->update([
                'status' => 'expired',
                'last_error' => 'O código de confirmação expirou.',
            ]);

            return response()->json([
                'message' => 'O código de confirmação expirou.',
            ], 422);
        }

        $submittedCode = $request->string('verification_code')->toString();

        if (! $this->matchesConfirmationCode($pixPayoutRequest, $submittedCode)) {
            $attempts = $pixPayoutRequest->confirmation_code_attempts + 1;
            $pixPayoutRequest->update([
                'confirmation_code_attempts' => $attempts,
                'status' => $attempts >= 3 ? 'blocked' : 'awaiting_confirmation',
                'last_error' => 'Código de confirmação inválido.',
            ]);

            return response()->json([
                'message' => 'Código de confirmação inválido.',
                'remaining_attempts' => max(0, 3 - $attempts),
            ], 422);
        }

        try {
            $response = $this->pixPayoutService->confirm([
                'type' => $pixPayoutRequest->pix_key_type,
                'key' => $pixPayoutRequest->pix_key,
                'amount' => $pixPayoutRequest->amount,
                'init_id' => $pixPayoutRequest->init_id,
            ]);
        } catch (Throwable $throwable) {
            $pixPayoutRequest->update([
                'status' => 'failed',
                'last_error' => $throwable->getMessage(),
                'confirm_payload' => [
                    'type' => $pixPayoutRequest->pix_key_type,
                    'amount' => $pixPayoutRequest->amount,
                    'init_id' => $pixPayoutRequest->init_id,
                ],
            ]);

            Log::warning('Falha ao confirmar payout PIX', [
                'payout_request_id' => $pixPayoutRequest->id,
                'seller_id' => $pixPayoutRequest->seller_id,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'message' => 'Não foi possível confirmar o envio do PIX.',
            ], 400);
        }

        $gatewayTransactionId = $this->resolveTransferId($response) ?? data_get($response, 'transaction_id');
        $receiptUrl = $this->resolveReceiptUrl($response);

        $pixPayoutRequest->update([
            'status' => 'confirmed',
            'confirm_payload' => [
                'type' => $pixPayoutRequest->pix_key_type,
                'amount' => $pixPayoutRequest->amount,
                'init_id' => $pixPayoutRequest->init_id,
            ],
            'confirm_response' => $response,
            'gateway_transaction_id' => is_scalar($gatewayTransactionId) ? (string) $gatewayTransactionId : null,
            'confirmed_at' => now(),
            'confirmation_code_verified_at' => now(),
            'last_error' => null,
        ]);

        Log::info('Payout PIX confirmado', [
            'payout_request_id' => $pixPayoutRequest->id,
            'seller_id' => $pixPayoutRequest->seller_id,
            'gateway_transaction_id' => $gatewayTransactionId,
        ]);

        $freshRequest = $pixPayoutRequest->fresh();

        return response()->json([
            'message' => 'PIX enviado com sucesso.',
            'payout_request' => $this->normalizeRequest($freshRequest, $receiptUrl),
        ]);
    }

    private function resolveSellerName(object $user): string
    {
        return trim((string) ($user->name ?? '')) !== '' ? (string) $user->name : 'Vendedor';
    }

    private function resolveEstablishmentName(object $user): string
    {
        $user->loadMissing('vendedor.estabelecimento');

        $establishment = $user->vendedor?->estabelecimento;

        return (string) data_get(
            $establishment,
            'display_name',
            data_get($establishment, 'fantasy_name', data_get($establishment, 'name', $user->vendedor?->estabelecimento_id ?? 'Estabelecimento'))
        );
    }

    /**
     * @return array{available:int, available_label:string, blocked:int, blocked_label:string, total:int, total_label:string}
     */
    private function normalizeBalance(array $response): array
    {
        $available = $this->extractAmount($response, ['balance', 'data.balance']);
        $blocked = $this->extractAmount($response, ['blocked_balance', 'data.blocked_balance']);
        $total = $this->extractAmount($response, ['total_balance', 'data.total_balance']);

        if ($total === 0 && ($available !== 0 || $blocked !== 0)) {
            $total = $available + $blocked;
        }

        return [
            'available' => $available,
            'available_label' => $this->formatMoney($available),
            'blocked' => $blocked,
            'blocked_label' => $this->formatMoney($blocked),
            'total' => $total,
            'total_label' => $this->formatMoney($total),
        ];
    }

    private function emptyBalance(): array
    {
        return [
            'available' => 0,
            'available_label' => $this->formatMoney(0),
            'blocked' => 0,
            'blocked_label' => $this->formatMoney(0),
            'total' => 0,
            'total_label' => $this->formatMoney(0),
        ];
    }

    private function resolveAvailableBalance(string $establishmentId): ?int
    {
        try {
            $response = $this->balanceService->saldoAtual([
                'extra_headers' => [
                    'establishment_id' => $establishmentId,
                ],
            ]);

            return $this->extractAmount($response, ['balance', 'data.balance']);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{pix_key?: string|null}
     */
    private function resolvePixKey(StorePixPayoutRequest $request, string $pixKeyType): array
    {
        return [
            'pix_key' => $request->string('pix_key')->toString(),
        ];
    }

    /**
     * @return array<int, array{value:string, label:string, help:string}>
     */
    private function pixKeyTypeOptions(): array
    {
        return [
            ['value' => 'PHONE', 'label' => 'Celular', 'help' => 'Chave de telefone cadastrada.'],
            ['value' => 'CPF', 'label' => 'CPF', 'help' => 'Chave vinculada ao CPF.'],
            ['value' => 'EMAIL', 'label' => 'E-mail', 'help' => 'Chave de e-mail cadastrada.'],
            ['value' => 'CNPJ', 'label' => 'CNPJ', 'help' => 'Chave vinculada ao CNPJ.'],
        ];
    }

    private function buildInitPayload(string $establishmentId, string $pixKeyType, array $pixKey): array
    {
        $payload = [
            'type' => $pixKeyType,
            'extra_headers' => [
                'establishment_id' => $establishmentId,
            ],
        ];

        $payload['key'] = $pixKey['pix_key'] ?? null;

        return $payload;
    }

    private function buildTransactionReview(User $user, PixPayoutRequest $request, array $response, ?int $availableAfterFee): array
    {
        $keyType = $this->resolvePixKeyTypeLabel($request->pix_key_type);

        return [
            'amount' => $request->amount,
            'amount_label' => $this->formatMoney((int) $request->amount),
            'fee_cents' => $this->resolvePayoutFeeCents(),
            'fee_label' => $this->formatMoney($this->resolvePayoutFeeCents()),
            'available_after_fee' => $availableAfterFee,
            'available_after_fee_label' => $this->formatMoney($availableAfterFee ?? 0),
            'receiver' => [
                'name' => (string) data_get($response, 'receiver.name', data_get($response, 'beneficiary.name', $this->maskPixKey((string) $request->pix_key))),
                'document' => (string) data_get($response, 'receiver.document', data_get($response, 'beneficiary.document', 'Não informado')),
                'institution' => (string) data_get($response, 'receiver.institution', data_get($response, 'beneficiary.institution', 'Não informado')),
                'pix_key_type' => $keyType,
                'pix_key' => $this->maskPixKey((string) $request->pix_key),
            ],
            'debtor' => [
                'name' => $this->resolveSellerName($user),
                'document' => (string) data_get($response, 'debtor.document', data_get($response, 'payer.document', $user->getEstabelecimentoId() ?? 'Não informado')),
                'institution' => $this->resolveEstablishmentName($user),
            ],
        ];
    }

    private function resolveInitId(array $response): ?string
    {
        $value = data_get($response, 'init_id');

        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }

    private function resolveTransferId(array $response): ?string
    {
        foreach (['_id', 'id', 'init_id', 'transfer_id', 'transaction_id'] as $key) {
            $value = data_get($response, $key);

            if (is_scalar($value) && $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    private function resolveReceiptUrl(array $response): ?string
    {
        foreach (['receipt_url', 'voucher_url', 'proof_url', 'comprovante_url'] as $key) {
            $value = data_get($response, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function resolveExpiresAt(array $response): ?Carbon
    {
        foreach (['expires_at', 'expiration', 'expiration_at', 'expiresAt'] as $key) {
            $value = data_get($response, $key);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            try {
                return Carbon::parse($value);
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function normalizeRequest(PixPayoutRequest $request, ?string $receiptUrl = null): array
    {
        return [
            'id' => $request->id,
            'seller_id' => $request->seller_id,
            'amount' => $request->amount,
            'amount_label' => $this->formatMoney((int) $request->amount),
            'pix_key_type' => $request->pix_key_type,
            'pix_key_label' => $this->maskPixKey((string) ($request->pix_key ?? '')),
            'description' => $request->description ?? '',
            'status' => $request->status,
            'status_label' => $this->formatStatus($request->status),
            'init_id' => $request->init_id,
            'gateway_transaction_id' => $request->gateway_transaction_id,
            'confirmation_code_attempts' => $request->confirmation_code_attempts,
            'confirmation_code_sent_at' => $request->confirmation_code_sent_at?->format('d/m/Y H:i'),
            'confirmation_code_expires_at' => $request->confirmation_code_expires_at?->format('d/m/Y H:i'),
            'confirmation_code_verified_at' => $request->confirmation_code_verified_at?->format('d/m/Y H:i'),
            'expires_at' => $request->expires_at?->format('d/m/Y H:i'),
            'confirmed_at' => $request->confirmed_at?->format('d/m/Y H:i'),
            'last_error' => $request->last_error,
            'created_at' => $request->created_at?->format('d/m/Y H:i'),
            'receipt_url' => $receiptUrl,
        ];
    }

    private function normalizeElectronicSignatureState(User $user): array
    {
        return [
            'configured' => is_string($user->electronic_signature_hash ?? null) && $user->electronic_signature_hash !== '',
            'pending' => is_string($user->electronic_signature_pending_hash ?? null) && $user->electronic_signature_pending_hash !== '',
            'verified_at' => $user->electronic_signature_verified_at?->format('d/m/Y H:i'),
            'code_sent_at' => $user->electronic_signature_code_sent_at?->format('d/m/Y H:i'),
            'code_expires_at' => $user->electronic_signature_code_expires_at?->format('d/m/Y H:i'),
        ];
    }

    private function hasPendingElectronicSignature(User $user): bool
    {
        return is_string($user->electronic_signature_pending_hash ?? null) && $user->electronic_signature_pending_hash !== '';
    }

    private function isElectronicSignatureCodeExpired(User $user): bool
    {
        return $user->electronic_signature_code_expires_at !== null && Carbon::parse($user->electronic_signature_code_expires_at)->isPast();
    }

    private function electronicSignatureCodeAttempts(User $user): int
    {
        return (int) ($user->electronic_signature_code_attempts ?? 0);
    }

    private function matchesElectronicSignatureCode(User $user, string $submittedCode): bool
    {
        $hash = $user->electronic_signature_code_hash;

        return is_string($hash) && $hash !== '' && Hash::check($submittedCode, $hash);
    }

    private function clearElectronicSignatureCode(User $user, bool $clearPendingHash = false): void
    {
        $user->forceFill([
            'electronic_signature_pending_hash' => $clearPendingHash ? null : $user->electronic_signature_pending_hash,
            'electronic_signature_code_hash' => null,
            'electronic_signature_code_attempts' => 0,
            'electronic_signature_code_sent_at' => null,
            'electronic_signature_code_expires_at' => null,
        ])->save();
    }

    private function isConfirmationCodeExpired(PixPayoutRequest $request): bool
    {
        return $request->confirmation_code_expires_at !== null && Carbon::parse($request->confirmation_code_expires_at)->isPast();
    }

    private function matchesConfirmationCode(PixPayoutRequest $request, string $submittedCode): bool
    {
        $hash = $request->confirmation_code_hash;

        return is_string($hash) && $hash !== '' && Hash::check($submittedCode, $hash);
    }

    private function resolvePayoutFeeCents(): int
    {
        return max(0, (int) config('services.paytime.payout_fee_cents', 29));
    }

    private function formatFee(int $amount): array
    {
        return [
            'cents' => $amount,
            'label' => $this->formatMoney($amount),
        ];
    }

    private function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function resolvePixKeyTypeLabel(string $pixKeyType): string
    {
        $option = collect($this->pixKeyTypeOptions())
            ->firstWhere('value', $pixKeyType);

        return is_array($option) ? ($option['label'] ?? $pixKeyType) : $pixKeyType;
    }

    private function maskPixKey(string $value): string
    {
        $cleanValue = trim($value);

        if ($cleanValue === '') {
            return 'Não informado';
        }

        if (strlen($cleanValue) <= 4) {
            return str_repeat('*', strlen($cleanValue));
        }

        return str_repeat('*', strlen($cleanValue) - 4).substr($cleanValue, -4);
    }

    private function formatMoney(int $amount): string
    {
        return 'R$ '.number_format($amount / 100, 2, ',', '.');
    }

    private function formatStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'AWAITING_CONFIRMATION' => 'Aguardando confirmação',
            'AWAITING_PIN' => 'Aguardando PIN',
            'INITIATING' => 'Iniciando',
            'CONFIRMED' => 'Confirmado',
            'FAILED' => 'Falhou',
            'BLOCKED' => 'Bloqueado',
            'EXPIRED' => 'Expirado',
            default => ucfirst(strtolower($status)),
        };
    }

    private function convertAmountToCents(string $amount): int
    {
        $amount = preg_replace('/[R$\s]/', '', $amount);

        if (str_contains($amount, ',')) {
            $amount = str_replace('.', '', $amount);
            $amount = str_replace(',', '.', $amount);
        }

        $amountFloat = (float) $amount;

        if ($amountFloat < 0.01) {
            throw new \RuntimeException('O valor deve ser pelo menos R$ 0,01');
        }

        return (int) round($amountFloat * 100);
    }

    private function extractAmount(array $response, array $paths): int
    {
        foreach ($paths as $path) {
            $value = data_get($response, $path);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return 0;
    }
}

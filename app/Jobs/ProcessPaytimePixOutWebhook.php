<?php

namespace App\Jobs;

use App\Models\PixPayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaytimePixOutWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        $event = $this->payload['event'] ?? null;

        if (! in_array($event, ['new-transfer-pix-out', 'updated-transfer-pix-out'], true)) {
            Log::warning('Webhook PIX OUT ignorado: evento não reconhecido', ['event' => $event]);

            return;
        }

        $data = is_array($this->payload['data'] ?? null) ? $this->payload['data'] : [];
        $pixPayoutRequest = $this->resolveRequest($data);

        if (! $pixPayoutRequest) {
            Log::warning('Webhook PIX OUT ignorado: solicitação não encontrada', [
                'event' => $event,
                'init_id' => data_get($data, 'init_id'),
                'transaction_id' => data_get($data, '_id') ?? data_get($data, 'transaction_id'),
            ]);

            return;
        }

        $status = $this->normalizeStatus((string) data_get($data, 'status', ''));
        $update = [
            'webhook_payload' => $this->payload,
            'last_error' => null,
        ];

        if ($gatewayTransactionId = $this->resolveGatewayTransactionId($data)) {
            $update['gateway_transaction_id'] = $gatewayTransactionId;
        }

        if ($gatewayAuthorization = data_get($data, 'gateway_authorization')) {
            $update['gateway_authorization'] = is_scalar($gatewayAuthorization) ? (string) $gatewayAuthorization : null;
        }

        if ($status !== null) {
            $update['status'] = $status;

            if ($status === 'confirmed' && $pixPayoutRequest->confirmed_at === null) {
                $update['confirmed_at'] = now();
            }
        }

        if ($status === 'failed') {
            $update['last_error'] = $this->resolveFailureReason($data);
        }

        $pixPayoutRequest->update($update);

        Log::info('Webhook PIX OUT recebido via Paytime', [
            'event' => $event,
            'payout_request_id' => $pixPayoutRequest->id,
            'init_id' => $pixPayoutRequest->init_id,
            'gateway_transaction_id' => $pixPayoutRequest->gateway_transaction_id,
            'status' => $pixPayoutRequest->status,
        ]);
    }

    private function resolveRequest(array $data): ?PixPayoutRequest
    {
        $initId = $this->resolveInitId($data);
        $gatewayTransactionId = $this->resolveGatewayTransactionId($data);

        if (is_string($initId) && $initId !== '') {
            $request = PixPayoutRequest::query()->where('init_id', $initId)->first();

            if ($request) {
                return $request;
            }
        }

        if ($gatewayTransactionId !== null) {
            return PixPayoutRequest::query()->where('gateway_transaction_id', $gatewayTransactionId)->first();
        }

        return null;
    }

    private function resolveInitId(array $data): ?string
    {
        $value = data_get($data, 'init_id');

        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }

    private function resolveGatewayTransactionId(array $data): ?string
    {
        foreach (['_id', 'id', 'transaction_id', 'external_id'] as $key) {
            $value = data_get($data, $key);

            if (is_scalar($value) && $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    private function normalizeStatus(string $status): ?string
    {
        return match (strtoupper(trim($status))) {
            'PAID', 'APPROVED', 'CONFIRMED', 'SUCCESS', 'PROCESSING', 'COMPLETED' => 'confirmed',
            'FAILED', 'CANCELED', 'CANCELLED' => 'failed',
            'BLOCKED' => 'blocked',
            'EXPIRED' => 'expired',
            default => null,
        };
    }

    private function resolveFailureReason(array $data): ?string
    {
        $reason = data_get($data, 'message')
            ?? data_get($data, 'error')
            ?? data_get($data, 'reason')
            ?? data_get($data, 'status_detail');

        return is_string($reason) && trim($reason) !== '' ? trim($reason) : 'Webhook de PIX OUT sinalizou falha.';
    }
}

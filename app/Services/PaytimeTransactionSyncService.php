<?php

namespace App\Services;

use App\Models\PaytimeTransaction;
use Carbon\Carbon;

class PaytimeTransactionSyncService
{
    public function sync(array $data, array $context = []): ?PaytimeTransaction
    {
        $externalId = $this->resolveExternalId($data);

        if ($externalId === null) {
            return null;
        }

        $transaction = PaytimeTransaction::firstOrNew([
            'external_id' => $externalId,
        ]);

        $transaction->fill($this->mapAttributes($data, $context));

        if (! $transaction->exists) {
            $transaction->created_at = $this->resolveDate(
                $context['created_at'] ?? $data['created_at'] ?? $context['event_date'] ?? null
            ) ?? now();
        }

        $transaction->save();

        return $transaction;
    }

    public function syncWebhookPayload(array $payload): ?PaytimeTransaction
    {
        return $this->sync($payload['data'] ?? [], [
            'metadata' => $payload,
            'created_at' => $payload['data']['created_at'] ?? ($payload['event_date'] ?? null),
        ]);
    }

    private function mapAttributes(array $data, array $context = []): array
    {
        $customer = $data['customer'] ?? [];
        $acquirer = $data['acquirer'] ?? [];

        return [
            'establishment_id' => $data['establishment']['id'] ?? ($data['establishment_id'] ?? ($context['default_establishment_id'] ?? null)),
            'type' => $data['type'] ?? ($context['default_type'] ?? 'UNKNOWN'),
            'status' => $data['status'] ?? 'UNKNOWN',
            'amount' => $data['amount'] ?? 0,
            'original_amount' => $data['original_amount'] ?? ($data['amount'] ?? 0),
            'fees' => $data['fees'] ?? 0,
            'installments' => $data['installments'] ?? 1,
            'gateway_key' => $data['gateway_key'] ?? ($acquirer['gateway_key'] ?? null),
            'authorization_code' => $data['gateway_authorization'] ?? ($data['authorization_code'] ?? ($acquirer['name'] ?? null)),
            'scheduled_at' => $this->resolveDate($data['scheduled_at'] ?? null),
            'expiration_at' => $this->resolveDate($data['expiration_at'] ?? null),
            'paid_at' => $this->resolveDate($data['paid_at'] ?? null),
            'customer_name' => $this->resolveCustomerName($customer),
            'customer_document' => $customer['document'] ?? ($data['customer_document'] ?? null),
            'metadata' => $context['metadata'] ?? $data,
        ];
    }

    private function resolveExternalId(array $data): ?string
    {
        $externalId = $data['_id'] ?? ($data['id'] ?? null);

        if (! is_scalar($externalId) || $externalId === '') {
            return null;
        }

        return (string) $externalId;
    }

    private function resolveCustomerName(array $customer): ?string
    {
        $firstName = trim((string) ($customer['first_name'] ?? ''));
        $lastName = trim((string) ($customer['last_name'] ?? ''));
        $fullName = trim($firstName.' '.$lastName);

        if ($fullName !== '') {
            return $fullName;
        }

        $name = trim((string) ($customer['name'] ?? ''));

        return $name !== '' ? $name : null;
    }

    private function resolveDate(mixed $value): ?Carbon
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
}

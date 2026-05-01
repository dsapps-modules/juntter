<?php

namespace Tests\Unit;

use App\Jobs\ProcessPaytimeTransactionWebhook;
use App\Models\PaytimeTransaction;
use App\Services\PaytimeTransactionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaytimeTransactionWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_a_new_pagseguro_transaction_from_the_webhook_payload(): void
    {
        $payload = [
            'event' => 'new-pagseguro-transaction',
            'event_date' => '2026-04-14T12:00:00.000Z',
            'data' => [
                '_id' => 'transaction-123',
                'status' => 'PAID',
                'amount' => 15750,
                'original_amount' => 16000,
                'fees' => 250,
                'type' => 'CREDIT',
                'installments' => 1,
                'gateway_key' => 'gateway-abc',
                'gateway_authorization' => 'AUTH-999',
                'scheduled_at' => '2026-04-14T11:30:00.000Z',
                'expiration_at' => '2026-04-20T23:59:59.000Z',
                'paid_at' => '2026-04-14T12:05:00.000Z',
                'establishment' => [
                    'id' => 155463,
                ],
                'customer' => [
                    'first_name' => 'Joao',
                    'last_name' => 'Silva',
                    'document' => '10068114004',
                ],
                'acquirer' => [
                    'name' => 'PAGSEGURO',
                    'gateway_key' => 'gateway-from-acquirer',
                ],
            ],
        ];

        $service = app(PaytimeTransactionSyncService::class);
        $service->syncWebhookPayload($payload);

        $transaction = PaytimeTransaction::query()->first();

        $this->assertNotNull($transaction);
        $this->assertSame('transaction-123', $transaction->external_id);
        $this->assertSame(155463, (int) $transaction->establishment_id);
        $this->assertSame('CREDIT', $transaction->type);
        $this->assertSame('PAID', $transaction->status);
        $this->assertSame(15750, $transaction->amount);
        $this->assertSame(16000, $transaction->original_amount);
        $this->assertSame(250, $transaction->fees);
        $this->assertSame(1, $transaction->installments);
        $this->assertSame('gateway-abc', $transaction->gateway_key);
        $this->assertSame('AUTH-999', $transaction->authorization_code);
        $this->assertSame('Joao Silva', $transaction->customer_name);
        $this->assertSame('10068114004', $transaction->customer_document);
        $this->assertSame('2026-04-14 11:30:00', $transaction->scheduled_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-20 23:59:59', $transaction->expiration_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-14 12:05:00', $transaction->paid_at?->format('Y-m-d H:i:s'));
        $this->assertSame($payload, $transaction->metadata);
    }

    public function test_it_updates_an_existing_transaction_when_a_new_payload_arrives(): void
    {
        PaytimeTransaction::query()->create([
            'external_id' => 'transaction-123',
            'establishment_id' => 155463,
            'type' => 'CREDIT',
            'status' => 'PENDING',
            'amount' => 1000,
            'original_amount' => 1000,
            'fees' => 0,
            'installments' => 1,
            'metadata' => ['event' => 'seed'],
        ]);

        $payload = [
            'event' => 'updated-pagseguro-transaction',
            'event_date' => '2026-04-14T13:00:00.000Z',
            'data' => [
                '_id' => 'transaction-123',
                'status' => 'PAID',
                'amount' => 15750,
                'original_amount' => 16000,
                'fees' => 250,
                'type' => 'CREDIT',
                'installments' => 1,
                'customer' => [
                    'first_name' => 'Joao',
                    'last_name' => 'Silva',
                    'document' => '10068114004',
                ],
            ],
        ];

        $service = app(PaytimeTransactionSyncService::class);
        $service->syncWebhookPayload($payload);

        $this->assertSame(1, PaytimeTransaction::query()->count());

        $transaction = PaytimeTransaction::query()->firstOrFail();

        $this->assertSame('PAID', $transaction->status);
        $this->assertSame(15750, $transaction->amount);
        $this->assertSame(16000, $transaction->original_amount);
        $this->assertSame(250, $transaction->fees);
        $this->assertSame($payload, $transaction->metadata);
    }

    public function test_it_stores_a_new_sub_transaction_from_the_webhook_payload(): void
    {
        $payload = [
            'event' => 'new-sub-transaction',
            'event_date' => '2026-04-30T17:05:53.107Z',
            'data' => [
                '_id' => 'sub-transaction-123',
                'status' => 'PENDING',
                'amount' => 1005,
                'original_amount' => 1017,
                'fees' => 12,
                'type' => 'CREDIT',
                'gateway_key' => 'gateway-abc',
                'gateway_authorization' => 'PAYTIME',
                'installments' => 1,
                'establishment' => [
                    'id' => 155463,
                ],
                'customer' => [
                    'first_name' => 'Joao',
                    'last_name' => 'Silva',
                    'document' => '10068114004',
                ],
            ],
        ];

        $service = app(PaytimeTransactionSyncService::class);
        $service->syncWebhookPayload($payload);

        $transaction = PaytimeTransaction::query()->first();

        $this->assertNotNull($transaction);
        $this->assertSame('sub-transaction-123', $transaction->external_id);
        $this->assertSame(155463, (int) $transaction->establishment_id);
        $this->assertSame('CREDIT', $transaction->type);
        $this->assertSame('PENDING', $transaction->status);
        $this->assertSame(1005, $transaction->amount);
        $this->assertSame(1017, $transaction->original_amount);
        $this->assertSame(12, $transaction->fees);
        $this->assertSame(1, $transaction->installments);
        $this->assertSame('gateway-abc', $transaction->gateway_key);
        $this->assertSame('PAYTIME', $transaction->authorization_code);
        $this->assertSame('Joao Silva', $transaction->customer_name);
        $this->assertSame('10068114004', $transaction->customer_document);
        $this->assertSame($payload, $transaction->metadata);
    }

    public function test_it_processes_new_sub_transactions_in_the_job_handler(): void
    {
        $payload = [
            'event' => 'new-sub-transaction',
            'data' => [
                '_id' => 'sub-transaction-123',
                'status' => 'PENDING',
            ],
        ];

        $job = new ProcessPaytimeTransactionWebhook($payload);

        $service = $this->createMock(PaytimeTransactionSyncService::class);
        $service->expects($this->once())
            ->method('syncWebhookPayload')
            ->with($payload)
            ->willReturn(null);

        $job->handle($service);

        $this->assertTrue(true);
    }
}

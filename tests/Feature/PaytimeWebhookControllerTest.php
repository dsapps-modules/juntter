<?php

namespace Tests\Feature;

use App\Jobs\ProcessPaytimeBilletStatusChange;
use App\Jobs\ProcessPaytimeTransactionWebhook;
use App\Models\PaytimeEstablishment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaytimeWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.paytime.webhook_user', 'webhook-user');
        config()->set('services.paytime.webhook_pass', 'webhook-pass');
    }

    public function test_it_dispatches_the_billet_handler_from_the_single_paytime_webhook_route(): void
    {
        Queue::fake();

        $response = $this
            ->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/webhook/paytime', [
                'event' => 'updated-billet-status',
                'data' => [
                    '_id' => 'billet-123',
                    'status' => 'PAID',
                ],
            ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Paytime webhook received',
        ]);

        Queue::assertPushed(ProcessPaytimeBilletStatusChange::class, function (ProcessPaytimeBilletStatusChange $job): bool {
            return ($job->payload['event'] ?? null) === 'update-billet-status';
        });
    }

    public function test_it_dispatches_the_pagseguro_transaction_handler_from_the_single_paytime_webhook_route(): void
    {
        Queue::fake();

        $response = $this
            ->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/webhook/paytime', [
                'event' => 'new-pagseguro-transaction',
                'data' => [
                    '_id' => 'transaction-123',
                    'status' => 'PENDING',
                ],
            ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Paytime webhook received',
        ]);

        Queue::assertPushed(ProcessPaytimeTransactionWebhook::class, function (ProcessPaytimeTransactionWebhook $job): bool {
            return ($job->payload['event'] ?? null) === 'new-pagseguro-transaction';
        });
    }

    public function test_it_dispatches_the_sub_transaction_handler_from_the_single_paytime_webhook_route(): void
    {
        Queue::fake();
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Paytime webhook received for new-sub-transaction'
                    && ($context['event'] ?? null) === 'new-sub-transaction'
                    && ($context['transaction_id'] ?? null) === 'transaction-456'
                    && (int) ($context['establishment_id'] ?? 0) === 127700
                    && ($context['status'] ?? null) === 'PENDING'
                    && (int) ($context['amount'] ?? 0) === 1005
                    && in_array('establishment', $context['data_keys'] ?? [], true);
            })
            ->andReturnNull();

        $response = $this
            ->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/webhook/paytime', [
                'event' => 'new-sub-transaction',
                'data' => [
                    '_id' => 'transaction-456',
                    'status' => 'PENDING',
                    'amount' => 1005,
                    'establishment' => [
                        'id' => 127700,
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Paytime webhook received',
        ]);

        Queue::assertPushed(ProcessPaytimeTransactionWebhook::class, function (ProcessPaytimeTransactionWebhook $job): bool {
            return ($job->payload['event'] ?? null) === 'new-sub-transaction';
        });
    }

    public function test_it_dispatches_the_updated_sub_transaction_handler_from_the_single_paytime_webhook_route(): void
    {
        Queue::fake();
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Paytime webhook received for updated-sub-transaction'
                    && ($context['event'] ?? null) === 'updated-sub-transaction'
                    && ($context['transaction_id'] ?? null) === 'transaction-789'
                    && (int) ($context['establishment_id'] ?? 0) === 127700
                    && ($context['status'] ?? null) === 'PAID'
                    && (int) ($context['amount'] ?? 0) === 100
                    && in_array('establishment', $context['data_keys'] ?? [], true);
            })
            ->andReturnNull();

        $response = $this
            ->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/webhook/paytime', [
                'event' => 'updated-sub-transaction',
                'data' => [
                    '_id' => 'transaction-789',
                    'status' => 'PAID',
                    'amount' => 100,
                    'type' => 'PIX',
                    'establishment' => [
                        'id' => 127700,
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Paytime webhook received',
        ]);

        Queue::assertPushed(ProcessPaytimeTransactionWebhook::class, function (ProcessPaytimeTransactionWebhook $job): bool {
            return ($job->payload['event'] ?? null) === 'updated-sub-transaction'
                && ($job->payload['data']['_id'] ?? null) === 'transaction-789';
        });
    }

    public function test_it_returns_ok_for_unsupported_events_without_dispatching_jobs(): void
    {
        Queue::fake();

        $response = $this
            ->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/webhook/paytime', [
                'event' => 'updated-establishment-gateway',
                'data' => [
                    'id' => 123,
                ],
            ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Paytime webhook received',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_it_rejects_requests_without_basic_auth(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/webhook/paytime', [
            'event' => 'new-establishment',
            'data' => [
                'id' => 123,
            ],
        ]);

        $response->assertUnauthorized();
        Queue::assertNothingPushed();
    }

    public function test_it_persists_updated_establishment_data_from_the_webhook_route(): void
    {
        config()->set('queue.default', 'sync');

        PaytimeEstablishment::query()->create([
            'id' => 155463,
            'first_name' => 'Empresa Antiga',
            'last_name' => 'LTDA',
            'fantasy_name' => 'Empresa Antiga LTDA',
            'document' => '11111111000111',
            'email' => 'antiga@example.com',
            'phone_number' => '11999990000',
            'active' => false,
            'status' => 'REVIEW',
            'risk' => 'MEDIUM',
            'category' => 'Antiga',
            'code' => 'OLD123',
            'revenue' => 1000,
            'address_json' => [
                'street' => 'Rua Antiga',
                'number' => '1',
            ],
            'responsible_json' => [
                'name' => 'Responsável Antigo',
            ],
        ]);

        $response = $this
            ->withBasicAuth('webhook-user', 'webhook-pass')
            ->postJson('/api/webhook/paytime', [
                'event' => 'updated-establishment-data',
                'data' => [
                    'id' => 155463,
                    'type' => 'COMPANY',
                    'first_name' => 'Empresa Nova',
                    'last_name' => 'LTDA',
                    'fantasy_name' => 'Empresa Nova LTDA',
                    'document' => '22222222000122',
                    'email' => 'nova@example.com',
                    'phone_number' => '11988887777',
                    'active' => true,
                    'status' => 'ACTIVE',
                    'risk' => 'LOW',
                    'category' => 'Nova',
                    'code' => 'NEW456',
                    'revenue' => 2500,
                    'address' => [
                        'street' => 'Rua Nova',
                        'number' => '99',
                        'neighborhood' => 'Centro',
                        'city' => 'Sao Paulo',
                        'state' => 'SP',
                        'zip_code' => '01001000',
                    ],
                    'responsible' => [
                        'name' => 'Responsavel Novo',
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Paytime webhook received',
        ]);

        $establishment = PaytimeEstablishment::query()->findOrFail(155463);

        $this->assertSame('Empresa Nova', $establishment->first_name);
        $this->assertSame('Empresa Nova LTDA', $establishment->fantasy_name);
        $this->assertSame('nova@example.com', $establishment->email);
        $this->assertTrue($establishment->active);
        $this->assertSame('ACTIVE', $establishment->status);
        $this->assertSame('LOW', $establishment->risk);
        $this->assertSame('Rua Nova', $establishment->address_json['street']);
        $this->assertSame('Responsavel Novo', $establishment->responsible_json['name']);
    }
}

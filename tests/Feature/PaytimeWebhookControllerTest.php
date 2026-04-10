<?php

namespace Tests\Feature;

use App\Jobs\ProcessPaytimeBilletStatusChange;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaytimeWebhookControllerTest extends TestCase
{
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
}

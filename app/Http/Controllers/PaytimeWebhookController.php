<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCreatePaytimeEstablishment;
use App\Jobs\ProcessPaytimeBilletStatusChange;
use App\Jobs\ProcessPaytimeEstablishmentStatusChange;
use App\Jobs\ProcessPaytimeTransactionWebhook;
use App\Jobs\ProcessUpdatePaytimeEstablishmentData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class PaytimeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->isAuthorized($request)) {
            Log::warning('Paytime webhook request unauthorized', $payload);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $event = $payload['event'] ?? null;

        if (! is_string($event) || $event === '') {
            Log::warning('Paytime webhook request missing event', $payload);

            return response()->json(['message' => 'Event is required'], 422);
        }

        $this->dispatchEventHandler($event, $payload);

        return response()->json(['message' => 'Paytime webhook received'], 200);
    }

    private function dispatchEventHandler(string $event, array $payload): void
    {
        match ($event) {
            'new-billet' => $this->handleNewBillet($payload),
            'updated-billet-status' => $this->handleUpdatedBilletStatus($payload),
            'new-sub-split' => $this->handleNewSubSplit($payload),
            'canceled-sub-split' => $this->handleCanceledSubSplit($payload),
            'new-establishment' => $this->handleNewEstablishment($payload),
            'updated-establishment-status' => $this->handleUpdatedEstablishmentStatus($payload),
            'updated-establishment-gateway' => $this->handleUpdatedEstablishmentGateway($payload),
            'updated-establishment-data' => $this->handleUpdatedEstablishmentData($payload),
            'new-sub-transaction' => $this->handleNewSubTransaction($payload),
            'updated-sub-transaction' => $this->handleUpdatedSubTransaction($payload),
            'new-pagseguro-transaction' => $this->handleNewPagseguroTransaction($payload),
            'updated-pagseguro-transaction' => $this->handleUpdatedPagseguroTransaction($payload),
            'new-zoop-transaction' => $this->handleNewZoopTransaction($payload),
            'updated-zoop-transaction' => $this->handleUpdatedZoopTransaction($payload),
            default => $this->handleUnknownEvent($event, $payload),
        };
    }

    private function handleNewEstablishment(array $payload): void
    {
        Log::info('Paytime webhook received for new-establishment', ['event' => 'new-establishment']);
        Queue::push(new ProcessCreatePaytimeEstablishment($payload));
    }

    private function handleUpdatedEstablishmentStatus(array $payload): void
    {
        Log::info('Paytime webhook received for updated-establishment-status', ['event' => 'updated-establishment-status']);
        Queue::push(new ProcessPaytimeEstablishmentStatusChange($this->payloadWithEvent($payload, 'update-establishment-status')));
    }

    private function handleUpdatedEstablishmentGateway(array $payload): void
    {
        Log::info('Paytime webhook received for updated-establishment-gateway', ['event' => 'updated-establishment-gateway']);
    }

    private function handleUpdatedEstablishmentData(array $payload): void
    {
        Log::info('Paytime webhook received for updated-establishment-data', ['event' => 'updated-establishment-data']);
        Queue::push(new ProcessUpdatePaytimeEstablishmentData($this->payloadWithEvent($payload, 'update-establishment-data')));
    }

    private function handleNewSubTransaction(array $payload): void
    {
        Log::info('Paytime webhook received for new-sub-transaction', [
            'event' => 'new-sub-transaction',
            'transaction_id' => $payload['data']['_id'] ?? null,
            'establishment_id' => $payload['data']['establishment']['id'] ?? ($payload['data']['establishment_id'] ?? null),
            'status' => $payload['data']['status'] ?? null,
            'amount' => $payload['data']['amount'] ?? null,
            'created_at' => $payload['data']['created_at'] ?? null,
            'data_keys' => array_keys($payload['data'] ?? []),
        ]);
        Queue::push(new ProcessPaytimeTransactionWebhook($this->payloadWithEvent($payload, 'new-sub-transaction')));
    }

    private function handleUpdatedSubTransaction(array $payload): void
    {
        Log::info('Paytime webhook received for updated-sub-transaction', ['event' => 'updated-sub-transaction']);
    }

    private function handleNewBillet(array $payload): void
    {
        Log::info('Paytime webhook received for new-billet', ['event' => 'new-billet']);
    }

    private function handleUpdatedBilletStatus(array $payload): void
    {
        Log::info('Paytime webhook received for updated-billet-status', ['event' => 'updated-billet-status']);
        Queue::push(new ProcessPaytimeBilletStatusChange($this->payloadWithEvent($payload, 'update-billet-status')));
    }

    private function handleNewSubSplit(array $payload): void
    {
        Log::info('Paytime webhook received for new-sub-split', ['event' => 'new-sub-split']);
    }

    private function handleCanceledSubSplit(array $payload): void
    {
        Log::info('Paytime webhook received for canceled-sub-split', ['event' => 'canceled-sub-split']);
    }

    private function handleNewPagseguroTransaction(array $payload): void
    {
        Log::info('Paytime webhook received for new-pagseguro-transaction', ['event' => 'new-pagseguro-transaction']);
        Queue::push(new ProcessPaytimeTransactionWebhook($payload));
    }

    private function handleUpdatedPagseguroTransaction(array $payload): void
    {
        Log::info('Paytime webhook received for updated-pagseguro-transaction', ['event' => 'updated-pagseguro-transaction']);
        Queue::push(new ProcessPaytimeTransactionWebhook($payload));
    }

    private function handleNewZoopTransaction(array $payload): void
    {
        Log::info('Paytime webhook received for new-zoop-transaction', ['event' => 'new-zoop-transaction']);
    }

    private function handleUpdatedZoopTransaction(array $payload): void
    {
        Log::info('Paytime webhook received for updated-zoop-transaction', ['event' => 'updated-zoop-transaction']);
    }

    private function handleUnknownEvent(string $event, array $payload): void
    {
        Log::warning('Paytime webhook received with unsupported event', [
            'event' => $event,
            'payload' => $payload,
        ]);
    }

    private function payloadWithEvent(array $payload, string $event): array
    {
        $payload['event'] = $event;

        return $payload;
    }

    protected function isAuthorized(Request $request): bool
    {
        $user = config('services.paytime.webhook_user');
        $pass = config('services.paytime.webhook_pass');

        $hasAuth = $request->getUser() && $request->getPassword();
        $passKey = $request->getPassword();
        $userKey = $request->getUser();

        return $hasAuth &&
            $userKey === $user &&
            $passKey === $pass;
    }
}

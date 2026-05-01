<?php

namespace App\Jobs;

use App\Services\PaytimeTransactionSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaytimeTransactionWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function handle(PaytimeTransactionSyncService $paytimeTransactionSyncService): void
    {
        $event = $this->payload['event'] ?? null;

        if (! in_array($event, ['new-sub-transaction', 'new-pagseguro-transaction', 'updated-pagseguro-transaction'], true)) {
            Log::warning('Webhook ignorado: evento não reconhecido', ['event' => $event]);

            return;
        }

        $transaction = $paytimeTransactionSyncService->syncWebhookPayload($this->payload);

        if ($transaction === null) {
            Log::warning('Webhook de transação ignorado: external_id ausente', [
                'event' => $event,
                'payload' => $this->payload,
            ]);

            return;
        }

        Log::info('Transação PagSeguro recebida via webhook Paytime', [
            'event' => $event,
            'external_id' => $transaction->external_id,
            'status' => $transaction->status,
        ]);
    }
}

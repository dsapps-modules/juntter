<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaytimeEstablishmentStatusChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $event = $this->payload['event'] ?? null;
        $data = $this->payload['data'] ?? [];

        if (! in_array($event, ['updated-establishment-status', 'update-establishment-status'], true)) {
            Log::warning('Webhook ignorado: evento não reconhecido', ['event' => $event]);

            return;
        }

        Log::info('Atualização de status do estabelecimento recebida via webhook Paytime', [
            'transaction_id' => $data['_id'] ?? null,
            'status' => $data['status'] ?? null,
            'valor' => $data['amount'] ?? null,
            'nome_cliente' => $data['customer']['first_name'] ?? null,
            'sobrenome_cliente' => $data['customer']['last_name'] ?? null,
            'email' => $data['customer']['email'] ?? null,
        ]);
    }
}

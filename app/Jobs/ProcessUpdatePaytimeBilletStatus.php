<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPaytimeBilletStatusChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $event = $this->payload['event'] ?? null;
        $data  = $this->payload['data'] ?? [];

        if ($event !== 'update-billet-status') {
            Log::warning("Webhook ignorado: evento não reconhecido", ['event' => $event]);
            return;
        }

        Log::info("🔔 Atualização de boleto recebida via webhook Paytime", [
            'transaction_id' => $data['_id'] ?? null,
            'status' => $data['status'] ?? null,
            'valor' => $data['amount'] ?? null,
            'nome_cliente' => $data['customer']['first_name'] ?? null,
            'sobrenome_cliente' => $data['customer']['last_name'] ?? null,
            'email' => $data['customer']['email'] ?? null,
        ]);

        // Aqui você pode adicionar lógica para:
        // - Registrar a transação em uma tabela
        // - Atualizar o status de uma venda
        // - Relacionar o pagamento ao cliente
        // - Salvar o log antifraude
        // - Disparar notificações
    }
}

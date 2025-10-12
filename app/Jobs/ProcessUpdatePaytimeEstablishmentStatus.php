<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPaytimeEstablishmentStatusChange implements ShouldQueue
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

        if ($event !== 'update-establishment-status') {
            Log::warning("Webhook ignorado: evento não reconhecido", ['event' => $event]);
            return;
        }

        Log::info("🔔 Atualização de status do estabelecimento recebida via webhook Paytime", [
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

/*
    Payload Example 

    {
        "event":"new-sub-transaction",
        "event_date":"2025-04-30T17:05:53.107Z",
        "data":{
            "_id":"681258717903c84441e0e823",
            "status":"PENDING",
            "amount":1005,
            "original_amount":1017,
            "fees":12,
            "type":"CREDIT",
            "gateway_key":"849c88d8-8599-449d-8b0e-598036c6f014",
            "gateway_authorization":"PAYTIME",
            "card":{
                "brand_name":"MASTERCARD",
                "first4_digits":"5200",
                "last4_digits":"1005",
                "expiration_month":"12",
                "expiration_year":"2026",
                "holder_name":"JOÃO DA SILVA",
                "_id":"681258707903c84441e0e80b"
            },
            "installments":1,
            "customer":{
                "first_name":"João",
                "last_name":"da Silva",
                "document":"10068114004",
                "phone":"31992831124",
                "email":"emaildocliente@gmail.com",
                "address":{
                    "street":"Rua Maria dos Desenvolvedores",
                    "number":"0101",
                    "complement":"Debug",
                    "neighborhood":"Bairro Deploy",
                    "city":"Vitória",
                    "state":"ES",
                    "zip_code":"29000000"
                },
                "_id":"681258707903c84441e0e80c"
            },
            "antifraud":[
                {
                    "analyse_status":"NO_ANALYSED",
                    "_id":"681258717903c84441e0e820"
                }
            ],
            "point_of_sale":{
                "type":"ONLINE",
                "identification_type":"API"
            },
            "acquirer":{
                "name":"PAGSEGURO",
                "acquirer_nsu":123456789123,
                "gateway_key":"354F9DD8-39AB-417D-B543-558126B347E9",
                "mid":"100000000000002",
                "_id":"681258717903c84441e0e822"
            },
            "created_at":"2025-04-30T17:05:52.924Z",
            "pix":null
        }
    }

*/
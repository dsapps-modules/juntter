<?php

namespace App\Jobs;

use App\Services\PaytimePricingCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUpdatePaytimeEstablishmentData implements ShouldQueue
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
        $data = $this->payload['data'] ?? [];

        if (! in_array($event, ['updated-establishment-data', 'update-establishment-data'], true)) {
            Log::warning('Webhook ignorado: evento não reconhecido', ['event' => $event]);

            return;
        }

        $establishmentId = $data['id'] ?? $data['_id'] ?? null;

        if (! is_numeric($establishmentId)) {
            Log::warning('Webhook ignorado: estabelecimento sem identificador válido', [
                'event' => $event,
                'payload_keys' => array_keys($data),
            ]);

            return;
        }

        app(PaytimePricingCacheService::class)->persistPricingSnapshot($data);

        Log::info('Estabelecimento atualizado via webhook Paytime', [
            'event' => $event,
            'establishment_id' => (int) $establishmentId,
            'document' => $data['document'] ?? null,
            'status' => $data['status'] ?? null,
        ]);
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

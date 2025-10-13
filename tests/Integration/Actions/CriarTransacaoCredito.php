<?php

namespace Tests\Integration\Actions;

use Tests\TestCase;
use App\Services\CreditoService;
use App\Services\ApiClientService;

// teste individual do serviço CreditoService 
// php artisan test tests/Integration/Actions/CriarTransacaoCredito.php

class CriarTransacaoCredito extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = app(ApiClientService::class);
    }

    /** @test */
    public function criar_transacao_credito()
    {
        // $this->markTestSkipped('This test is skipped because it requires a real API call.');
        $service = new CreditoService($this->apiClient);

        $dados = [
            "payment_type" => "CREDIT",
            "amount" => 35000, // R$ 350,00
            "interest" => "CLIENT",
            "installments" => 5,
            "client" => [
                "first_name" => "João",
                "last_name" => "da Silva",
                "document" => "10068114001",
                "phone" => "31992876543",
                "email" => "emaildocliente@gmail.com",
                "address" => [
                    "street" => "Rua Maria dos Desenvolvedores",
                    "number" => "0101",
                    "complement" => "Debug",
                    "neighborhood" => "Bairro Deploy",
                    "city" => "Vitória",
                    "state" => "ES",
                    "country" => "BR",
                    "zip_code" => "29000000"
                ]
            ],
            "card" => [
                "holder_name" => "João da Silva",
                "holder_document" => "58246374079",
                "card_number" => "5200000000001005",
                "expiration_month" => 12,
                "expiration_year" => 2026,
                "security_code" => "123"
            ],
            "session_id" => "IdGeradoSDK",
            "extra_headers" => [
                "establishment_id" => "155161"
            ]
        ];

        $response = $service->criarTransacaoCredito($dados);
        
        dump('RESPOSTA CRIAÇÃO CRÉDITO:', $response);
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('_id', $response);

        return $response['_id'];
    }

}
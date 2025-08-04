<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Services\CreditoService;
use App\Services\ApiClientService;

class CreditoServiceIntegrationTest extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = app(ApiClientService::class);
    }

    /** @test */
    public function criar_transacao_credito_real()
    {
        $service = new CreditoService($this->apiClient);

        $dados = [
            "payment_type" => "CREDIT",
            "amount" => 8005,
            "installments" => 1,
            "interest" => "CLIENT",
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
            "session_id" => "IdGeradoSDK"
        ];

        $response = $service->criarTransacaoCredito($dados);

        dump($response);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('transaction_id', $response);

        return $response['transaction_id'];
    }

    /**
     * @depends criar_transacao_credito_real
     */
    public function estornar_transacao_credito_real(string $id)
    {
        $service = new CreditoService($this->apiClient);

        $dados = [
            "transaction_amount" => 1000.00,
            "transaction_type" => "credit"
        ];

        $response = $service->estornarTransacao($id, $dados);

        dump($response);
        $this->assertIsArray($response);
    }
}

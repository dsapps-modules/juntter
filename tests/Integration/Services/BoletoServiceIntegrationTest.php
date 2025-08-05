<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Services\BoletoService;
use App\Services\ApiClientService;

// teste individual do serviço BoletoService 
// php artisan test tests/Integration/Services/BoletoServiceIntegrationTest.php

class BoletoServiceIntegrationTest extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = app(ApiClientService::class);
    }

    /** @test */
    public function gerar_boleto()
    {
        $service = new BoletoService($this->apiClient);

        $dados = [
            "amount" => 22233,
            "expiration" => "2025-04-19",
            "payment_limit_date" => "2025-04-27",
            "recharge" => true,
            "client" => [
                "first_name" => "Antonio",
                "last_name" => "Francisco",
                "document" => "05628435553",
                "email" => "antonio@emaildocliente.com",
                "address" => [
                    "street" => "Av Longe",
                    "number" => "10",
                    "neighborhood" => "Bairro distante",
                    "complement" => "Perto da zona",
                    "city" => "Goiania",
                    "state" => "GO",
                    "zip_code" => "29163321"
                ]
            ],
            "instruction" => [
                "booklet" => false,
                "description" => "Venda por Boleto",
                "late_fee" => [
                    "mode" => "PERCENTAGE",
                    "amount" => 1
                ],
                "interest" => [
                    "mode" => "MONTHLY_PERCENTAGE",
                    "amount" => 1
                ],
                "discount" => [
                    "mode" => "PERCENTAGE",
                    "amount" => 1,
                    "limit_date" => "2025-04-18"
                ]
            ],
            "extra_headers" => [
                "establishment_id" => "155102"
            ]
        ];

        $response = $service->gerarBoleto($dados);

        dump('RESPOSTA GERAÇÃO BOLETO:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_id', $response);
        $this->assertArrayHasKey('type', $response);
        $this->assertEquals('BILLET', $response['type']);
        $this->assertArrayHasKey('gateway_key', $response);
        $this->assertArrayHasKey('establishment_id', $response);
        $this->assertArrayHasKey('amount', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('expiration_at', $response);
        $this->assertArrayHasKey('client', $response);
    }


}
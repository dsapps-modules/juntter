<?php

namespace Tests\Integration\Actions;

use Tests\TestCase;
use App\Services\BoletoService;
use App\Services\ApiClientService;

// teste individual do serviço BoletoService 
// php artisan test tests/Integration/Actions/GerarBoleto.php

class GerarBoleto extends TestCase
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
            "amount" => 34567,
            "expiration" => date('Y-m-d', strtotime('+2 days')),
            "payment_limit_date" => date('Y-m-d', strtotime('+5 days')),
            "recharge" => true,
            "client" => [
                "first_name" => "Francisco",
                "last_name" => "Filho",
                "document" => "77327746048",
                "email" => "chico@cliente.com",
                "address" => [
                    "street" => "Av. Longe",
                    "number" => "10",
                    "neighborhood" => "Bairro distante",
                    "complement" => "Perto da zona",
                    "city" => "São Paulo",
                    "state" => "SP",
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
                    "limit_date" => date('Y-m-d', strtotime('+1 days'))
                ]
            ],
            "extra_headers" => [
                "establishment_id" => "155161"
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

        return $response['_id'];
    }

}
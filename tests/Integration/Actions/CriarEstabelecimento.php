<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Services\EstabelecimentoService;
use App\Services\ApiClientService;


class CriarEstabelecimento extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = app(ApiClientService::class);
    }

    /** @test */
    public function criacao_real_de_estabelecimento() // acesso negado
    {
        $service = new EstabelecimentoService($this->apiClient);

        $dados = [
            "type" => "BUSINESS",
            "activity_id" => 30,
            "notes" => "Observações sobre o Universo",
            "visited" => true,
            "responsible" => [
                "email" => "emaildoresponsavel@email.com",
                "document" => "823.509.930-60",
                "first_name" => "José Desenvolvedor",
                "phone" => "27998765431",
                "birthdate" => "2000-10-12"
            ],
            "address" => [
                "zip_code" => "29000000",
                "street" => "Rua dos Devs",
                "neighborhood" => "Bairro da Programação",
                "city" => "Vitória",
                "state" => "ES",
                "number" => "01"
            ],
            "first_name" => "DS",
            "last_name" => "Apps",
            "cnae" => "0111302",
            "document" => "64.096.162/0001-58",
            "phone_number" => "27992836213",
            "email" => "emaildodoecl@email.com",
            "birthdate" => "2022-01-01",
            "revenue" => 10000,
            "format" => "LTDA",
            "gmv" => 13000
        ];

        $response = $service->criarEstabelecimento($dados);
        echo json_encode($response, JSON_PRETTY_PRINT);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response); // esperando retorno com id
    }
}
<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Services\PixService;
use App\Services\ApiClientService;

// teste individual do serviço PixService 
// php artisan test tests/Integration/Services/PixServiceIntegrationTest.php


class PixServiceIntegrationTest extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = app(ApiClientService::class);
    }

    /** @test */
    public function criar_transacao_pix()
    {
        // $this->markTestSkipped('This test is skipped because it requires a real API call.');
        $service = new PixService($this->apiClient);

        $dados = [
            "payment_type" => "PIX",
            "amount" => 305,
            "interest" => "CLIENT",
            "client" => [
                "first_name" => "João",
                "last_name" => "da Silva",
                "document" => "10068114001",
                "phone" => "31992831122",
                "email" => "emaildocliente@gmail.com"
            ],
            "extra_headers" => [
                "establishment_id" => "155102"
            ]
        ];

        $response = $service->criarTransacaoPix($dados);
        // echo json_encode($response, JSON_PRETTY_PRINT);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_id', $response);

        return $response['_id'];
    }

    /**
     * @depends test_criar_transacao_pix
     */
    public function obter_qrcode_pix(string $id)
    {
        $service = new PixService($this->apiClient);
        $response = $service->obterQrCodePix($id);

        dump($response);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('qrcode', $response);
    }
}

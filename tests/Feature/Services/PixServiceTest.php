<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\PixService;
use App\Services\ApiClientService;

class PixServiceTest extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = $this->createMock(ApiClientService::class);
    }

    /** @test */
    public function pode_criar_transacao_pix_com_mock()
    {
        $this->apiClient->method('post')->willReturn(['_id' => 'abc123', 'expected_on' => now()]);
        $service = new PixService($this->apiClient);
        $response = $service->criarTransacaoPix([]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_id', $response);
    }

    /** @test */
    public function pode_obter_qrcode_pix_com_mock()
    {
        $this->apiClient->method('get')->willReturn(['qrcode' => 'codigo_qr']);
        $service = new PixService($this->apiClient);
        $response = $service->obterQrCodePix('abc123');

        $this->assertIsArray($response);
        $this->assertEquals('codigo_qr', $response['qrcode']);
    }
}

<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\CreditoService;
use App\Services\ApiClientService;

class CreditoServiceTest extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = $this->createMock(ApiClientService::class);
    }

    /** @test */
    public function pode_criar_transacao_credito_com_mock()
    {
        $this->apiClient->method('post')->willReturn(['transaction_id' => 'abc123']);
        $service = new CreditoService($this->apiClient);
        $response = $service->criarTransacaoCredito([]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('transaction_id', $response);
    }

    /** @test */
    public function pode_estornar_transacao_com_mock()
    {
        $this->apiClient->method('post')->willReturn(['status' => 'reversed']);
        $service = new CreditoService($this->apiClient);
        $response = $service->estornarTransacao('abc123', []);

        $this->assertIsArray($response);
        $this->assertEquals('reversed', $response['status']);
    }
}

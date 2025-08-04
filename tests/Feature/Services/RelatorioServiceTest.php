<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\RelatorioService;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\WithFaker;

class RelatorioServiceTest extends TestCase
{
    use WithFaker;

    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock do ApiClientService
        $this->apiClient = $this->createMock(ApiClientService::class);
    }

    /** @test */
    public function exemplo_de_teste_para_relatorioservice_pode_ser_executado()
    {
        $this->apiClient->method('get')->willReturn(['sucesso' => true]);
        $this->apiClient->method('post')->willReturn(['sucesso' => true]);

        $service = new RelatorioService($this->apiClient);
        $response = $service->gerarRelatorio(["mes" => "2024-07"]);

        $this->assertIsArray($response);
        $this->assertTrue($response['sucesso']);
    }
}

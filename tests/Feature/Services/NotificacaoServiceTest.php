<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\NotificacaoService;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\WithFaker;

class NotificacaoServiceTest extends TestCase
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
    public function exemplo_de_teste_para_notificacaoservice_pode_ser_executado()
    {
        $this->apiClient->method('get')->willReturn(['sucesso' => true]);
        $this->apiClient->method('post')->willReturn(['sucesso' => true]);

        $service = new NotificacaoService($this->apiClient);
        $response = $service->enviarNotificacao(["mensagem" => "Teste"]);

        $this->assertIsArray($response);
        $this->assertTrue($response['sucesso']);
    }
}

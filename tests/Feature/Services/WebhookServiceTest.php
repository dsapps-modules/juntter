<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\WebhookService;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\WithFaker;

class WebhookServiceTest extends TestCase
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
    public function exemplo_de_teste_para_webhookservice_pode_ser_executado()
    {
        $this->apiClient->method('get')->willReturn(['sucesso' => true]);
        $this->apiClient->method('post')->willReturn(['sucesso' => true]);

        $service = new WebhookService($this->apiClient);
        $response = $service->listarWebhooks();

        $this->assertIsArray($response);
        $this->assertTrue($response['sucesso']);
    }
}

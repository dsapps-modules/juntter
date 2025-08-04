<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\PagamentoService;
use App\Services\ApiClientService;
use Illuminate\Foundation\Testing\WithFaker;

class PagamentoServiceTest extends TestCase
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
    public function exemplo_de_teste_para_pagamentoservice_pode_ser_executado()
    {
        $this->apiClient->method('get')->willReturn(['sucesso' => true]);
        $this->apiClient->method('post')->willReturn(['sucesso' => true]);

        $service = new PagamentoService($this->apiClient);
        $response = $service->consultarPagamento("123");

        $this->assertIsArray($response);
        $this->assertTrue($response['sucesso']);
    }
}

<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Services\EstabelecimentoService;
use App\Services\ApiClientService;

class EstabelecimentoServiceTest extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = $this->createMock(ApiClientService::class);
    }

    /** @test */
    public function pode_listar_estabelecimentos()
    {
        $this->apiClient->method('get')->willReturn([
            ['id' => 1, 'nome' => 'Estabelecimento A'],
            ['id' => 2, 'nome' => 'Estabelecimento B']
        ]);

        $service = new EstabelecimentoService($this->apiClient);
        $response = $service->listarEstabelecimentos();

        $this->assertIsArray($response);
        $this->assertCount(2, $response);
    }
}

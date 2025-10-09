<?php

namespace Tests\Integration\Actions;

use Tests\TestCase;
use App\Services\TransacaoService;
use App\Services\ApiClientService;

// teste individual do serviço TransacaoService 
// php artisan test tests/Integration/Services/TransacaoServiceIntegrationTest.php

class ListarTransacoes extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = app(ApiClientService::class);
    }

    /** @test */
    public function listar_transacoes()
    {
        $service = new TransacaoService($this->apiClient);

        $filtros = [
            'perPage' => 10,
            'page' => 1
        ];

        $response = $service->listarTransacoes($filtros);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('page', $response);
        $this->assertArrayHasKey('perPage', $response);

        dump('Listar Transações:', $response);
    }

}
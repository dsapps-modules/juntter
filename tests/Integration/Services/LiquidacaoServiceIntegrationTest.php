<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Services\LiquidacaoService;
use App\Services\ApiClientService;

// teste individual do serviço LiquidacaoService 
// php artisan test tests/Integration/Services/LiquidacaoServiceIntegrationTest.php

class LiquidacaoServiceIntegrationTest extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = app(ApiClientService::class);
    }

    /** @test */
    public function listar_liquidacoes()
    {
        $service = new LiquidacaoService($this->apiClient);

        $filtros = [
            'perPage' => 10,
            'page' => 1
        ];

        $response = $service->listarLiquidacoes($filtros);

        dump('RESPOSTA LISTAR LIQUIDAÇÕES:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('page', $response);
        $this->assertArrayHasKey('perPage', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    /** @test */
    public function listar_liquidacoes_com_filtros()
    {
        $service = new LiquidacaoService($this->apiClient);

        $filtros = [
            'perPage' => 5,
            'page' => 1,
            'filters' => json_encode(['status' => 'PAID']),
            'sorters' => json_encode([
                ['column' => 'created_at', 'direction' => 'DESC']
            ])
        ];

        $response = $service->listarLiquidacoes($filtros);

        dump('RESPOSTA LISTAR LIQUIDAÇÕES COM FILTROS:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('page', $response);
        $this->assertArrayHasKey('perPage', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }
} 
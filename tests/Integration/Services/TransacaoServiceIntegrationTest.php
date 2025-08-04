<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Services\TransacaoService;
use App\Services\ApiClientService;

// teste individual do serviço TransacaoService 
// php artisan test tests/Integration/Services/TransacaoServiceIntegrationTest.php

class TransacaoServiceIntegrationTest extends TestCase
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
    }

    /** @test */
    public function listar_transacoes_com_filtros_avancados()
    {
        $service = new TransacaoService($this->apiClient);

        $filtros = [
            'filters' => json_encode([
                'status' => 'PENDING',
                'type' => 'CREDIT',
                'establishment.id' => 155102
            ]),
            'perPage' => 5,
            'page' => 1
        ];

        $response = $service->listarTransacoes($filtros);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('total', $response);
    }

    /** @test */
    public function detalhes_transacao()
    {
        $service = new TransacaoService($this->apiClient);

        // Primeiro lista as transações para pegar um ID real
        $filtros = [
            'perPage' => 1,
            'page' => 1
        ];

        $listaTransacoes = $service->listarTransacoes($filtros);
        
        $this->assertIsArray($listaTransacoes);
        $this->assertArrayHasKey('data', $listaTransacoes);
        $this->assertNotEmpty($listaTransacoes['data']);

        // Pega o ID da primeira transação encontrada
        $codigoTransacao = $listaTransacoes['data'][0]['_id'];

        // Agora busca os detalhes dessa transação
        $response = $service->detalhesTransacao($codigoTransacao);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_id', $response);
        $this->assertEquals($codigoTransacao, $response['_id']);
    }
} 
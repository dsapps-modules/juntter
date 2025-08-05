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

    /** @test */
    public function listar_liquidacoes_sumarizadas()
    {
        $service = new LiquidacaoService($this->apiClient);

        $filtros = [
            'perPage' => 10,
            'page' => 1
        ];

        $response = $service->listarLiquidacoesSumarizadas($filtros);

        dump('RESPOSTA LISTAR LIQUIDAÇÕES SUMARIZADAS:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('page', $response);
        $this->assertArrayHasKey('perPage', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    /** @test */
    public function listar_liquidacoes_sumarizadas_com_filtros()
    {
        $service = new LiquidacaoService($this->apiClient);

        $filtros = [
            'perPage' => 5,
            'page' => 1,
            'filters' => json_encode(['status' => 'PAID']),
            'sorters' => json_encode([
                ['column' => 'liquidation', 'direction' => 'DESC']
            ])
        ];

        $response = $service->listarLiquidacoesSumarizadas($filtros);

        dump('RESPOSTA LISTAR LIQUIDAÇÕES SUMARIZADAS COM FILTROS:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('page', $response);
        $this->assertArrayHasKey('perPage', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    /** @test */
    public function listar_liquidacoes_para_obter_ids()
    {
        $service = new LiquidacaoService($this->apiClient);

        $filtros = [
            'perPage' => 1,
            'page' => 1
        ];

        $response = $service->listarLiquidacoes($filtros);

        dump('RESPOSTA PARA OBTER IDS:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);

        // Retorna o primeiro ID de liquidação encontrado
        if (!empty($response['data'])) {
            $liquidation = $response['data'][0];
            $ids = [
                'liquidation_id' => $liquidation['_id'],
                'payment_id' => $liquidation['payments'][0]['_id'] ?? null
            ];
            dump('IDS ENCONTRADOS:', $ids);
            return $ids;
        }

        // Se não encontrar dados, retorna null
        $ids = [
            'liquidation_id' => null,
            'payment_id' => null
        ];
        dump('NENHUM ID ENCONTRADO:', $ids);
        return $ids;
    }

    /** @test
     * @depends listar_liquidacoes_para_obter_ids
     */
    public function exibir_transferencia(array $ids)
    {
        $service = new LiquidacaoService($this->apiClient);

        dump('IDS RECEBIDOS NO TESTE:', $ids);

        $liquidationId = $ids['liquidation_id'];
        $paymentId = $ids['payment_id'];

        // Se não temos IDs válidos, pula o teste
        if (!$liquidationId || !$paymentId) {
            $this->markTestSkipped('Nenhum liquidation_id ou payment_id válido encontrado para testar transferência');
            return;
        }

        $response = $service->exibirTransferencia($liquidationId, $paymentId);

        dump('RESPOSTA EXIBIR TRANSFERÊNCIA:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_id', $response);
        $this->assertArrayHasKey('type', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('amount', $response);
        $this->assertArrayHasKey('expected_at', $response);
        $this->assertArrayHasKey('gateway_key', $response);
        $this->assertArrayHasKey('liquidation_id', $response);
        $this->assertArrayHasKey('payer', $response);
        $this->assertArrayHasKey('recipient', $response);
        $this->assertArrayHasKey('history', $response);
        $this->assertIsArray($response['history']);
    }
} 
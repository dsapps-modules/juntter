<?php

namespace Tests\Integration\Services;

use App\Helpers\DashHelper;
use App\Services\ApiClientService;
use App\Services\TransacaoService;
use Tests\TestCase;

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
            'filters' => json_encode([
                'created_at' => [
                    'min' => '2025-10-01',
                    'max' => '2025-11-30',
                ],
            ]),
            'sorters' => json_encode([
                'column' => 'created_at',
                'direction' => 'ASC',
            ]),
            'perPage' => 1000,
            'page' => 2,
        ];

        $response = $service->listarTransacoes($filtros);
        $metrics = DashHelper::buildDashboardMetrics($response);

        dump('RESPOSTA LISTAR TRANSAÇÕES:', $metrics);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('page', $response);
    }

    /** @teste */
    public function listar_transacoes_com_filtros_avancados()
    {
        $service = new TransacaoService($this->apiClient);

        $filtros = [
            'filters' => json_encode([
                'status' => 'PENDING',
                'type' => 'CREDIT',
                'establishment.id' => 155102,
            ]),
            'perPage' => 5,
            'page' => 1,
        ];

        $response = $service->listarTransacoes($filtros);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('total', $response);
    }

    /** @teste */
    public function detalhes_transacao()
    {
        $service = new TransacaoService($this->apiClient);

        // Primeiro lista as transações para pegar um ID real
        $filtros = [
            'perPage' => 1,
            'page' => 1,
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

    /** @teste */
    public function simular_transacao_credito()
    {
        $service = new TransacaoService($this->apiClient);

        $dados = [
            'amount' => 5000, // R$ 50,00
            'flag_id' => 1, // MASTERCARD
            'gateway_id' => 4, // SUBPAYTIME
            'modality' => 'ONLINE',
            'interest' => 'ESTABLISHMENT',
            'extra_headers' => [
                'establishment_id' => '155102',
            ],
        ];

        $response = $service->simularTransacao($dados);

        dump('RESPOSTA SIMULAÇÃO:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('amount', $response);
        $this->assertArrayHasKey('simulation', $response);
        $this->assertArrayHasKey('credit', $response['simulation']);
        $this->assertArrayHasKey('debit', $response['simulation']);
        $this->assertArrayHasKey('pix', $response['simulation']);
    }

    /** @teste */
    public function aplicar_split_transacao()
    {
        $service = new TransacaoService($this->apiClient);

        // Primeiro lista as transações para pegar um ID real
        $filtros = [
            'perPage' => 1,
            'page' => 1,
        ];

        $listaTransacoes = $service->listarTransacoes($filtros);

        $this->assertIsArray($listaTransacoes);
        $this->assertArrayHasKey('data', $listaTransacoes);
        $this->assertNotEmpty($listaTransacoes['data']);

        // Pega o ID da primeira transação encontrada
        $idTransacao = $listaTransacoes['data'][0]['_id'];

        $dadosSplit = [
            'title' => 'Comissão EC Secundário',
            'division' => 'PERCENTAGE',
            'establishments' => [
                [
                    'id' => 155161, // Estabelecimento diferente
                    'value' => 30,
                ],
            ],
        ];

        $response = $service->aplicarSplit($idTransacao, $dadosSplit);

        dump('RESPOSTA SPLIT:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Processo de Split iniciado', $response['message']);
    }

    /** @teste */
    public function aplicar_split_transacao_valor_fixo()
    {
        $service = new TransacaoService($this->apiClient);

        // Primeiro lista as transações para pegar um ID real
        $filtros = [
            'perPage' => 1,
            'page' => 1,
        ];

        $listaTransacoes = $service->listarTransacoes($filtros);

        $this->assertIsArray($listaTransacoes);
        $this->assertArrayHasKey('data', $listaTransacoes);
        $this->assertNotEmpty($listaTransacoes['data']);

        // Pega o ID da primeira transação encontrada
        $idTransacao = $listaTransacoes['data'][0]['_id'];

        $dadosSplit = [
            'title' => 'Comissão Valor Fixo',
            'division' => 'CURRENCY',
            'establishments' => [
                [
                    'id' => 155161, // Estabelecimento diferente
                    'value' => 1500, // R$ 15,00 em centavos
                ],
            ],
        ];

        $response = $service->aplicarSplit($idTransacao, $dadosSplit);

        dump('RESPOSTA SPLIT VALOR FIXO:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Processo de Split iniciado', $response['message']);
    }

    /** @teste */
    public function lancamentos_futuros()
    {
        $service = new TransacaoService($this->apiClient);

        $filtros = [
            'extra_headers' => [
                'establishment_id' => '155102',
            ],
        ];

        $response = $service->lancamentosFuturos($filtros);

        dump('RESPOSTA LANÇAMENTOS FUTUROS:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('calendar', $response);
        $this->assertArrayHasKey('thirtyDays', $response);
        $this->assertArrayHasKey('months', $response);
        $this->assertArrayHasKey('total', $response);
    }

    /** @teste */
    public function lancamentos_futuros_diarios()
    {
        $service = new TransacaoService($this->apiClient);

        $filtros = [
            'filters' => json_encode([
                'gateway_authorization' => 'PAYTIME',
                'date' => '2025-09-03',
            ]),
            'extra_headers' => [
                'establishment_id' => '155102',
            ],
        ];

        $response = $service->lancamentosFuturosDiarios($filtros);

        dump('RESPOSTA LANÇAMENTOS FUTUROS DIÁRIOS:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('page', $response);
        $this->assertArrayHasKey('perPage', $response);
    }

    /** @teste */
    public function consultar_split_transacao()
    {
        $service = new TransacaoService($this->apiClient);

        // Primeiro lista as transações para pegar um ID real
        $filtros = [
            'perPage' => 1,
            'page' => 1,
        ];

        $listaTransacoes = $service->listarTransacoes($filtros);

        $this->assertIsArray($listaTransacoes);
        $this->assertArrayHasKey('data', $listaTransacoes);
        $this->assertNotEmpty($listaTransacoes['data']);

        // Pega o ID da primeira transação encontrada
        $idTransacao = $listaTransacoes['data'][0]['_id'];

        $response = $service->consultarSplitTransacao($idTransacao);

        dump('RESPOSTA CONSULTAR SPLIT:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('_id', $response);
        $this->assertArrayHasKey('transaction_id', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('establishment', $response);
        $this->assertArrayHasKey('original_amount', $response);
        $this->assertArrayHasKey('channel', $response);
        $this->assertArrayHasKey('modality', $response);
        $this->assertArrayHasKey('division', $response);
        $this->assertArrayHasKey('establishments', $response);
        $this->assertArrayHasKey('history', $response);
        $this->assertArrayHasKey('created_at', $response);
        $this->assertIsArray($response['establishments']);
        $this->assertIsArray($response['history']);
    }

    /** @teste */
    public function cancelar_split_transacao()
    {
        $service = new TransacaoService($this->apiClient);

        // Primeiro lista as transações para pegar um ID real
        $filtros = [
            'perPage' => 1,
            'page' => 1,
        ];

        $listaTransacoes = $service->listarTransacoes($filtros);

        $this->assertIsArray($listaTransacoes);
        $this->assertArrayHasKey('data', $listaTransacoes);
        $this->assertNotEmpty($listaTransacoes['data']);

        // Pega o ID da primeira transação encontrada
        $idTransacao = $listaTransacoes['data'][0]['_id'];

        $response = $service->cancelarSplitTransacao($idTransacao);

        dump('RESPOSTA CANCELAR SPLIT:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Processo de cancelamento de Split iniciado.', $response['message']);
    }
}

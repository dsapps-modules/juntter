<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Services\SplitPreService;
use App\Services\ApiClientService;

// teste individual do serviço SplitPreService 
// php artisan test tests/Integration/Services/SplitPreServiceIntegrationTest.php

class SplitPreServiceIntegrationTest extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = app(ApiClientService::class);
    }

    /** @test */
    public function criar_regra_split_pre()
    {
        $service = new SplitPreService($this->apiClient);

        $establishmentId = '155102';
        
        $dados = [
            'title' => 'Comissão Sandbox Teste ' . time(),
            'modality' => 'CREDIT',
            'channel' => 'SMART',
            'division' => 'PERCENTAGE',
            'active' => true,
            'installment' => 3,
            'establishments' => [
                [
                    'id' => 155161,
                    'active' => true,
                    'value' => 30
                ]
            ]
        ];

        $response = $service->criarRegraSplitPre($establishmentId, $dados);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('title', $response);

        return $response['id'];
    }

    /** @test */
    public function listar_regras_split_pre()
    {
        $service = new SplitPreService($this->apiClient);

        $establishmentId = '155102';
        
        $response = $service->listarRegrasSplitPre($establishmentId);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }

    /**
     * @test
     * @depends criar_regra_split_pre
     */
    public function consultar_regra_split_pre_especifica(int $splitId)
    {
        $service = new SplitPreService($this->apiClient);

        $establishmentId = '155102';
        
        $response = $service->consultarRegraSplitPre($establishmentId, $splitId);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('title', $response);
        $this->assertArrayHasKey('establishments', $response);
    }

    /**
     * @test
     * @depends criar_regra_split_pre
     */
    public function atualizar_regra_split_pre(int $splitId)
    {
        $service = new SplitPreService($this->apiClient);

        $establishmentId = '155102';
        
        $dados = [
            'title' => 'Comissão Sandbox Atualizada ' . time(),
            'modality' => 'CREDIT',
            'channel' => 'ONLINE',
            'division' => 'PERCENTAGE',
            'active' => true,
            'installment' => 3,
            'establishments' => [
                [
                    'id' => 155161,
                    'active' => true,
                    'value' => 25
                ]
            ]
        ];

        $response = $service->atualizarRegraSplitPre($establishmentId, $splitId, $dados);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('title', $response);
        $this->assertArrayHasKey('establishments', $response);
    }

    /**
     * @test
     * @depends criar_regra_split_pre
     */
    public function deletar_regra_split_pre(int $splitId)
    {
        $service = new SplitPreService($this->apiClient);

        $establishmentId = '155102';
        
        $response = $service->deletarRegraSplitPre($establishmentId, $splitId);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('message', $response);
    }

} 
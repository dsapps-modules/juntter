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
            'channel' => 'ONLINE',
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
} 
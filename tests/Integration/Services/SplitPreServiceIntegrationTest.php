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
            'title' => 'Comissão Sandbox Atualizado',
            'modality' => 'PIX',
            'channel' => 'ALL',
            'division' => 'PERCENTAGE',
            'active' => true,
            'installment' => 2,
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
} 
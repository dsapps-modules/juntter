<?php

namespace Tests\Integration\Actions;

use Tests\TestCase;
use App\Services\BoletoService;
use App\Services\ApiClientService;

// teste individual do serviço BoletoService 
// php artisan test tests/Integration/Actions/GerarBoleto.php

class ListarBoletos extends TestCase
{
    protected $apiClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiClient = app(ApiClientService::class);
    }

    /** @test */

    /** @test */
    public function listar_boletos()
    {
        $service = new BoletoService($this->apiClient);


        $filters = [
            'created_at' => [
                "min" => "2025-10-01",
                "max" => "2025-10-31"
            ]
        ];

        $filtros = [
            'filters' => json_encode($filters),
            'perPage' => 10,
            'page' => 1,
        ];

        $response = $service->listarBoletos($filtros);

        dump('RESPOSTA LISTAR BOLETOS:', $response);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('page', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);
    }
}
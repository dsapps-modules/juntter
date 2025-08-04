<?php

namespace App\Services;

use App\Services\ApiClientService;

class RelatorioService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function gerarRelatorio(array $filtros)
    {
        return $this->apiClient->post("/v1/report?" . http_build_query($filtros));
    }

}

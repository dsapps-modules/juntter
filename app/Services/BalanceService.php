<?php

namespace App\Services;

use App\Services\ApiClientService;

class BalanceService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function saldoAtual(array $filtros)
    {
        return $this->apiClient->get("/v1/marketplace/establishments/balance", $filtros);
    }

}

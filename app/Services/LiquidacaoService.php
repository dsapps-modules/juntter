<?php

namespace App\Services;

use App\Services\ApiClientService;

class LiquidacaoService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function listarLiquidacoes(array $filtros = [])
    {
        return $this->apiClient->get("marketplace/liquidations/extract?" . http_build_query($filtros));
    }
} 
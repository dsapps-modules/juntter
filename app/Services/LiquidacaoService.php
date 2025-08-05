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
        return $this->apiClient->get("marketplace/liquidations?" . http_build_query($filtros));
    }

    public function listarLiquidacoesSumarizadas(array $filtros = [])
    {
        return $this->apiClient->get("marketplace/liquidations/extract?" . http_build_query($filtros));
    }

    public function exibirTransferencia(string $liquidationId, string $paymentId)
    {
        return $this->apiClient->get("marketplace/liquidations/{$liquidationId}/payments/{$paymentId}/transfer");
    }
} 
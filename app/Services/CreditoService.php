<?php

namespace App\Services;

use App\Services\ApiClientService;

class CreditoService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function criarTransacaoCredito(array $dados)
    {
        return $this->apiClient->post("/v1/marketplace/transactions", $dados);
    }

    public function estornarTransacao(string $id, array $dados)
    {
        return $this->apiClient->post("/v1/marketplace/transactions/{$id}/reversal", $dados);
    }
}

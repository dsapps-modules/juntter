<?php

namespace App\Services;

use App\Services\ApiClientService;

class PagamentoService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function criarPagamento(array $dados)
    {
        return $this->apiClient->post("/v1/payment", $dados);
    }

    public function consultarPagamento(string $codigo)
    {
        return $this->apiClient->get("/v1/payment/{$codigo}");
    }

}

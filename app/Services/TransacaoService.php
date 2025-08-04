<?php

namespace App\Services;

use App\Services\ApiClientService;

class TransacaoService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function listarTransacoes(array $filtros = [])
    {
        return $this->apiClient->get("marketplace/transactions?" . http_build_query($filtros));
    }

    public function detalhesTransacao(string $codigo)
    {
        return $this->apiClient->get("marketplace/transactions/{$codigo}");
    }

    public function simularTransacao(array $dados)
    {
        return $this->apiClient->post("marketplace/transactions/simulate", $dados);
    }

    public function aplicarSplit(string $idTransacao, array $dados)
    {
        return $this->apiClient->post("marketplace/transactions/{$idTransacao}/split", $dados);
    }
}

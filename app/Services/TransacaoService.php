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
        return $this->apiClient->get("marketplace/transactions", $filtros);
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

    public function consultarSplitTransacao(string $idTransacao)
    {
        return $this->apiClient->get("marketplace/transactions/{$idTransacao}/split");
    }

    public function cancelarSplitTransacao(string $idTransacao)
    {
        return $this->apiClient->delete("marketplace/transactions/{$idTransacao}/split");
    }

    public function lancamentosFuturos(array $filtros = [])
    {
        return $this->apiClient->get("marketplace/transactions/future_releases", $filtros);
    }
    public function lancamentosFuturosDiarios(array $filtros = [])
    {
        
        
        return $this->apiClient->get("marketplace/transactions/future_releases_daily", $filtros);
    }

    public function estornarTransacao(string $id)
    {
        $dados = [
            'use_account' => true
        ];
        
        return $this->apiClient->post("marketplace/transactions/{$id}/reversal", $dados);
    }

    public function listarPlanosComerciais(array $filtros = [])
    {
        return $this->apiClient->get("marketplace/plans", $filtros);
    }

    public function detalhesPlanoComercial(int $id)
    {
        return $this->apiClient->get("marketplace/plans/{$id}");
    }
}

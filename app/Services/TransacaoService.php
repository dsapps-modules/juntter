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
        // Separa extra_headers dos filtros normais
        $extra_headers = $filtros['extra_headers'] ?? [];
        unset($filtros['extra_headers']);
        
        $query_params = http_build_query($filtros);
        $endpoint = "marketplace/transactions/future_releases";
        if ($query_params) {
            $endpoint .= "?" . $query_params;
        }
        
        return $this->apiClient->get($endpoint, ['extra_headers' => $extra_headers]);
    }
    public function lancamentosFuturosDiarios(array $filtros = [])
    {
        // Separa extra_headers dos filtros normais
        $extra_headers = $filtros['extra_headers'] ?? [];
        unset($filtros['extra_headers']);
        

        
        $query_params = http_build_query($filtros);
        $endpoint = "marketplace/transactions/future_releases_daily";
        if ($query_params) {
            $endpoint .= "?" . $query_params;
        }
        
        return $this->apiClient->get($endpoint, ['extra_headers' => $extra_headers]);
    }

    public function estornarTransacao(string $id)
    {
        $dados = [
            'use_account' => true
        ];
        
        return $this->apiClient->post("marketplace/transactions/{$id}/reversal", $dados);
    }
}

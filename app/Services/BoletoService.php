<?php

namespace App\Services;

use App\Services\ApiClientService;

class BoletoService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function gerarBoleto(array $dados)
    {
        return $this->apiClient->post("marketplace/billets", $dados);
    }

    public function listarBoletos(array $filtros = [])
    {
        return $this->apiClient->get("marketplace/billets?" . http_build_query($filtros));
    }

    public function consultarBoleto(string $id)
    {
        return $this->apiClient->get("marketplace/billets/{$id}");
    }

    public function recargaViaBoleto(array $dados)
    {
        return $this->apiClient->post("marketplace/billets/recharge", $dados);
    }
}
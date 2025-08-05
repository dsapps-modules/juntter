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
}
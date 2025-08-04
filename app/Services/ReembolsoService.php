<?php

namespace App\Services;

use App\Services\ApiClientService;

class ReembolsoService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function criarReembolso(array $dados)
    {
        return $this->apiClient->post("/v1/refund", $dados);
    }

}

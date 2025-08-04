<?php

namespace App\Services;

use App\Services\ApiClientService;

class CheckoutService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function criarCheckout(array $dados)
    {
        return $this->apiClient->post("/v1/checkout", $dados);
    }

}

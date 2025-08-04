<?php

namespace App\Services;

use App\Services\ApiClientService;

class WebhookService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function listarWebhooks()
    {
        return $this->apiClient->get("/v1/webhook");
    }

    public function cadastrarWebhook(array $dados)
    {
        return $this->apiClient->post("/v1/webhook", $dados);
    }

}

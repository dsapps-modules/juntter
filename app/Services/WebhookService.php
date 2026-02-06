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
        return $this->apiClient->get("webhook");
    }

    public function criarWebhook(array $dados)
    {
        return $this->apiClient->post("webhook", $dados);
    }

}

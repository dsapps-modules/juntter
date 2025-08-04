<?php

namespace App\Services;

use App\Services\ApiClientService;

class NotificacaoService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function enviarNotificacao(array $dados)
    {
        return $this->apiClient->post("/v1/notification", $dados);
    }

}

<?php

namespace App\Services;

use App\Services\ApiClientService;

class PixService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function criarTransacaoPix(array $dados)
    {
        return $this->apiClient->post("marketplace/transactions", $dados);
    }

    public function obterQrCodePix(string $id)
    {
        return $this->apiClient->get("marketplace/transactions/{$id}/qrcode");
    }
}

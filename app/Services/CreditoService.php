<?php

namespace App\Services;

use App\Services\ApiClientService;
use Illuminate\Support\Facades\Log;

class CreditoService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function criarTransacaoCredito(array $dados)
    {
        return $this->apiClient->post("marketplace/transactions", $dados);
    }

    public function confirmar3ds(array $dados, $id)
    {
        Log::info("8. Envia confirmação 3Ds para a API usando a rota marketplace/transactions/{$id}/antifraud-auth\n" . json_encode($dados));
        return $this->apiClient->post("marketplace/transactions/{$id}/antifraud-auth", $dados);
    }

    public function estornarTransacao(string $id, array $dados)
    {
        return $this->apiClient->post("marketplace/transactions/{$id}/reversal", $dados);
    }
}
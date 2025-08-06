<?php

namespace App\Services;

class SplitPreService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Cria uma regra de split pré para um estabelecimento
     */
    public function criarRegraSplitPre(string $establishmentId, array $dados): array
    {
        $endpoint = "marketplace/establishments/{$establishmentId}/split-pre";
        
        return $this->apiClient->post($endpoint, $dados);
    }
} 
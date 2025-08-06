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

    /**
     * Lista todas as regras de split pré de um estabelecimento
     */
    public function listarRegrasSplitPre(string $establishmentId): array
    {
        $endpoint = "marketplace/establishments/{$establishmentId}/split-pre";
        
        return $this->apiClient->get($endpoint);
    }

    /**
     * Consulta uma regra específica de split pré
     */
    public function consultarRegraSplitPre(string $establishmentId, string $splitId): array
    {
        $endpoint = "marketplace/establishments/{$establishmentId}/split-pre/{$splitId}";
        
        return $this->apiClient->get($endpoint);
    }
} 
<?php

namespace App\Services;

use App\Services\ApiClientService;

class EstabelecimentoService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function listarEstabelecimentos()
    {
        return $this->apiClient->get("marketplace/establishments");
    }

    public function buscarEstabelecimento(string $id)
    {
        return $this->apiClient->get("marketplace/establishments/{$id}");
    }

    public function criarEstabelecimento(array $dados)
    {
        return $this->apiClient->post("marketplace/establishments", $dados);
    }

    public function atualizarEstabelecimento(string $is, array $dados)
    {
        return $this->apiClient->put("marketplace/establishments/{$is}", $dados);
    }

}

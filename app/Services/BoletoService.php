<?php

namespace App\Services;

use App\Services\ApiClientService;

class BoletoService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function gerarBoleto(array $dados)
    {
        return $this->apiClient->post("marketplace/billets", $dados);
    }

    public function listarBoletos(array $filtros = [])
    {
        return $this->apiClient->get("marketplace/billets", $filtros);
    }

    public function consultarBoleto(string $id)
    {
        return $this->apiClient->get("marketplace/billets/{$id}");
    }

    public function recargaViaBoleto(array $dados)
    {
        return $this->apiClient->post("marketplace/billets/recharge", $dados);
    }

    public function deletarBoleto(string $id)
    {
        return $this->apiClient->delete("marketplace/billets/{$id}");
    }

    public function organiza($dados){
        $dados['client']['document'] = preg_replace('/[^0-9]/', '', $dados['client']['document']);
        $dados['client']['address']['zip_code'] = preg_replace('/[^0-9]/', '', $dados['client']['address']['zip_code']);

        // Adicionar os campos mode automaticamente (são sempre os mesmos para boleto)
        $dados['instruction']['late_fee']['mode'] = 'PERCENTAGE';
        $dados['instruction']['interest']['mode'] = 'MONTHLY_PERCENTAGE';
        $dados['instruction']['discount']['mode'] = 'PERCENTAGE';

        // Adicionar establishment_id
        $dados['extra_headers'] = [
            'establishment_id' => auth()->user()?->vendedor?->estabelecimento_id
        ];

        return $dados;
    }

}
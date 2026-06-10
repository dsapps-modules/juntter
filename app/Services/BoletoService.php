<?php

namespace App\Services;

class BoletoService
{
    protected $apiClient;

    public function __construct(ApiClientService $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function gerarBoleto(array $dados): array
    {
        return $this->apiClient->post('marketplace/billets', $dados);
    }

    public function gerarBoletoComConsulta(array $dados, int $maxTentativas = 5, int $intervaloMilissegundos = 400): array
    {
        $boleto = $this->normalizarResposta($this->gerarBoleto($dados));
        $boletoId = $this->resolverIdentificadorBoleto($boleto);

        if ($boletoId === null || $this->boletoTemDadosEssenciais($boleto)) {
            return $boleto;
        }

        for ($tentativa = 1; $tentativa <= $maxTentativas; $tentativa++) {
            if ($intervaloMilissegundos > 0) {
                usleep($intervaloMilissegundos * 1000);
            }

            $consulta = $this->normalizarResposta($this->consultarBoleto($boletoId));
            $boleto = array_replace($boleto, array_filter($consulta, static fn ($value) => $value !== null));

            if ($this->boletoTemDadosEssenciais($boleto)) {
                return $boleto;
            }
        }

        return $boleto;
    }

    public function normalizarResposta(array $boleto): array
    {
        $boleto['boleto_url'] = data_get($boleto, 'boleto_url')
            ?? data_get($boleto, 'url')
            ?? data_get($boleto, 'boleto.url')
            ?? data_get($boleto, 'api_boleto.boleto_url')
            ?? data_get($boleto, 'api_boleto.url')
            ?? data_get($boleto, 'api_boleto.boleto.url')
            ?? data_get($boleto, 'api_boleto.data.url')
            ?? data_get($boleto, 'data.url')
            ?? null;
        $boleto['boleto_barcode'] = data_get($boleto, 'boleto_barcode')
            ?? data_get($boleto, 'barcode')
            ?? data_get($boleto, 'boleto.barcode')
            ?? data_get($boleto, 'api_boleto.boleto_barcode')
            ?? data_get($boleto, 'api_boleto.barcode')
            ?? data_get($boleto, 'api_boleto.boleto.barcode')
            ?? data_get($boleto, 'api_boleto.data.barcode')
            ?? data_get($boleto, 'data.barcode')
            ?? null;
        $boleto['boleto_digitable_line'] = data_get($boleto, 'boleto_digitable_line')
            ?? data_get($boleto, 'digitable_line')
            ?? data_get($boleto, 'boleto.digitable_line')
            ?? data_get($boleto, 'api_boleto.boleto_digitable_line')
            ?? data_get($boleto, 'api_boleto.digitable_line')
            ?? data_get($boleto, 'api_boleto.boleto.digitable_line')
            ?? data_get($boleto, 'api_boleto.data.digitable_line')
            ?? data_get($boleto, 'data.digitable_line')
            ?? null;

        return $boleto;
    }

    public function listarBoletos(array $filtros = []): array
    {
        return $this->apiClient->get('marketplace/billets', $filtros);
    }

    public function consultarBoleto(string $id): array
    {
        return $this->apiClient->get("marketplace/billets/{$id}");
    }

    public function recargaViaBoleto(array $dados): array
    {
        return $this->apiClient->post('marketplace/billets/recharge', $dados);
    }

    public function deletarBoleto(string $id): array
    {
        return $this->apiClient->delete("marketplace/billets/{$id}");
    }

    public function organiza(array $dados): array
    {
        $dados['client']['document'] = preg_replace('/[^0-9]/', '', $dados['client']['document']);
        $dados['client']['address']['zip_code'] = preg_replace('/[^0-9]/', '', $dados['client']['address']['zip_code']);

        // Adicionar os campos mode automaticamente (são sempre os mesmos para boleto)
        $dados['instruction']['late_fee']['mode'] = 'PERCENTAGE';
        $dados['instruction']['interest']['mode'] = 'MONTHLY_PERCENTAGE';
        $dados['instruction']['discount']['mode'] = 'PERCENTAGE';

        // Adicionar establishment_id
        $dados['extra_headers'] = [
            'establishment_id' => auth()->user()?->vendedor?->estabelecimento_id,
        ];

        return $dados;
    }

    private function boletoTemDadosEssenciais(array $boleto): bool
    {
        return filled($boleto['boleto_url'] ?? null)
            || filled($boleto['boleto_barcode'] ?? null)
            || filled($boleto['boleto_digitable_line'] ?? null);
    }

    private function resolverIdentificadorBoleto(array $boleto): ?string
    {
        $identificador = $boleto['_id']
            ?? $boleto['id']
            ?? data_get($boleto, 'boleto._id')
            ?? data_get($boleto, 'boleto.id')
            ?? data_get($boleto, 'api_boleto._id')
            ?? data_get($boleto, 'api_boleto.id')
            ?? data_get($boleto, 'data._id')
            ?? data_get($boleto, 'data.id')
            ?? null;

        if (! is_scalar($identificador) || trim((string) $identificador) === '') {
            return null;
        }

        return (string) $identificador;
    }
}

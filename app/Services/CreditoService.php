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

    public function organiza($dados){
        // Limpar campos com máscara - manter apenas números
        $dados['client']['document'] = preg_replace('/[^0-9]/', '', $dados['client']['document']);
        $dados['client']['phone'] = preg_replace('/[^0-9]/', '', $dados['client']['phone']);
        $dados['client']['address']['zip_code'] = preg_replace('/[^0-9]/', '', $dados['client']['address']['zip_code']);
        $dados['card']['card_number'] = preg_replace('/[^0-9]/', '', $dados['card']['card_number']);
        
        // Limpar documento do portador do cartão se fornecido
        if (!empty($dados['card']['holder_document'])) {
            $dados['card']['holder_document'] = preg_replace('/[^0-9]/', '', $dados['card']['holder_document']);
        }
        
        // Converter campos para tipos corretos para a API
        $dados['installments'] = (int)($dados['installments'] ?? 1); // Default para 1 se não enviado
        $dados['card']['expiration_month'] = (int)$dados['card']['expiration_month'];
        $dados['card']['expiration_year'] = (int)$dados['card']['expiration_year'];

        return $dados;
    }

    public function requer3DS($transacao){
        $data = [
            'success' => true,
            'requires_3ds' => true,
            'session_id' => $transacao['antifraud'][0]['session'],
            'transaction_id' => $transacao['_id'],
            'message' => 'Transação criada, aguardando autenticação 3DS'
        ];

        Log::info("3. Requer 3Ds...\n" . json_encode($data));
        return response()->json($data);
    }

    public function verificaSucesso($transacao){
        // Verificar se a transação tem um ID válido antes de retornar sucesso
        $transacaoId = $transacao['_id'] ?? null;
        
        if (!$transacaoId) {
            Log::error('Transação criada mas sem ID válido');
            throw new \Exception('erro no pagamento');
        }
    }
}
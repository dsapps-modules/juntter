<?php

namespace App\Http\Controllers;

use App\Models\LinkPagamento;
use App\Services\TransacaoService;
use App\Services\CreditoService;
use App\Services\BoletoService;
use Illuminate\Validation\Rule;
use App\Services\PixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagamentoClienteController extends Controller
{
    protected $transacaoService;
    protected $creditoService;
    protected $pixService;
    protected $boletoService;

    public function __construct(
        TransacaoService $transacaoService,
        CreditoService $creditoService,
        PixService $pixService,
        BoletoService $boletoService
    ) {
        $this->transacaoService = $transacaoService;
        $this->creditoService = $creditoService;
        $this->pixService = $pixService;
        $this->boletoService = $boletoService;
    }

    /**
     * Mostrar página de pagamento do cliente
     */
    public function mostrarPagamento($codigoUnico)
    {
        try {
            $link = LinkPagamento::where('codigo_unico', $codigoUnico)->first();

            if (!$link) {
                abort(404, 'Link de pagamento não encontrado');
            }

            if (!$link->estaAtivo()) {
                abort(410, 'Este link de pagamento não está mais ativo');
            }

            return view('pagamento.cliente', compact('link'));

        } catch (\Exception $e) {
            Log::error('Erro ao mostrar página de pagamento: ' . $e->getMessage());
            abort(404, 'Link de pagamento inválido');
        }
    }

    /**
     * Processar pagamento com cartão de crédito.
     * 
     * @execution: método chamado, via POST, enviado pelo form do link de pagamento com cartão.
     */
    public function processarCartao(Request $request, $codigoUnico)
    {
        try {
            $link = LinkPagamento::where('codigo_unico', $codigoUnico)->first();

            if (!$link || !$link->estaAtivo()) {
                return response()->json(['error' => 'Link inválido ou inativo'], 400);
            }

            $dados = $request->validate([
                'installments' => 'nullable|integer|min:1|max:18',
                'client.first_name' => 'required|string|max:255',
                'client.last_name' => 'nullable|string|max:255',
                'client.document' => 'required|string|max:18',
                'client.phone' => 'required|string|max:20',
                'client.email' => 'required|email',
                'client.address.street' => 'required|string|max:255',
                'client.address.number' => 'required|string|max:10',
                'client.address.complement' => 'nullable|string|max:255',
                'client.address.neighborhood' => 'required|string|max:255',
                'client.address.city' => 'required|string|max:255',
                'client.address.state' => 'required|string|size:2',
                'client.address.zip_code' => 'required|string|size:9 ',
                'card.holder_name' => 'required|string|max:255',
                'card.holder_document' => 'nullable|string|max:18',
                'card.card_number' => 'required|string|min:13|max:19',
                'card.expiration_month' => 'required|integer|min:1|max:12',
                'card.expiration_year' => 'required|integer|min:2025',
                'card.security_code' => 'required|string|min:3|max:4',
            ]);

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

            // Forçar parcela 1 para links à vista
            if ($link->is_avista || $link->parcelas == 1) {
                $dados['installments'] = 1;
            }

            // Verificar se o parcelamento é válido
            $parcelasPermitidas = is_array($link->parcelas) ? $link->parcelas : range(1, $link->parcelas ?: 1);
            if (!in_array($dados['installments'], $parcelasPermitidas)) {
                return response()->json(['error' => 'Parcelamento não permitido'], 400);
            }

            // Preparar dados para a API
            $dadosTransacao = [
                'payment_type' => 'CREDIT',
                'amount' => $link->valor_centavos,
                'installments' => $dados['installments'],
                'interest' => $link->juros,
                'client' => $dados['client'],
                'card' => $dados['card'],
                'extra_headers' => [
                    'establishment_id' => (int) $link->estabelecimento_id
                ],
                'session_id' => 'session_' . uniqid(),
                'info_additional' => [
                    [
                        'key' => 'link_pagamento_id',
                        'value' => (string) $link->id
                    ],
                    [
                        'key' => 'codigo_unico',
                        'value' => $link->codigo_unico ?: 'N/A'
                    ]
                ]
            ];

            // Tratar campos opcionais
            if (empty($dadosTransacao['client']['last_name'])) {
                $dadosTransacao['client']['last_name'] = '';
            }
            if (empty($dadosTransacao['client']['address']['complement'])) {
                $dadosTransacao['client']['address']['complement'] = '';
            }
            if (empty($dadosTransacao['card']['holder_document'])) {
                $dadosTransacao['card']['holder_document'] = '';
            }

            Log::info("1. Dados da transação:\n" . json_encode($dadosTransacao));
            $transacao = $this->creditoService->criarTransacaoCredito($dadosTransacao);
            Log::info("2. Resposta da API:\n" . json_encode($transacao));

            if (!$transacao) {
                Log::error('Transação retornou vazia ou falsa');
                throw new \Exception('Falha ao criar transação');
            }

            // Verificar se a transação requer autenticação 3DS
            if (($transacao['antifraud'][0]['analyse_required'] ?? null) === 'THREEDS' &&
                ($transacao['antifraud'][0]['analyse_status'] ?? null) === 'WAITING_AUTH') {

                $data = [
                    'success' => true,
                    'requires_3ds' => true,
                    'session_id' => $transacao['antifraud'][0]['session'],
                    'transaction_id' => $transacao['antifraud'][0]['_id'],
                    'message' => 'Transação criada, aguardando autenticação 3DS'
                ];

                Log::info("3. Requer 3Ds...\n" . json_encode($data));
                return response()->json($data);
            }

            // Atualizar status do link se necessário
            if (isset($transacao['status']) && $transacao['status'] === 'PAID') {
                $link->update(['status' => 'PAID']);
            }

            // Verificar se a transação tem um ID válido antes de retornar sucesso
            $transacaoId = $transacao['_id'] ?? null;
            
            if (!$transacaoId) {
                Log::error('Transação criada mas sem ID válido');
                throw new \Exception('erro no pagamento');
            }

            return response()->json([
                'success' => true,
                'message' => 'Pagamento processado com sucesso',
                
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar pagamento com cartão: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar pagamento: ' . $e->getMessage()], 500);
        }
    }

    public function confirmar3ds(Request $request, $transid, $codigoUnico) {
        $link = LinkPagamento::where('codigo_unico', $codigoUnico)->first();
        if (!$link || !$link->estaAtivo()) {
            return response()->json(['error' => 'Link inválido ou inativo'], 400);
        }        

        $data = $request->validate([
            'id' => 'required|string|regex:/^3DS_[A-F0-9\-]{36}$/i',
            'status' => 'required|string|'.Rule::in(['AUTH_FLOW_COMPLETED', 'AUTH_NOT_SUPPORTED', 'CHANGE_PAYMENT_METHOD']),
            'authentication_status' => 'required|string|'.Rule::in(['AUTHENTICATED', 'NOT_AUTHENTICATED'])
        ]);

        // faz o post para a api
        $data['extra_headers'] = ['establishment_id' => $link->estabelecimento_id];
        Log::info("7. Confirma 3Ds na API com esses dados?\n" . json_encode($data));
        $result = $this->creditoService->confirmar3ds($data, $transid);
        Log::info("10. Recebe resposta da API Paytime sobre 3Ds:\n" . json_encode($result));

        // retorna o json de cofirmação
        return response()->json($result);
    }

    /**
     * Processar pagamento PIX via link de pagamento
     */
    public function processarPix(Request $request, $codigoUnico)
    {
        try {
            $link = LinkPagamento::where('codigo_unico', $codigoUnico)->first();

            if (!$link || !$link->estaAtivo()) {
                return response()->json(['error' => 'Link inválido ou inativo'], 400);
            }

            if ($link->tipo_pagamento !== 'PIX') {
                return response()->json(['error' => 'Este link não é para PIX'], 400);
            }

            // Pegar dados diretamente do link (sem validação de request)
            $dadosCliente = $link->dados_cliente['preenchidos'] ?? [];
            
            // Preparar dados para a API PIX (igual à cobrança única)
            $dadosPix = [
                'payment_type' => 'PIX',
                'amount' => $link->valor_centavos,
                'interest' => $link->juros,
                'client' => [
                    'first_name' => $dadosCliente['nome'] ?? 'Cliente',
                    'last_name' => $dadosCliente['sobrenome'] ?? '',
                    'email' => $dadosCliente['email'] ?? '',
                    'phone' => $dadosCliente['telefone'] ?? '',
                    'document' => $dadosCliente['documento'] ?? '',
                ],
                'extra_headers' => [
                    'establishment_id' => $link->estabelecimento_id
                ],
                'session_id' => 'session_' . uniqid(),
            ];

            // Adicionar info_additional no formato correto (igual à cobrança única)
            if (!empty($link->descricao)) {
                $dadosPix['info_additional'] = [
                    [
                        'key' => 'info_adicional',
                        'value' => $link->descricao
                    ],
                    [
                        'key' => 'link_pagamento_id',
                        'value' => (string) $link->id
                    ],
                    [
                        'key' => 'codigo_unico',
                        'value' => $link->codigo_unico ?: 'N/A'
                    ]
                ];
            } else {
                $dadosPix['info_additional'] = [
                    [
                        'key' => 'link_pagamento_id',
                        'value' => (string) $link->id
                    ],
                    [
                        'key' => 'codigo_unico',
                        'value' => $link->codigo_unico ?: 'N/A'
                    ]
                ];
            }

            // Criar transação PIX
            $transacao = $this->pixService->criarTransacaoPix($dadosPix);

            Log::info('Transação PIX via link criada', [
                'codigo_unico' => $codigoUnico,
                'link_id' => $link->id,
                'transacao_id' => $transacao['_id'] ?? null,
                'status' => $transacao['status'] ?? null,
                'amount' => $transacao['amount'] ?? null
            ]);

            if (!$transacao) {
                Log::error('Transação PIX retornou vazia ou falsa');
                throw new \Exception('Falha ao criar transação PIX');
            }

            // Verificar se a transação tem ID válido
            if (!isset($transacao['_id'])) {
                Log::error('Transação PIX criada mas sem _id');
                throw new \Exception('Transação criada mas sem ID válido');
            }

            // Obter QR Code com try-catch (igual à cobrança única)
            $qrCode = null;
            try {
                $qrCode = $this->pixService->obterQrCodePix($transacao['_id']);
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar QR Code: ' . $e->getMessage());
                // Continua sem QR Code
            }

            if (!$qrCode) {
                Log::error('QR Code PIX retornou vazio ou falso');
                throw new \Exception('Falha ao obter QR Code PIX');
            }

            // Atualizar status do link se necessário
            if (isset($transacao['status']) && $transacao['status'] === 'PAID') {
                $link->update(['status' => 'PAID']);
            }

            // Filtrar apenas dados necessários para o frontend
            $filteredPixData = [
                'qr_code' => $qrCode ? [
                    'qrcode' => $qrCode['qrcode'] ?? null,
                    'emv' => $qrCode['emv'] ?? null
                ] : null,
                'pix_code' => $transacao['emv'] ?? null, // Código PIX para copiar e colar
                'amount' => $transacao['amount'] ?? null,
                'status' => $transacao['status'] ?? null
            ];

            return response()->json([
                'success' => true,
                'pix_data' => $filteredPixData
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar PIX via link: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar PIX: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Processar pagamento Boleto via link de pagamento
     */
    public function processarBoleto(Request $request, $codigoUnico)
    {
        try {
            $link = LinkPagamento::where('codigo_unico', $codigoUnico)->first();

            if (!$link || !$link->estaAtivo()) {
                return response()->json(['error' => 'Link inválido ou inativo'], 400);
            }

            if ($link->tipo_pagamento !== 'BOLETO') {
                return response()->json(['error' => 'Este link não é para Boleto'], 400);
            }

            // Pegar dados diretamente do link (sem validação de request)
            $dadosCliente = $link->dados_cliente['preenchidos'] ?? [];
            $dadosEndereco = $dadosCliente['endereco'] ?? [];
            
            // Preparar dados para a API Boleto (igual à cobrança única)
            $dadosBoleto = [
                'amount' => $link->valor_centavos,
                'expiration' => $link->data_vencimento ?: now()->addDays(7)->format('Y-m-d'),
                'payment_limit_date' => $link->data_limite_pagamento,
                'recharge' => false, // Links de pagamento não são recarga
                'client' => [
                    'first_name' => $dadosCliente['nome'] ?? 'Cliente',
                    'last_name' => $dadosCliente['sobrenome'] ?? '',
                    'email' => $dadosCliente['email'] ?? '',
                    'phone' => preg_replace('/[^0-9]/', '', $dadosCliente['telefone'] ?? ''), // Remover máscara do telefone
                    'document' => preg_replace('/[^0-9]/', '', $dadosCliente['documento'] ?? ''), // Remover máscara do documento
                    'address' => [
                        'street' => $dadosEndereco['rua'] ?? '',
                        'number' => $dadosEndereco['numero'] ?? '',
                        'complement' => $dadosEndereco['complemento'] ?? '',
                        'neighborhood' => $dadosEndereco['bairro'] ?? '',
                        'city' => $dadosEndereco['cidade'] ?? '',
                        'state' => $dadosEndereco['estado'] ?? '',
                        'zip_code' => preg_replace('/[^0-9]/', '', $dadosEndereco['cep'] ?? ''), // Remover máscara do CEP
                    ]
                ],
                'instruction' => [
                    'booklet' => false, // Links de pagamento não são carnê
                    'description' => $link->descricao ?? '',
                    'late_fee' => [
                        'mode' => 'PERCENTAGE',
                        'amount' => (float) ($link->instrucoes_boleto['late_fee']['amount'] ?? '2.00')
                    ],
                    'interest' => [
                        'mode' => 'MONTHLY_PERCENTAGE',
                        'amount' => (float) ($link->instrucoes_boleto['interest']['amount'] ?? '1.00')
                    ],
                    'discount' => [
                        'mode' => 'PERCENTAGE',
                        'amount' => (float) ($link->instrucoes_boleto['discount']['amount'] ?? '5.00'),
                        'limit_date' => $link->instrucoes_boleto['discount']['limit_date'] ?? now()->addDays(5)->format('Y-m-d')
                    ]
                ],
                'extra_headers' => [
                    'establishment_id' => $link->estabelecimento_id
                ]
            ];

            // Garantir tipos booleanos corretos (igual à cobrança única)
            $dadosBoleto['recharge'] = false;
            $dadosBoleto['instruction']['booklet'] = false;

            // Validar e corrigir formato da data de expiração
            if (isset($dadosBoleto['expiration'])) {
                try {
                    $dataExpiracao = \Carbon\Carbon::parse($dadosBoleto['expiration']);
                    $dadosBoleto['expiration'] = $dataExpiracao->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::warning('Data de expiração inválida, usando data padrão', [
                        'data_original' => $dadosBoleto['expiration'],
                        'data_padrao' => now()->addDays(7)->format('Y-m-d')
                    ]);
                    $dadosBoleto['expiration'] = now()->addDays(7)->format('Y-m-d');
                }
            }

            // Validar e corrigir CEP (máximo 8 caracteres)
            if (isset($dadosBoleto['client']['address']['zip_code'])) {
                $cepLimpo = preg_replace('/[^0-9]/', '', $dadosBoleto['client']['address']['zip_code']);
                $dadosBoleto['client']['address']['zip_code'] = substr($cepLimpo, 0, 8);
                
                
            }

            

            // Criar boleto
            $boleto = $this->boletoService->gerarBoleto($dadosBoleto);


            if (!$boleto) {
                Log::error('Boleto retornou vazio ou falso');
                throw new \Exception('Falha ao criar boleto');
            }

            // Verificar se o boleto tem ID válido
            if (!isset($boleto['_id'])) {
                Log::error('Boleto criado mas sem _id');
                throw new \Exception('Boleto criado mas sem ID válido');
            }

            // Atualizar status do link se necessário
            if (isset($boleto['status']) && $boleto['status'] === 'PAID') {
                $link->update(['status' => 'PAID']);
            }

            // Filtrar apenas dados necessários para o frontend
            $filteredBoletoData = [
                'boleto_url' => $boleto['boleto_url'] ?? null,
                'boleto_barcode' => $boleto['boleto_barcode'] ?? null,
                'amount' => $boleto['amount'] ?? null,
                'status' => $boleto['status'] ?? null
            ];

            return response()->json([
                'success' => true,
                'boleto_data' => $filteredBoletoData
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar boleto via link: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar boleto: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Verificar status da transação
     */
    public function verificarStatus($codigoUnico)
    {
        try {
            $link = LinkPagamento::where('codigo_unico', $codigoUnico)->first();

            if (!$link) {
                return response()->json(['error' => 'Link não encontrado'], 404);
            }

            return response()->json([
                'success' => true,
                'status' => $link->status,
                'esta_ativo' => $link->estaAtivo()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao verificar status: ' . $e->getMessage());
            return response()->json(['error' => 'Erro interno'], 500);
        }
    }

    // Autenticar transação com 3DS
    public function autenticarAntifraude(Request $request, $id)
    {
        try {
            $dados = $request->validate([
                'id' => 'required|string',
                'status' => 'required|string|in:AUTH_FLOW_COMPLETED,AUTH_NOT_SUPPORTED,CHANGE_PAYMENT_METHOD',
                'authentication_status' => 'required|string|in:AUTHENTICATED,NOT_AUTHENTICATED'
            ]);

            $resultado = $this->transacaoService->autenticarAntifraude($id, $dados);

            if ($resultado) {
                return response()->json([
                    'success' => true,
                    'message' => 'Autenticação 3DS processada com sucesso',
                    'data' => $resultado
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao processar autenticação 3DS'
                ], 500);
            }
        } 
        catch (\Exception $e) {
            Log::error('Erro ao autenticar antifraude (link): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar autenticação: ' . $e->getMessage()
            ], 500);
        }
    }
}
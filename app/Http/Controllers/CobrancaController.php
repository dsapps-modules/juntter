<?php

namespace App\Http\Controllers;

use App\Services\TransacaoService;
use App\Services\CreditoService;
use App\Services\PixService;
use App\Services\BoletoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CobrancaController extends Controller
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
     * Página principal de cobrança única
     */
    public function index(Request $request)
    {
        try {
            // Buscar todas as transações do estabelecimento atual
            $filtros = [
                'perPage' => 1000, // Buscar o máximo possível
                'page' => 1,
               
            ];

            $transacoes = $this->transacaoService->listarTransacoes($filtros);

          

          

            return view('cobranca.index', compact('transacoes'));
        } catch (\Exception $e) {
            Log::error('Erro ao listar transações: ' . $e->getMessage());
            return view('cobranca.index')->with('error', 'Erro ao carregar transações.');
        }
    }

    /**
     * Criar transação de crédito
     */
    public function criarTransacaoCredito(Request $request)
    {
        try {
            $dados = $request->validate([
                'payment_type' => 'required|in:CREDIT',
                'amount' => 'required|string',
                'installments' => 'required|integer|min:1|max:12',
                'interest' => 'required|in:CLIENT,ESTABLISHMENT',
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
                'client.address.zip_code' => 'required|string|size:8',
                'card.holder_name' => 'required|string|max:255',
                'card.holder_document' => 'nullable|string|max:18',
                'card.card_number' => 'required|string|min:13|max:19',
                'card.expiration_month' => 'required|integer|min:1|max:12',
                'card.expiration_year' => 'required|integer|min:2024',
                'card.security_code' => 'required|string|min:3|max:4',
            ]);

            // Converter valor para centavos (aceita vírgula ou ponto)
            $valor = $dados['amount'];
            
            // Remover símbolos de moeda e espaços
            $valor = preg_replace('/[R$\s]/', '', $valor);
            $valor = str_replace(',', '.', $valor);
            $valorFloat = (float)$valor;
            
            // Validar valor mínimo (1 centavo = R$ 0,01)
            if ($valorFloat < 0.01) {
                throw new \Exception('O valor deve ser pelo menos R$ 0,01');
            }
            
            // Converter para centavos
            $dados['amount'] = (int)($valorFloat * 100);
            
            // Converter campos para números inteiros
            $dados['installments'] = (int)$dados['installments'];
            $dados['card']['expiration_month'] = (int)$dados['card']['expiration_month'];
            $dados['card']['expiration_year'] = (int)$dados['card']['expiration_year'];
            
            // Adicionar establishment_id
            $dados['extra_headers'] = [
                'establishment_id' => '155102'
            ];
            
           
            $sessionId = $request->input('session_id', 'session_' . uniqid());
            $dados['session_id'] = $sessionId;

            $transacao = $this->creditoService->criarTransacaoCredito($dados);

            return redirect()->route('cobranca.index')
                ->with('success', 'Transação de crédito criada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar transação de crédito: ' . $e->getMessage());
            return redirect()->route('cobranca.index')
                ->with('error', 'Erro ao criar transação de crédito: ' . $e->getMessage());
        }
    }

    /**
     * Criar transação PIX
     */
    public function criarTransacaoPix(Request $request)
    {
        try {
            $dados = $request->validate([
                'payment_type' => 'required|in:PIX',
                'amount' => 'required|string',
                'interest' => 'required|in:CLIENT,ESTABLISHMENT',
                'client.first_name' => 'nullable|string|max:255',
                'client.last_name' => 'nullable|string|max:255',
                'client.document' => 'nullable|string|max:18',
                'client.phone' => 'nullable|string|max:20',
                'client.email' => 'nullable|email',
                'info_additional' => 'nullable|array',
                'info_additional.*.key' => 'nullable|string',
                'info_additional.*.value' => 'nullable|string',
            ]);

            // Converter valor para centavos (aceita vírgula ou ponto)
            $valor = $dados['amount'];
            
            // Remover símbolos de moeda e espaços
            $valor = preg_replace('/[R$\s]/', '', $valor);
            $valor = str_replace(',', '.', $valor);
            $valorFloat = (float)$valor;
            
            // Validar valor mínimo (1 centavo = R$ 0,01)
            if ($valorFloat < 0.01) {
                throw new \Exception('O valor deve ser pelo menos R$ 0,01');
            }
            
            $dados['amount'] = (int)($valorFloat * 100);

            // Filtrar info_additional vazios
            if (isset($dados['info_additional'])) {
                $dados['info_additional'] = array_filter($dados['info_additional'], function($item) {
                    return !empty($item['key']) && !empty($item['value']);
                });
                
                // Se não há info_additional válidos, remover o campo
                if (empty($dados['info_additional'])) {
                    unset($dados['info_additional']);
                }
            }

            // Adicionar establishment_id
            $dados['extra_headers'] = [
                'establishment_id' => '155102'
            ];

            $transacao = $this->pixService->criarTransacaoPix($dados);

            // Verificar se a transação foi criada com sucesso
            if (!$transacao) {
                throw new \Exception('Falha ao criar transação PIX');
            }

            // Buscar QR Code pelo ID da transação
            $qrCode = null;
            if (isset($transacao['_id'])) {
                try {
                    $qrCode = $this->pixService->obterQrCodePix($transacao['_id']);
                } catch (\Exception $e) {
                    Log::warning('Erro ao buscar QR Code: ' . $e->getMessage());
                    // Continua sem QR Code
                }
            }

            return redirect()->route('cobranca.index')
                ->with('success', 'Transação PIX criada com sucesso!')
                ->with('pix_data', [
                    'transacao' => $transacao,
                    'qr_code' => $qrCode
                ]);
        } catch (\Exception $e) {
            Log::error('Erro ao criar transação PIX: ' . $e->getMessage());
            return redirect()->route('cobranca.index')
                ->with('error', 'Erro ao criar transação PIX: ' . $e->getMessage());
        }
    }

    /**
     * Criar boleto
     */
    public function criarBoleto(Request $request)
    {
        try {
            $dados = $request->validate([
                'amount' => 'required|string',
                'expiration' => 'required|date_format:Y-m-d',
                'payment_limit_date' => 'nullable|date_format:Y-m-d|after:expiration',
                'recharge' => 'nullable|boolean',
                'client.first_name' => 'required|string|max:255',
                'client.last_name' => 'required|string|max:255',
                'client.document' => 'required|string|max:18',
                'client.email' => 'required|email',
                'client.address.street' => 'required|string|max:255',
                'client.address.number' => 'required|string|max:10',
                'client.address.complement' => 'nullable|string|max:255',
                'client.address.neighborhood' => 'required|string|max:255',
                'client.address.city' => 'required|string|max:255',
                'client.address.state' => 'required|string|size:2',
                'client.address.zip_code' => 'required|string|size:8',
                'instruction.booklet' => 'required|boolean',
                'instruction.description' => 'nullable|string|max:255',
                'instruction.late_fee.mode' => 'required|string|in:PERCENTAGE',
                'instruction.late_fee.amount' => 'required|string',
                'instruction.interest.mode' => 'required|string|in:MONTHLY_PERCENTAGE',
                'instruction.interest.amount' => 'required|string',
                'instruction.discount.mode' => 'required|string|in:PERCENTAGE',
                'instruction.discount.amount' => 'required|string',
                'instruction.discount.limit_date' => 'required|date_format:Y-m-d|before:expiration',
            ]);

            // Converter valores para centavos (aceita vírgula ou ponto)
            $valor = str_replace(',', '.', $dados['amount']);
            $valorFloat = (float)$valor;
            
            // Validar valor mínimo (1 centavo = R$ 0,01)
            if ($valorFloat < 0.01) {
                throw new \Exception('O valor deve ser pelo menos R$ 0,01');
            }
            
            $dados['amount'] = (int)($valorFloat * 100);
            
            // Converter outros valores numéricos
            $dados['instruction']['late_fee']['amount'] = (float)str_replace(',', '.', $dados['instruction']['late_fee']['amount']);
            $dados['instruction']['interest']['amount'] = (float)str_replace(',', '.', $dados['instruction']['interest']['amount']);
            $dados['instruction']['discount']['amount'] = (float)str_replace(',', '.', $dados['instruction']['discount']['amount']);

            // Adicionar establishment_id
            $dados['extra_headers'] = [
                'establishment_id' => '155102'
            ];

            $boleto = $this->boletoService->gerarBoleto($dados);

            return redirect()->route('cobranca.index')
                ->with('success', 'Boleto criado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar boleto: ' . $e->getMessage());
            return redirect()->route('cobranca.index')
                ->with('error', 'Erro ao criar boleto: ' . $e->getMessage());
        }
    }

    /**
     * Simular transação
     */
    public function simularTransacao(Request $request)
    {
        try {
            $dados = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'flag_id' => 'required|integer',
                'gateway_id' => 'required|integer',
                'modality' => 'required|in:ONLINE,PHYSICAL',
                'interest' => 'required|in:CLIENT,ESTABLISHMENT'
            ]);

            // Converter valor para centavos
            $dados['amount'] = (int)($dados['amount'] * 100);

            $dados['extra_headers'] = [
                'establishment_id' => '155102'
            ];

            $simulacao = $this->transacaoService->simularTransacao($dados);

            return redirect()->route('cobranca.index')
                ->with('success', 'Simulação realizada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao simular transação: ' . $e->getMessage());
            return redirect()->route('cobranca.index')
                ->with('error', 'Erro ao simular transação: ' . $e->getMessage());
        }
    }

    /**
     * Obter QR Code PIX
     */
    public function obterQrCodePix($id)
    {
        try {
            $qrCode = $this->pixService->obterQrCodePix($id);

            return redirect()->route('cobranca.index')
                ->with('success', 'QR Code PIX obtido com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao obter QR Code PIX: ' . $e->getMessage());
            return redirect()->route('cobranca.index')
                ->with('error', 'Erro ao obter QR Code PIX: ' . $e->getMessage());
        }
    }

    /**
     * Detalhes da transação
     */
    public function detalhesTransacao($id)
    {
        try {
            // Buscar detalhes da transação específica
            $transacao = $this->transacaoService->detalhesTransacao($id);

            if (!$transacao) {
                return redirect()->route('cobranca.index')
                    ->with('error', 'Transação não encontrada.');
            }

            // Preparar breadcrumb dinâmico
            $breadcrumbItems = [
                ['label' => 'Cobranças', 'icon' => 'fas fa-credit-card', 'url' => route('cobranca.index')]
            ];
            
            if(request('from') == 'saldoextrato') {
                $breadcrumbItems[] = ['label' => 'Saldo e Extrato', 'icon' => 'fas fa-chart-bar', 'url' => route('cobranca.saldoextrato')];
            }
            
            $breadcrumbItems[] = ['label' => 'Detalhes da Transação', 'icon' => 'fas fa-eye', 'url' => '#'];

            return view('cobranca.detalhes', compact('transacao', 'breadcrumbItems'));
        } catch (\Exception $e) {
            Log::error('Erro ao buscar detalhes da transação: ' . $e->getMessage());
            return redirect()->route('cobranca.index')
                ->with('error', 'Erro ao buscar detalhes da transação: ' . $e->getMessage());
        }
    }

    /**
     * Estornar transação
     */
    public function estornarTransacao($id)
    {
        try {
            // Buscar detalhes da transação para verificar se pode ser estornada
            $transacao = $this->transacaoService->detalhesTransacao($id);

            if (!$transacao) {
                return redirect()->route('cobranca.index')
                    ->with('error', 'Transação não encontrada.');
            }

            // Verificar se a transação pode ser estornada/cancelada
            if (!in_array($transacao['status'] ?? '', ['PAID', 'APPROVED', 'PENDING'])) {
                return redirect()->route('cobranca.index')
                    ->with('error', 'Apenas transações pagas, aprovadas ou pendentes podem ser estornadas/canceladas.');
            }

            // Verificar se já foi estornada
            if (($transacao['status'] ?? '') === 'REFUNDED') {
                return redirect()->route('cobranca.index')
                    ->with('error', 'Esta transação já foi estornada.');
            }

            // Chamar serviço para estornar a transação
            $resultado = $this->transacaoService->estornarTransacao($id);

            if ($resultado && isset($resultado['status'])) {
                $valorFormatado = number_format(($transacao['amount'] ?? 0) / 100, 2, ',', '.');
                $acao = ($transacao['status'] ?? '') === 'PENDING' ? 'cancelada' : 'estornada';
                
                return redirect()->route('cobranca.index')
                    ->with('success', "Transação de R$ {$valorFormatado} {$acao} com sucesso!");
            } else {
                return redirect()->route('cobranca.index')
                    ->with('error', 'Erro ao processar transação. Tente novamente.');
            }
        } catch (\Exception $e) {
            Log::error('Erro ao estornar transação: ' . $e->getMessage());
            return redirect()->route('cobranca.index')
                ->with('error', 'Erro ao estornar transação: ' . $e->getMessage());
        }
    }

    /**
     * Saldo e Extrato
     */
    public function saldoExtrato(Request $request)
    {
        try {
            // Preparar filtros para lançamentos futuros (saldo - apenas headers obrigatórios)
            $filtrosSaldo = [
                'extra_headers' => [
                    'establishment_id' => '155102'
                ]
            ];

            // Buscar lançamentos futuros (saldo) - sem filtros, apenas headers obrigatórios
            $saldo = $this->transacaoService->lancamentosFuturos($filtrosSaldo);

            // Preparar filtros para extrato detalhado
            $filtrosExtrato = [
                'extra_headers' => [
                    'establishment_id' => '155102'
                ]
            ];

            // Filtros para extrato detalhado (campos obrigatórios da API)
            $filtrosExtratoData = [];
            
            // Gateway é obrigatório - se não foi especificado, usa PAYTIME como padrão
            $filtrosExtratoData['gateway_authorization'] = $request->gateway_authorization ?? 'PAYTIME';
            
            // Data de liberação (date) - se especificada
            if ($request->filled('data_inicio')) {
                $filtrosExtratoData['date'] = $request->data_inicio;
            } else {
                $filtrosExtratoData['date'] = date('Y-m-d');
            }



            // Sempre adiciona filters pois os campos são obrigatórios para extrato
            $filtrosExtrato['filters'] = json_encode($filtrosExtratoData);

            // Adicionar busca livre
            if ($request->filled('search')) {
                $filtrosExtrato['search'] = $request->search;
            }

            // Adicionar paginação
            if ($request->filled('page')) {
                $filtrosExtrato['page'] = $request->page;
            }

            if ($request->filled('perPage')) {
                $filtrosExtrato['perPage'] = $request->perPage;
            }

            // Buscar lançamentos futuros diários (extrato detalhado)
            $extrato = $this->transacaoService->lancamentosFuturosDiarios($filtrosExtrato);

            // Preparar dados para a view
            $dados = [
                'saldo' => $saldo,
                'extrato' => $extrato,
                'filtros' => [
                    'gateway_authorization' => $request->gateway_authorization,
                    'data_inicio' => $request->data_inicio,
                    'search' => $request->search,
                    'page' => $request->page ?? 1,
                    'perPage' => $request->perPage ?? 20
                ]
            ];

            return view('cobranca.saldoextrato', $dados);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar saldo e extrato: ' . $e->getMessage());
            return view('cobranca.saldoextrato')->with('error', 'Erro ao carregar saldo e extrato: ' . $e->getMessage());
        }
    }
}

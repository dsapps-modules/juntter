<?php

namespace App\Http\Controllers;

use App\Services\TransacaoService;
use App\Services\CreditoService;
use App\Services\PixService;
use App\Services\BoletoService;
use App\Models\LinkPagamento;
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
     * Converte valor brasileiro para centavos
     * Aceita formatos: 1.100,00, 1100,00, 1100.00, 1100
     */
    private function converterValorParaCentavos($valor)
    {
        // Remover símbolos de moeda e espaços
        $valor = preg_replace('/[R$\s]/', '', $valor);
        
        // Se tem vírgula, é formato brasileiro (1.100,00)
        if (strpos($valor, ',') !== false) {
            // Remover pontos (separadores de milhares) e trocar vírgula por ponto
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }
        
        $valorFloat = (float)$valor;
        
        // Validar valor mínimo (1 centavo = R$ 0,01)
        if ($valorFloat < 0.01) {
            throw new \Exception('O valor deve ser pelo menos R$ 0,01');
        }
        
        return (int)($valorFloat * 100);
    }

    /**
     * Página principal de cobrança única
     */
    public function index(Request $request)
    {
        try {
            // Obter mês e ano do filtro
            $mesAtual = $request->input('mes'); // pode ser vazio (Todos) ou um número
            $anoAtual = $request->input('ano'); // pode ser vazio (Todos) ou um ano
            
            // Preparar filtros base
            $filtrosData = [
                'establishment.id' => auth()->user()?->vendedor?->estabelecimento_id
            ];
            
            // Aplicar filtro de data baseado no que foi especificado
            if (!empty($mesAtual) || !empty($anoAtual)) {
                $dataInicio = null;
                $dataFim = null;
                
                if (!empty($mesAtual) && !empty($anoAtual)) {
                    // Mês e ano específicos
                    $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mesAtual, 1, $anoAtual));
                    $dataFim = date('Y-m-t', mktime(0, 0, 0, $mesAtual, 1, $anoAtual));
                } elseif (!empty($mesAtual)) {
                    // Só mês especificado - usar ano atual
                    $anoAtual = date('Y');
                    $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mesAtual, 1, $anoAtual));
                    $dataFim = date('Y-m-t', mktime(0, 0, 0, $mesAtual, 1, $anoAtual));
                } elseif (!empty($anoAtual)) {
                    // Só ano especificado - usar todo o ano
                    $dataInicio = date('Y-m-d', mktime(0, 0, 0, 1, 1, $anoAtual));
                    $dataFim = date('Y-m-t', mktime(0, 0, 0, 12, 1, $anoAtual));
                }
                
                if ($dataInicio && $dataFim) {
                    $filtrosData['created_at'] = [
                        'min' => $dataInicio,
                        'max' => $dataFim
                    ];
                }
            }
            // Se ambos estiverem vazios (Todos), não aplica filtro de data
            
            // Buscar todas as transações do estabelecimento atual
            $filtros = [
                'filters' => json_encode($filtrosData),
                'perPage' => 1000, // Buscar o máximo possível
                'page' => 1,
            ];

            $transacoes = $this->transacaoService->listarTransacoes($filtros);

            // Filtrar transações de crédito para mostrar apenas à vista (1x) antes de mesclar com boletos
            if (isset($transacoes['data']) && is_array($transacoes['data'])) {
                $transacoes['data'] = array_filter($transacoes['data'], function($transacao) {
                    // Se não for transação de crédito, manter
                    if (!isset($transacao['type']) || $transacao['type'] !== 'CREDIT') {
                        return true;
                    }
                    
                    // Se for crédito, verificar se é à vista (1x)
                    $installments = $transacao['installments'] ?? 1;
                    return $installments == 1;
                });
                
                // Recalcular total após filtragem
                $transacoes['total'] = count($transacoes['data']);
            }

            // Também buscar boletos e mesclar na mesma lista para exibir junto
            try {
                $filtrosBoletos = [
                    'perPage' => 1000,
                    'page' => 1,
                    'filters' => json_encode($filtrosData)
                ];
                $boletos = $this->boletoService->listarBoletos($filtrosBoletos);

                if (isset($boletos['data']) && is_array($boletos['data'])) {
                    // Adaptar estrutura dos boletos para o formato da tabela de transações
                    $boletosAdaptados = array_map(function($b) {
                        return [
                            '_id' => $b['_id'] ?? ($b['id'] ?? null),
                            'type' => 'BOLETO',
                            'amount' => $b['amount'] ?? 0,
                            'fees' => $b['fees'] ?? 0,
                            'gateway_authorization' => $b['gateway_authorization'] ?? ($b['gateway_key'] ?? null),
                            'created_at' => $b['created_at'] ?? ($b['updated_at'] ?? null),
                            'status' => $b['status'] ?? null,
                        ];
                    }, $boletos['data']);

                    // Garantir estrutura base
                    if (!isset($transacoes['data']) || !is_array($transacoes['data'])) {
                        $transacoes['data'] = [];
                        $transacoes['total'] = 0;
                        $transacoes['page'] = 1;
                    }

                    // Mesclar e ordenar por data desc
                    $transacoes['data'] = array_merge($transacoes['data'], $boletosAdaptados);
                    
                    // Filtrar transações de crédito para mostrar apenas à vista (1x)
                    $transacoes['data'] = array_filter($transacoes['data'], function($transacao) {
                        // Se não for transação de crédito, manter
                        if (!isset($transacao['type']) || $transacao['type'] !== 'CREDIT') {
                            return true;
                        }
                        
                        // Se for crédito, verificar se é à vista (1x)
                        $installments = $transacao['installments'] ?? 1;
                        return $installments == 1;
                    });
                    
                    usort($transacoes['data'], function($a, $b) {
                        $da = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
                        $db = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
                        return $db <=> $da;
                    });

                    // Recalcular total aproximado
                    $transacoes['total'] = count($transacoes['data']);
                }
            } catch (\Exception $e) {
                // Silenciar falhas de boletos para não quebrar a listagem principal
            }

         

            return view('cobranca.index', compact('transacoes', 'mesAtual', 'anoAtual'));
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
            $valor = $this->converterValorParaCentavos($request->input('amount')) / 100;
            $parcelas = (int) $request->input('installments');

            if($parcelas > 1) {
                $valorMinimoParcela = 5.00;
                $valorParcela = $valor / $parcelas;

                if($valorParcela < $valorMinimoParcela) {
                    return redirect()->route('cobranca.index')
                        ->with('error', 'O valor mínimo de cada parcela é de R$ 5,00');
                } 
            }

            $dados = $request->validate([
                'payment_type' => 'required|in:CREDIT',
                'amount' => 'required|string',
                'installments' => 'required|integer|min:1|max:18',
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

            // Converter valor para centavos usando função helper
            $dados['amount'] = $this->converterValorParaCentavos($dados['amount']);
            
            // Converter campos para números inteiros
            $dados['installments'] = (int)$dados['installments'];
            $dados['card']['expiration_month'] = (int)$dados['card']['expiration_month'];
            $dados['card']['expiration_year'] = (int)$dados['card']['expiration_year'];
            
            // Tratar campos opcionais que a API espera como string
            if (empty($dados['client']['last_name'])) {
                $dados['client']['last_name'] = '';
            }
            if (empty($dados['client']['address']['complement'])) {
                $dados['client']['address']['complement'] = '';
            }
            if (empty($dados['card']['holder_document'])) {
                $dados['card']['holder_document'] = '';
            }
            
            // Adicionar establishment_id
            $dados['extra_headers'] = [
                'establishment_id' => auth()->user()?->vendedor?->estabelecimento_id
            ];
            
            $sessionId = $request->input('session_id', 'session_' . uniqid());
            $dados['session_id'] = $sessionId;

            $transacao = $this->creditoService->criarTransacaoCredito($dados);
            
        

            return redirect()->route('cobranca.index')
                ->with('success', 'Transação de crédito criada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar transação de crédito', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);
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
                'info_additional' => 'nullable|string|max:500',
            ]);

            // Converter valor para centavos usando função helper
            $dados['amount'] = $this->converterValorParaCentavos($dados['amount']);

            // Processar info_additional como string simples
            if (isset($dados['info_additional']) && !empty($dados['info_additional'])) {
                // Converter para o formato esperado pela API com chave padrão
                $dados['info_additional'] = [
                    [
                        'key' => 'info_adicional',
                        'value' => $dados['info_additional']
                    ]
                ];
            } else {
                unset($dados['info_additional']);
            }

            // Adicionar establishment_id
            $dados['extra_headers'] = [
                'establishment_id' => auth()->user()?->vendedor?->estabelecimento_id
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
            Log::error('Erro ao criar transação PIX', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);
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
                'instruction.late_fee.amount' => 'required|string',
                'instruction.interest.amount' => 'required|string',
                'instruction.discount.amount' => 'required|string',
                'instruction.discount.limit_date' => 'required|date_format:Y-m-d|before:expiration',
            ]);

            // Converter valores para centavos usando função helper
            $dados['amount'] = $this->converterValorParaCentavos($dados['amount']);
            
            // Adicionar os campos mode automaticamente (são sempre os mesmos para boleto)
            $dados['instruction']['late_fee']['mode'] = 'PERCENTAGE';
            $dados['instruction']['interest']['mode'] = 'MONTHLY_PERCENTAGE';
            $dados['instruction']['discount']['mode'] = 'PERCENTAGE';
            
            // Converter outros valores numéricos usando função helper
            $dados['instruction']['late_fee']['amount'] = $this->converterValorParaCentavos($dados['instruction']['late_fee']['amount']) / 100.0;
            $dados['instruction']['interest']['amount'] = $this->converterValorParaCentavos($dados['instruction']['interest']['amount']) / 100.0;
            $dados['instruction']['discount']['amount'] = $this->converterValorParaCentavos($dados['instruction']['discount']['amount']) / 100.0;

            // Garantir tipos booleanos corretos exigidos pela API (normalização simples)
            $dados['recharge'] = $request->boolean('recharge');
            $dados['instruction']['booklet'] = $request->boolean('instruction.booklet');

            // Adicionar establishment_id
            $dados['extra_headers'] = [
                'establishment_id' => auth()->user()?->vendedor?->estabelecimento_id
            ];

            $boleto = $this->boletoService->gerarBoleto($dados);

            return redirect()->route('cobranca.index')
                ->with('success', 'Boleto criado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar boleto', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);
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
                'amount' => 'required|string',
                'flag_id' => 'required|integer|in:1,2,3,4,5,6,8',
                'interest' => 'required|in:CLIENT,ESTABLISHMENT'
            ]);

            // Converter valor para centavos usando função helper
            $dados['amount'] = $this->converterValorParaCentavos($dados['amount']);

            $dados['flag_id'] = (int)$dados['flag_id'];
            $dados['gateway_id'] = 4; // SUBPAYTIME
            $dados['modality'] = 'ONLINE';

            $dados['extra_headers'] = [
                'establishment_id' => auth()->user()?->vendedor?->estabelecimento_id
            ];

            $simulacao = $this->transacaoService->simularTransacao($dados);

            

            return view('cobranca.simular', compact('simulacao'))
                ->with('success', 'Simulação realizada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao simular transação: ' . $e->getMessage());
            return redirect()->route('cobranca.simular')
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
     * Detalhes do boleto (estrutura distinta da transação)
     */
    public function detalhesBoleto($id)
    {
        try {
            $boleto = $this->boletoService->consultarBoleto($id);

            if (!$boleto) {
                return redirect()->route('cobranca.index')
                    ->with('error', 'Boleto não encontrado.');
            }

            return view('cobranca.boleto-detalhes', compact('boleto'));
        } catch (\Exception $e) {
            Log::error('Erro ao buscar detalhes do boleto: ' . $e->getMessage());
            return redirect()->route('cobranca.index')
                ->with('error', 'Erro ao buscar detalhes do boleto: ' . $e->getMessage());
        }
    }

    /**
     * Criar link de pagamento para crédito à vista
     */
    public function criarCreditoVista(Request $request)
    {
        try {
            $dados = $request->validate([
                'valor' => 'required|string',
                'juros' => 'required|in:CLIENT,ESTABLISHMENT',
                'descricao' => 'nullable|string|max:1000',
                'data_expiracao' => 'required|date|after:today',
            ]);

            $estabelecimentoId = auth()->user()?->vendedor?->estabelecimento_id;
            
            if (!$estabelecimentoId) {
                return redirect()->back()->with('error', 'Estabelecimento não encontrado');
            }

            // Converter valor para centavos
            $valorCentavos = $this->converterValorParaCentavos($dados['valor']);
            $valorFloat = $valorCentavos / 100;

            // Criar link de pagamento
            $link = LinkPagamento::create([
                'estabelecimento_id' => $estabelecimentoId,
                'codigo_unico' => LinkPagamento::gerarCodigoUnico(),
                'descricao' => $dados['descricao'],
                'valor' => $valorFloat,
                'valor_centavos' => $valorCentavos,
                'parcelas' => 1, // Apenas à vista
                'is_avista' => true, // Marcar como à vista
                'juros' => $dados['juros'],
                'data_expiracao' => $dados['data_expiracao'],
                'dados_cliente' => [
                    'nome_obrigatorio' => true,
                    'email_obrigatorio' => true,
                    'telefone_obrigatorio' => true,
                    'documento_obrigatorio' => true,
                    'endereco_obrigatorio' => true,
                    'preenchidos' => null, // Cliente preenche na página
                ],
                'url_retorno' => null,
                'url_webhook' => null,
                'status' => 'ATIVO'
            ]);

            return redirect()->route('links-pagamento.show', $link->id)
                ->with('success', 'Link de pagamento à vista criado com sucesso!');

        } catch (\Exception $e) {
            Log::error('Erro ao criar link de crédito à vista: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao criar link: ' . $e->getMessage())
                ->withInput();
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
     * Mostrar formulário de simulação de transação
     */
    public function mostrarSimulacao()
    {
        return view('cobranca.simular');
    }

    /**
     * Saldo e Extrato
     */
    public function saldoExtrato(Request $request)
    {
        try {
            // Obter mês e ano do filtro
            $mesAtual = $request->input('mes'); // pode ser vazio (Todos) ou um número
            $anoAtual = $request->input('ano'); // pode ser vazio (Todos) ou um ano
            
            // Preparar filtros para lançamentos futuros (saldo - apenas headers obrigatórios)
            $filtrosSaldo = [
                'extra_headers' => [
                    'establishment_id' => auth()->user()?->vendedor?->estabelecimento_id
                ]
            ];

            // Buscar lançamentos futuros (saldo) - sem filtros, apenas headers obrigatórios
            $saldo = $this->transacaoService->lancamentosFuturos($filtrosSaldo);

            // Aplicar filtros de data localmente (igual ao dashboard)
            if (!empty($mesAtual) || !empty($anoAtual)) {
                $saldo = $this->aplicarFiltrosDataSaldo($saldo, $mesAtual, $anoAtual);
            }

            // Preparar filtros para extrato detalhado
            $filtrosExtrato = [
                'extra_headers' => [
                    'establishment_id' => auth()->user()?->vendedor?->estabelecimento_id
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

            // Preparar projeção mensal (5 meses: 2 para trás, atual, 2 para frente)
            $projecaoMensal = $this->prepararProjecaoMensal($saldo, $mesAtual, $anoAtual);

            // Preparar dados para a view
            $dados = [
                'saldo' => $saldo,
                'extrato' => $extrato,
                'projecaoMensal' => $projecaoMensal,
                'mesAtual' => $mesAtual,
                'anoAtual' => $anoAtual,
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

    /**
     * Aplicar filtros de data ao saldo (igual ao dashboard)
     */
    private function aplicarFiltrosDataSaldo($saldo, $mes, $ano)
    {
        if (empty($mes) && empty($ano)) {
            return $saldo; // Sem filtro
        }

        $saldoFiltrado = $saldo;
        
        if (!empty($mes) && !empty($ano)) {
            // Filtro completo: mês + ano específicos
            $mesFiltro = (int)$mes;
            $anoFiltro = (int)$ano;
            
            // Procurar no array de meses
            if (isset($saldo['months']) && is_array($saldo['months'])) {
                $valorFiltrado = 0;
                foreach ($saldo['months'] as $mesLancamento) {
                    if (isset($mesLancamento['month']) && isset($mesLancamento['year']) && 
                        $mesLancamento['month'] == $mesFiltro && $mesLancamento['year'] == $anoFiltro) {
                        $valorFiltrado = $mesLancamento['amount'];
                        break;
                    }
                }
                $saldoFiltrado['total']['amount'] = $valorFiltrado;
            }
            
            // Para filtro completo, calcular períodos relativos
            $saldoFiltrado['thirtyDays']['amount'] = $this->calcularPeriodoRelativo($saldo, $mesFiltro, $anoFiltro, 30);
            $saldoFiltrado['sevenDays']['amount'] = $this->calcularPeriodoRelativo($saldo, $mesFiltro, $anoFiltro, 7);
            
        } elseif (!empty($ano)) {
            // Apenas ano: somar todos os meses daquele ano
            $anoFiltro = (int)$ano;
            
            if (isset($saldo['months']) && is_array($saldo['months'])) {
                $valorFiltrado = 0;
                foreach ($saldo['months'] as $mesLancamento) {
                    if (isset($mesLancamento['year']) && $mesLancamento['year'] == $anoFiltro) {
                        $valorFiltrado += $mesLancamento['amount'];
                    }
                }
                $saldoFiltrado['total']['amount'] = $valorFiltrado;
            }
            
            // Para filtro de ano, usar mês atual como referência
            $saldoFiltrado['thirtyDays']['amount'] = $this->calcularPeriodoRelativo($saldo, date('n'), $anoFiltro, 30);
            $saldoFiltrado['sevenDays']['amount'] = $this->calcularPeriodoRelativo($saldo, date('n'), $anoFiltro, 7);
            
        } elseif (!empty($mes)) {
            // Apenas mês: usar ano atual
            $mesFiltro = (int)$mes;
            $anoAtual = (int)date('Y');
            
            if (isset($saldo['months']) && is_array($saldo['months'])) {
                $valorFiltrado = 0;
                foreach ($saldo['months'] as $mesLancamento) {
                    if (isset($mesLancamento['month']) && isset($mesLancamento['year']) && 
                        $mesLancamento['month'] == $mesFiltro && $mesLancamento['year'] == $anoAtual) {
                        $valorFiltrado = $mesLancamento['amount'];
                        break;
                    }
                }
                $saldoFiltrado['total']['amount'] = $valorFiltrado;
            }
            
            // Para filtro de mês, calcular períodos relativos
            $saldoFiltrado['thirtyDays']['amount'] = $this->calcularPeriodoRelativo($saldo, $mesFiltro, $anoAtual, 30);
            $saldoFiltrado['sevenDays']['amount'] = $this->calcularPeriodoRelativo($saldo, $mesFiltro, $anoAtual, 7);
        }

        return $saldoFiltrado;
    }

    /**
     * Calcular período relativo (30 ou 7 dias) em relação ao mês filtrado
     */
    private function calcularPeriodoRelativo($saldo, $mes, $ano, $dias)
    {
        if (!isset($saldo['calendar']) || !is_array($saldo['calendar'])) {
            return 0;
        }

        $valorTotal = 0;
        $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $ano));
        $dataFim = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
        
        // Calcular data limite baseada no período
        $dataLimite = date('Y-m-d', strtotime($dataInicio . " +{$dias} days"));
        
        // Se a data limite ultrapassa o mês, usar o final do mês
        if ($dataLimite > $dataFim) {
            $dataLimite = $dataFim;
        }

        // Procurar lançamentos dentro do período usando dados do calendar
        foreach ($saldo['calendar'] as $lancamento) {
            if (isset($lancamento['date']) && isset($lancamento['amount'])) {
                $dataLancamento = $lancamento['date'];
                
                // Verificar se a data está dentro do período
                if ($dataLancamento >= $dataInicio && $dataLancamento <= $dataLimite) {
                    $valorTotal += $lancamento['amount'];
                }
            }
        }

        return (int)$valorTotal;
    }

    /**
     * Preparar projeção mensal com 5 meses (2 para trás, atual, 2 para frente)
     */
    private function prepararProjecaoMensal($saldo, $mesFiltro = null, $anoFiltro = null)
    {
        // Se não há filtro, usar mês/ano atual
        $mesAtual = $mesFiltro ?: date('n'); // Mês atual (1-12)
        $anoAtual = $anoFiltro ?: date('Y'); // Ano atual
        
        // Criar array com 5 meses: 2 para trás, atual, 2 para frente
        $mesesExibir = [];
        for ($i = -2; $i <= 2; $i++) {
            $mesCalculado = $mesAtual + $i;
            $anoCalculado = $anoAtual;
            
            // Ajustar mês e ano se necessário
            if ($mesCalculado < 1) {
                $mesCalculado += 12;
                $anoCalculado--;
            } elseif ($mesCalculado > 12) {
                $mesCalculado -= 12;
                $anoCalculado++;
            }
            
            $mesesExibir[] = [
                'month' => $mesCalculado,
                'year' => $anoCalculado,
                'amount' => 0, // Valor padrão
                'is_current' => ($mesCalculado == $mesAtual && $anoCalculado == $anoAtual),
                'formatted_date' => date('M/Y', mktime(0, 0, 0, $mesCalculado, 1, $anoCalculado)),
                'formatted_amount' => 'R$ 0,00'
            ];
        }
        
        // Preencher com valores reais da API
        if (isset($saldo['months']) && is_array($saldo['months'])) {
            foreach ($saldo['months'] as $mesApi) {
                foreach ($mesesExibir as &$mesExibir) {
                    if ($mesExibir['month'] == $mesApi['month'] && $mesExibir['year'] == $mesApi['year']) {
                        $mesExibir['amount'] = $mesApi['amount'];
                        $mesExibir['formatted_amount'] = 'R$ ' . number_format($mesApi['amount'] / 100, 2, ',', '.');
                        break;
                    }
                }
            }
        }
        
        return $mesesExibir;
    }

    /**
     * Listar planos comerciais disponíveis
     */
    public function listarPlanos(Request $request)
    {
        try {
            // Buscar todos os planos - DataTable faz a filtragem
            $planos = $this->transacaoService->listarPlanosComerciais();

            return view('cobranca.planos', compact('planos'));
        } catch (\Exception $e) {
            Log::error('Erro ao listar planos: ' . $e->getMessage());
            return view('cobranca.planos')->with('error', 'Erro ao carregar planos: ' . $e->getMessage());
        }
    }

    /**
     * Exibir detalhes de um plano comercial
     */
    public function detalhesPlano($id)
    {
        try {
            $plano = $this->transacaoService->detalhesPlanoComercial($id);
            
            if (!$plano) {
                return redirect()->route('cobranca.planos')
                    ->with('error', 'Plano não encontrado.');
            }

            // Compactar parcelas de crédito para cada bandeira
            if (isset($plano['flags'])) {
                foreach ($plano['flags'] as &$flag) {
                    if (isset($flag['fees']['credit'])) {
                        $flag['parcelas_compactadas'] = $this->compactarParcelasCredito($flag['fees']['credit']);
                    }
                }
            }

            return view('cobranca.plano-detalhes', compact('plano'));
        } catch (\Exception $e) {
            Log::error('Erro ao buscar detalhes do plano: ' . $e->getMessage());
            return redirect()->route('cobranca.planos')
                ->with('error', 'Erro ao carregar detalhes do plano: ' . $e->getMessage());
        }
    }

    /**
     * Compacta parcelas de crédito agrupando aquelas com taxas iguais
     */
    private function compactarParcelasCredito($parcelas)
    {
        $resultado = [];
        $taxaAnterior = null;
        $inicioRange = null;
        
        // Agrupar parcelas com taxas iguais
        for ($i = 1; $i <= 18; $i++) {
            if (isset($parcelas[$i.'x'])) {
                $taxaAtual = $parcelas[$i.'x'];
                
                if ($taxaAtual === $taxaAnterior) {
                    // Continua o range
                    $inicioRange = $inicioRange ?? $i;
                } else {
                    // Finaliza o range anterior
                    if ($inicioRange !== null) {
                        if ($inicioRange === $i - 1) {
                            $resultado[] = [
                                'parcela' => $inicioRange.'x',
                                'taxa' => $taxaAnterior
                            ];
                        } else {
                            $resultado[] = [
                                'parcela' => $inicioRange.'x-'.($i-1).'x',
                                'taxa' => $taxaAnterior
                            ];
                        }
                    }
                    
                    // Inicia novo range
                    $inicioRange = $i;
                    $taxaAnterior = $taxaAtual;
                }
            }
        }
        
        // Finaliza o último range
        if ($inicioRange !== null) {
            if ($inicioRange === 18) {
                $resultado[] = [
                    'parcela' => $inicioRange.'x',
                    'taxa' => $taxaAnterior
                ];
            } else {
                $resultado[] = [
                    'parcela' => $inicioRange.'x-18x',
                    'taxa' => $taxaAnterior
                ];
            }
        }
        
        return $resultado;
    }
}

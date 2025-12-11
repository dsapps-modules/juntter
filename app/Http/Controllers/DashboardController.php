<?php

namespace App\Http\Controllers;

use App\Helpers\DashHelper;
use App\Services\BoletoService;
use App\Services\EstabelecimentoService;
use App\Services\TransacaoService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $estabelecimentoService;

    protected $transacaoService;

    protected $boletoService;

    public function __construct(EstabelecimentoService $estabelecimentoService, TransacaoService $transacaoService, BoletoService $boletoService)
    {
        $this->estabelecimentoService = $estabelecimentoService;
        $this->transacaoService = $transacaoService;
        $this->boletoService = $boletoService;
    }

    /**
     * Dashboard do Super Administrador
     */
    public function superAdminDashboard()
    {
        $saldos = [
            'disponivel' => 'R$ 15.500,00',
            'transito' => 'R$ 2.300,00',
            'bloqueado_cartao' => 'R$ 850,00',
            'bloqueado_boleto' => 'R$ 1.200,00',
        ];

        $metricas = [
            [
                'valor' => '25',
                'label' => 'Total de Usuários',
                'icone' => 'fas fa-users',
                'cor' => 'metric-icon-blue',
            ],
            [
                'valor' => '142',
                'label' => 'Transações Hoje',
                'icone' => 'fas fa-exchange-alt',
                'cor' => 'metric-icon-green',
            ],
            [
                'valor' => '8',
                'label' => 'Sistemas Ativos',
                'icone' => 'fas fa-server',
                'cor' => 'metric-icon-teal',
            ],
            [
                'valor' => 'R$ 45.200,00',
                'label' => 'Receita Total',
                'icone' => 'fas fa-chart-line',
                'cor' => 'metric-icon-cyan',
            ],
            [
                'valor' => '98.5%',
                'label' => 'Uptime do Sistema',
                'icone' => 'fas fa-check-circle',
                'cor' => 'metric-icon-green',
            ],
            [
                'valor' => '3',
                'label' => 'Alertas Pendentes',
                'icone' => 'fas fa-exclamation-triangle',
                'cor' => 'metric-icon-red',
            ],
        ];

        return view('dashboard.super_admin', compact('saldos', 'metricas'));
    }

    /**
     * Dashboard do Administrador - Consolidação Geral da Juntter
     */
    public function adminDashboard(Request $request)
    {
        set_time_limit(0);

        $mes = $request->input('mes') ?? date('m'); // pode ser vazio (Todos) ou um número
        $ano = $request->input('ano') ?? date('Y'); // pode ser vazio (Todos) ou um ano
        $dia = new DateTime("$ano-$mes-01");

        $filtros = [
            'filters' => json_encode([
                'created_at' => [
                    'min' => "$ano-$mes-01",
                    'max' => $dia->format('Y-m-t'),
                ],
            ]),
            'sorters' => json_encode([
                'column' => 'created_at',
                'direction' => 'ASC',
            ]),
            'perPage' => 53500
        ];

        $response = $this->transacaoService->listarTransacoes($filtros);
        $metrics = DashHelper::buildDashboardMetrics($response);

        return view('dashboard.admin', compact('metrics', 'mes', 'ano'));
    }

    private function buscarDadosConsolidados($mes = null, $ano = null)
    {
        try {
            // Buscar todos os estabelecimentos primeiro
            $estabelecimentos = $this->estabelecimentoService->listarEstabelecimentos();
            $estabelecimentosIds = [];

            if (isset($estabelecimentos['data']) && is_array($estabelecimentos['data'])) {
                foreach ($estabelecimentos['data'] as $estabelecimento) {
                    if (isset($estabelecimento['id'])) {
                        $estabelecimentosIds[] = $estabelecimento['id'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Erro ao buscar estabelecimentos: '.$e->getMessage());
            $estabelecimentosIds = [];
        }

        // Inicializar variáveis de consolidação
        $saldosConsolidados = [
            'disponivel' => 0,
            'transito' => 0,
            'processamento' => 0,
            'bloqueado_cartao' => 0,
            'bloqueado_boleto' => 0,
        ];

        $totalTransacoes = 0;
        $volumeBruto = 0;
        $volumeLiquido = 0;
        $totalTaxas = 0;
        $volumePorMetodo = ['CREDIT' => 0, 'DEBIT' => 0, 'PIX' => 0, 'BOLETO' => 0];
        $taxasPorMetodo = ['CREDIT' => 0, 'DEBIT' => 0, 'PIX' => 0, 'BOLETO' => 0];
        $transacoesHoje = 0;
        $transacoesPagas = 0;
        $transacoesFalhadas = 0;
        $transacoesProcessadasIds = []; // Para evitar duplicatas
        $transacoesPorTipo = [
            'CREDIT' => 0,
            'DEBIT' => 0,
            'PIX' => 0,
            'BOLETO' => 0,
        ];
        $transacoesPorStatus = [
            'PAID' => 0,
            'PENDING' => 0,
            'APPROVED' => 0,
            'FAILED' => 0,
            'CANCELED' => 0,
            'REFUNDED' => 0,
            'DISPUTED' => 0,
            'CHARGEBACK' => 0,
        ];

        // Para cada estabelecimento, buscar dados e consolidar
        foreach ($estabelecimentosIds as $estabelecimentoId) {
            try {
                // 1. Buscar lançamentos futuros (saldo disponível)
                $filtrosSaldo = [
                    'extra_headers' => [
                        'establishment_id' => $estabelecimentoId,
                    ],
                ];

                $saldo = $this->transacaoService->lancamentosFuturos($filtrosSaldo);

                if ($saldo && isset($saldo['total']['amount'])) {
                    $saldoDisponivel = 0;

                    if (empty($mes) && empty($ano)) {
                        // Sem filtro: mostrar total geral dos lançamentos futuros
                        $saldoDisponivel = $saldo['total']['amount'];
                    } elseif (! empty($mes) && ! empty($ano)) {
                        // Filtro completo: mês + ano específicos
                        $mesFiltro = (int) $mes;
                        $anoFiltro = (int) $ano;

                        // Procurar no array de meses
                        if (isset($saldo['months']) && is_array($saldo['months'])) {
                            foreach ($saldo['months'] as $mesLancamento) {
                                if (isset($mesLancamento['month']) && isset($mesLancamento['year']) &&
                                    $mesLancamento['month'] == $mesFiltro && $mesLancamento['year'] == $anoFiltro) {
                                    $saldoDisponivel = $mesLancamento['amount'];
                                    break;
                                }
                            }
                        }
                    } elseif (! empty($ano)) {
                        // Apenas ano: somar todos os meses daquele ano
                        $anoFiltro = (int) $ano;

                        if (isset($saldo['months']) && is_array($saldo['months'])) {
                            foreach ($saldo['months'] as $mesLancamento) {
                                if (isset($mesLancamento['year']) && $mesLancamento['year'] == $anoFiltro) {
                                    $saldoDisponivel += $mesLancamento['amount'];
                                }
                            }
                        }
                    } elseif (! empty($mes)) {
                        // Apenas mês: usar ano atual
                        $mesFiltro = (int) $mes;
                        $anoAtual = (int) date('Y');

                        if (isset($saldo['months']) && is_array($saldo['months'])) {
                            foreach ($saldo['months'] as $mesLancamento) {
                                if (isset($mesLancamento['month']) && isset($mesLancamento['year']) &&
                                    $mesLancamento['month'] == $mesFiltro && $mesLancamento['year'] == $anoAtual) {
                                    $saldoDisponivel = $mesLancamento['amount'];
                                    break;
                                }
                            }
                        }
                    }

                    $saldosConsolidados['disponivel'] += $saldoDisponivel;
                }

                // 2. Buscar lançamentos futuros diários para valores em trânsito
                $lancamentosDiarios = $this->transacaoService->lancamentosFuturosDiarios($filtrosSaldo);

                if ($lancamentosDiarios && isset($lancamentosDiarios['data'])) {
                    foreach ($lancamentosDiarios['data'] as $item) {
                        $valor = $item['amount'] ?? 0;
                        $data = $item['date'] ?? '';

                        if ($data) {
                            $dataLancamento = strtotime($data);

                            // Aplicar filtro de data se especificado
                            if (! empty($mes) || ! empty($ano)) {
                                $dataInicioFiltro = null;
                                $dataFimFiltro = null;

                                if (! empty($mes) && ! empty($ano)) {
                                    $dataInicioFiltro = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $ano));
                                    $dataFimFiltro = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
                                } elseif (! empty($mes)) {
                                    $anoTemp = date('Y');
                                    $dataInicioFiltro = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $anoTemp));
                                    $dataFimFiltro = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $anoTemp));
                                } elseif (! empty($ano)) {
                                    $dataInicioFiltro = date('Y-m-d', mktime(0, 0, 0, 1, 1, $ano));
                                    $dataFimFiltro = date('Y-m-t', mktime(0, 0, 0, 12, 1, $ano));
                                }

                                // Pular se não estiver no período filtrado
                                if ($dataInicioFiltro && $dataFimFiltro) {
                                    $dataInicioTs = strtotime($dataInicioFiltro);
                                    $dataFimTs = strtotime($dataFimFiltro.' 23:59:59');

                                    if ($dataLancamento < $dataInicioTs || $dataLancamento > $dataFimTs) {
                                        continue;
                                    }
                                }
                            }

                            // Valores para os próximos 7 dias são considerados "em trânsito"
                            if ($dataLancamento <= strtotime('+7 days')) {
                                $saldosConsolidados['transito'] += $valor;
                            }
                        }
                    }
                }

                // 3. Buscar transações com filtro de mês/ano ou últimos 30 dias como padrão
                $dataInicio = null;
                $dataFim = null;

                if (! empty($mes) || ! empty($ano)) {
                    // Aplicar filtro baseado no que foi especificado
                    if (! empty($mes) && ! empty($ano)) {
                        // Mês e ano específicos
                        $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $ano));
                        $dataFim = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
                    } elseif (! empty($mes)) {
                        // Só mês especificado - usar ano atual
                        $ano = date('Y');
                        $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $ano));
                        $dataFim = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
                    } elseif (! empty($ano)) {
                        // Só ano especificado - usar todo o ano
                        $dataInicio = date('Y-m-d', mktime(0, 0, 0, 1, 1, $ano));
                        $dataFim = date('Y-m-t', mktime(0, 0, 0, 12, 1, $ano));
                    }
                } else {
                    // Caso contrário, usamos os últimos 30 dias
                    $dataInicio = date('Y-m-d', strtotime('-30 days'));
                    $dataFim = date('Y-m-d');
                }

                $filtrosTransacoes = [
                    'perPage' => 1000,
                    'filters' => json_encode([
                        'created_at' => [
                            'min' => $dataInicio,
                            'max' => $dataFim,
                        ],
                        'establishment.id' => $estabelecimentoId,
                    ]),
                ];

                $transacoes = $this->transacaoService->listarTransacoes($filtrosTransacoes);

                if ($transacoes && isset($transacoes['data'])) {
                    $totalTransacoes += count($transacoes['data']);

                    foreach ($transacoes['data'] as $transacao) {
                        // Valores da transação
                        $valorLiquido = $transacao['amount'] ?? 0;
                        $valorOriginal = $transacao['original_amount'] ?? $valorLiquido;
                        $taxas = $transacao['fees'] ?? 0;
                        $status = $transacao['status'] ?? '';
                        $tipo = $transacao['type'] ?? '';
                        $dataTransacao = $transacao['created_at'] ?? '';

                        // Verificar se transação já foi processada
                        $transacaoId = $transacao['_id'] ?? '';
                        if (in_array($transacaoId, $transacoesProcessadasIds)) {
                            continue; // Pula transação duplicada
                        }

                        // Acumular volumes
                        $volumeBruto += $valorOriginal;
                        $volumeLiquido += $valorLiquido;
                        $totalTaxas += $taxas;
                        if (isset($volumePorMetodo[$tipo])) {
                            $volumePorMetodo[$tipo] += $valorLiquido;
                            $taxasPorMetodo[$tipo] += $taxas;
                        }

                        // Contar transações de hoje
                        if (date('Y-m-d', strtotime($dataTransacao)) === date('Y-m-d')) {
                            $transacoesHoje++;
                        }

                        // Contar por status
                        if (isset($transacoesPorStatus[$status])) {
                            $transacoesPorStatus[$status]++;
                        }

                        // Contar por tipo
                        if (isset($transacoesPorTipo[$tipo])) {
                            $transacoesPorTipo[$tipo]++;
                        }

                        // Categorizar por status para saldos
                        switch ($status) {
                            case 'PAID':
                                $transacoesPagas++;
                                break;
                            case 'PENDING':
                            case 'APPROVED':
                            case 'PROCESSING':
                                // Transações em processamento
                                $saldosConsolidados['processamento'] += $valorLiquido;
                                break;
                            case 'FAILED':
                            case 'CANCELED':
                            case 'REFUNDED':
                                $transacoesFalhadas++;
                                break;
                        }

                        // Adicionar ID ao array DEPOIS de processar tudo
                        $transacoesProcessadasIds[] = $transacaoId;
                    }
                }

                // 4. Buscar boletos com filtro de data aplicado na API
                try {
                    $filtrosBoletos = [
                        'perPage' => 1000,
                        'filters' => json_encode([
                            'establishment.id' => $estabelecimentoId,
                        ]),
                    ];

                    // Aplicar filtro de data aos boletos se especificado
                    if (! empty($mes) || ! empty($ano)) {
                        if (! empty($mes) && ! empty($ano)) {
                            $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $ano));
                            $dataFim = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
                        } elseif (! empty($mes)) {
                            $ano = date('Y');
                            $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $ano));
                            $dataFim = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
                        } elseif (! empty($ano)) {
                            $dataInicio = date('Y-m-d', mktime(0, 0, 0, 1, 1, $ano));
                            $dataFim = date('Y-m-t', mktime(0, 0, 0, 12, 1, $ano));
                        }

                        $filtrosBoletos['filters'] = json_encode([
                            'establishment.id' => $estabelecimentoId,
                            'created_at' => [
                                'min' => $dataInicio,
                                'max' => $dataFim,
                            ],
                        ]);
                    }

                    $boletos = $this->boletoService->listarBoletos($filtrosBoletos);

                    if ($boletos && isset($boletos['data']) && is_array($boletos['data'])) {
                        foreach ($boletos['data'] as $boleto) {
                            $valor = $boleto['amount'] ?? 0;
                            $taxa = $boleto['fees'] ?? 0;
                            $status = $boleto['status'] ?? '';
                            $dataBoleto = $boleto['created_at'] ?? ($boleto['updated_at'] ?? '');

                            // Verificar se a data do boleto é válida (filtro já aplicado na API)
                            if (! empty($dataBoleto)) {
                                $timestamp = strtotime($dataBoleto);
                                if ($timestamp === false) {
                                    continue; // Data inválida
                                }
                            }

                            // Consolidação geral
                            $valorOriginalBoleto = $boleto['original_amount'] ?? $valor;
                            $volumeBruto += $valorOriginalBoleto;
                            $volumeLiquido += $valor; // assumir amount como líquido para boletos
                            $totalTaxas += $taxa;
                            $volumePorMetodo['BOLETO'] += $valor;
                            $taxasPorMetodo['BOLETO'] += $taxa;
                            $transacoesPorTipo['BOLETO']++;
                            $totalTransacoes++;

                            // De hoje
                            if (! empty($dataBoleto) && date('Y-m-d', strtotime($dataBoleto)) === date('Y-m-d')) {
                                $transacoesHoje++;
                            }

                            // Status
                            if (isset($transacoesPorStatus[$status])) {
                                $transacoesPorStatus[$status]++;
                            }

                            switch ($status) {
                                case 'PAID':
                                    $transacoesPagas++;
                                    break;
                                case 'PENDING':
                                case 'APPROVED':
                                case 'PROCESSING':
                                    $saldosConsolidados['processamento'] += $valor;
                                    break;
                                case 'FAILED':
                                case 'CANCELED':
                                case 'REFUNDED':
                                    $transacoesFalhadas++;
                                    break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Erro ao buscar boletos do estabelecimento {$estabelecimentoId}: ".$e->getMessage());
                }
            } catch (\Exception $e) {
                Log::warning("Erro ao buscar dados do estabelecimento {$estabelecimentoId}: ".$e->getMessage());

                continue;
            }
        }

        // Formatar saldos para exibição (valores em centavos)
        $saldos = [
            'disponivel' => 'R$ '.number_format($saldosConsolidados['disponivel'] / 100, 2, ',', '.'),
            'transito' => 'R$ '.number_format($saldosConsolidados['transito'] / 100, 2, ',', '.'),
            'processamento' => 'R$ '.number_format($saldosConsolidados['processamento'] / 100, 2, ',', '.'),
            'bloqueado_cartao' => 'R$ '.number_format($saldosConsolidados['bloqueado_cartao'] / 100, 2, ',', '.'),
            'bloqueado_boleto' => 'R$ '.number_format($saldosConsolidados['bloqueado_boleto'] / 100, 2, ',', '.'),
        ];

        // Calcular taxa de sucesso
        $totalTransacoesProcessadas = $transacoesPagas + $transacoesFalhadas;

        // Métricas do dashboard tipadas (sem Taxa de Sucesso global)
        $metricas = [
            // GERAL
            ['valor' => count($estabelecimentosIds), 'label' => 'Total de Estabelecimentos', 'icone' => 'fas fa-building', 'cor' => 'metric-icon-blue', 'tipo' => 'GERAL', 'metodo' => null],
            ['valor' => $transacoesHoje, 'label' => 'Transações Hoje', 'icone' => 'fas fa-exchange-alt', 'cor' => 'metric-icon-green', 'tipo' => 'GERAL', 'metodo' => null],
            ['valor' => 'R$ '.number_format($volumeBruto / 100, 2, ',', '.'), 'label' => 'Volume Bruto (30 dias)', 'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-teal', 'tipo' => 'GERAL', 'metodo' => null],
            ['valor' => 'R$ '.number_format($volumeLiquido / 100, 2, ',', '.'), 'label' => 'Volume Líquido (30 dias)', 'icone' => 'fas fa-chart-bar', 'cor' => 'metric-icon-blue', 'tipo' => 'GERAL', 'metodo' => null],
            ['valor' => 'R$ '.number_format($totalTaxas / 100, 2, ',', '.'), 'label' => 'Taxas (30 dias)', 'icone' => 'fas fa-percentage', 'cor' => 'metric-icon-red', 'tipo' => 'GERAL', 'metodo' => null],
            ['valor' => 'R$ '.number_format($volumePorMetodo['PIX'] / 100, 2, ',', '.'), 'label' => 'Volume PIX (30 dias)', 'icone' => 'fas fa-qrcode', 'cor' => 'metric-icon-green', 'tipo' => 'GERAL', 'metodo' => 'PIX'],

            ['valor' => $transacoesPorTipo['PIX'], 'label' => 'Transações PIX', 'icone' => 'fas fa-qrcode', 'cor' => 'metric-icon-green', 'tipo' => 'GERAL', 'metodo' => 'PIX'],
            ['valor' => $transacoesPorTipo['BOLETO'], 'label' => 'Transações Boleto', 'icone' => 'fas fa-file-invoice', 'cor' => 'metric-icon-blue', 'tipo' => 'GERAL', 'metodo' => 'BOLETO'],
            ['valor' => $transacoesPorTipo['CREDIT'] + $transacoesPorTipo['DEBIT'], 'label' => 'Transações Cartão', 'icone' => 'fas fa-credit-card', 'cor' => 'metric-icon-blue', 'tipo' => 'GERAL', 'metodo' => 'CREDIT'],

            // CARTÃO
            ['valor' => $transacoesPorTipo['CREDIT'] + $transacoesPorTipo['DEBIT'], 'label' => 'Transações Cartão', 'icone' => 'fas fa-credit-card', 'cor' => 'metric-icon-blue', 'tipo' => 'CARTAO', 'metodo' => null],
            ['valor' => $transacoesPorTipo['CREDIT'], 'label' => 'Transações Crédito', 'icone' => 'fas fa-credit-card', 'cor' => 'metric-icon-blue', 'tipo' => 'CARTAO', 'metodo' => 'CREDIT'],
            ['valor' => $transacoesPorTipo['DEBIT'], 'label' => 'Transações Débito', 'icone' => 'fas fa-credit-card', 'cor' => 'metric-icon-teal', 'tipo' => 'CARTAO', 'metodo' => 'DEBIT'],
            ['valor' => 'R$ '.number_format($volumePorMetodo['CREDIT'] / 100, 2, ',', '.'), 'label' => 'Volume Crédito (30 dias)', 'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-teal', 'tipo' => 'CARTAO', 'metodo' => 'CREDIT'],
            ['valor' => 'R$ '.number_format($volumePorMetodo['DEBIT'] / 100, 2, ',', '.'), 'label' => 'Volume Débito (30 dias)', 'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-teal', 'tipo' => 'CARTAO', 'metodo' => 'DEBIT'],
            ['valor' => 'R$ '.number_format(($taxasPorMetodo['CREDIT'] + $taxasPorMetodo['DEBIT']) / 100, 2, ',', '.'), 'label' => 'Taxas Cartão (30 dias)', 'icone' => 'fas fa-percentage', 'cor' => 'metric-icon-red', 'tipo' => 'CARTAO', 'metodo' => null],

            // BOLETO (se houver)
            ['valor' => $transacoesPorTipo['BOLETO'] ?? 0, 'label' => 'Transações Boleto', 'icone' => 'fas fa-file-invoice', 'cor' => 'metric-icon-orange', 'tipo' => 'BOLETO', 'metodo' => 'BOLETO'],
            ['valor' => 'R$ '.number_format(($volumePorMetodo['BOLETO'] ?? 0) / 100, 2, ',', '.'), 'label' => 'Volume Boleto (30 dias)', 'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-orange', 'tipo' => 'BOLETO', 'metodo' => 'BOLETO'],
            ['valor' => 'R$ '.number_format(($taxasPorMetodo['BOLETO'] ?? 0) / 100, 2, ',', '.'), 'label' => 'Taxas Boleto (30 dias)', 'icone' => 'fas fa-percentage', 'cor' => 'metric-icon-red', 'tipo' => 'BOLETO', 'metodo' => 'BOLETO'],
        ];

        return [
            'saldos' => $saldos,
            'metricas' => $metricas,
        ];
    }

    /**
     * Dashboard do Vendedor
     */
    public function vendedorDashboard(Request $request)
    {
        // Obter mês e ano do filtro (podem vir vazios para representar "Todos")
        $mesAtual = $request->input('mes') ?? date('m');
        $anoAtual = $request->input('ano') ?? date('Y');
        $estabelecimentoId = auth()->user()?->vendedor?->estabelecimento_id;

        try {
            // Buscar dados do estabelecimento
            $estabelecimento = $this->estabelecimentoService->buscarEstabelecimento($estabelecimentoId);

            // Buscar transações do estabelecimento com filtro parcial (mês ou ano) ou completos; senão últimos 30 dias
            $dataInicio = null;
            $dataFim = null;
            if (! empty($mesAtual) || ! empty($anoAtual)) {
                if (! empty($mesAtual) && ! empty($anoAtual)) {
                    $dataInicio = date('Y-m-d', mktime(0, 0, 0, (int) $mesAtual, 1, (int) $anoAtual));
                    $dataFim = date('Y-m-t', mktime(0, 0, 0, (int) $mesAtual, 1, (int) $anoAtual));
                } elseif (! empty($mesAtual)) {
                    $anoTemp = (int) date('Y');
                    $dataInicio = date('Y-m-d', mktime(0, 0, 0, (int) $mesAtual, 1, $anoTemp));
                    $dataFim = date('Y-m-t', mktime(0, 0, 0, (int) $mesAtual, 1, $anoTemp));
                } elseif (! empty($anoAtual)) {
                    $dataInicio = date('Y-m-d', mktime(0, 0, 0, 1, 1, (int) $anoAtual));
                    $dataFim = date('Y-m-t', mktime(0, 0, 0, 12, 1, (int) $anoAtual));
                }
            } else {
                // Apenas registros do mês atual
                $dataInicio = date('Y-m-01');
                $dataFim = date('Y-m-t');
            }

            $filtrosTransacoes = [
                'perPage' => 1000,
                'filters' => json_encode([
                    'created_at' => [
                        'min' => $dataInicio,
                        'max' => $dataFim,
                    ],
                    'establishment.id' => $estabelecimentoId,
                ]),
            ];

            $transacoes = $this->transacaoService->listarTransacoes($filtrosTransacoes);

            // Buscar boletos e mesclar com transações para exibição na tabela
            try {
                $filtrosBoletos = [
                    'perPage' => 1000,
                    'filters' => json_encode([
                        'establishment.id' => $estabelecimentoId,
                    ]),
                ];

                // Aplicar filtro de data aos boletos se especificado
                if (! empty($mesAtual) || ! empty($anoAtual)) {
                    if (! empty($mesAtual) && ! empty($anoAtual)) {
                        $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mesAtual, 1, $anoAtual));
                        $dataFim = date('Y-m-t', mktime(0, 0, 0, $mesAtual, 1, $anoAtual));
                    } elseif (! empty($mesAtual)) {
                        $anoAtual = date('Y');
                        $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mesAtual, 1, $anoAtual));
                        $dataFim = date('Y-m-t', mktime(0, 0, 0, $mesAtual, 1, $anoAtual));
                    } elseif (! empty($anoAtual)) {
                        $dataInicio = date('Y-m-d', mktime(0, 0, 0, 1, 1, $anoAtual));
                        $dataFim = date('Y-m-t', mktime(0, 0, 0, 12, 1, $anoAtual));
                    }

                    $filtrosBoletos['filters'] = json_encode([
                        'establishment.id' => $estabelecimentoId,
                        'created_at' => [
                            'min' => $dataInicio,
                            'max' => $dataFim,
                        ],
                    ]);
                }

                $boletos = $this->boletoService->listarBoletos($filtrosBoletos);

                if (isset($boletos['data']) && is_array($boletos['data'])) {
                    // Adaptar estrutura dos boletos para o formato da tabela de transações
                    $boletosAdaptados = array_map(function ($b) {
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
                    if (! isset($transacoes['data']) || ! is_array($transacoes['data'])) {
                        $transacoes['data'] = [];
                        $transacoes['total'] = 0;
                        $transacoes['page'] = 1;
                    }

                    // Mesclar e ordenar por data desc
                    $transacoes['data'] = array_merge($transacoes['data'], $boletosAdaptados);
                    usort($transacoes['data'], function ($a, $b) {
                        $da = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
                        $db = isset($b['created_at']) ? strtotime($b['created_at']) : 0;

                        return $db <=> $da;
                    });

                    // Recalcular total
                    $transacoes['total'] = count($transacoes['data']);
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao buscar boletos para mesclar na tabela: '.$e->getMessage());
            }

            // Buscar saldos do estabelecimento
            $filtrosSaldo = [
                'extra_headers' => [
                    'establishment_id' => $estabelecimentoId,
                ],
            ];

            $saldo = $this->transacaoService->lancamentosFuturos($filtrosSaldo);
            $lancamentosDiarios = $this->transacaoService->lancamentosFuturosDiarios($filtrosSaldo);

            // Calcular métricas (inclui Cartão, PIX e Boleto)
            $totalTransacoes = 0;
            $volumeLiquidoTotal = 0; // amount
            $volumeBrutoTotal = 0;   // original_amount
            $totalTaxas = 0;
            $transacoesPagas = 0;
            $transacoesPendentes = 0;
            $transacoesHoje = 0;

            // Cartão
            $qtdCredito = 0;
            $qtdDebito = 0;
            $qtdCartao = 0;
            $volumeCredito = 0;
            $volumeDebito = 0;
            $taxasCartao = 0;
            $cartaoPagas = 0;
            $cartaoPendentes = 0;
            // PIX
            $qtdPix = 0;
            $volumePix = 0;
            $taxasPix = 0;

            if (isset($transacoes['data'])) {
                foreach ($transacoes['data'] as $transacao) {
                    $tipo = $transacao['type'] ?? '';
                    // Importante: boletos já serão contabilizados no bloco específico de boletos abaixo
                    if ($tipo === 'BOLETO') {
                        continue;
                    }
                    $valor = (int) ($transacao['amount'] ?? 0);
                    $valorOriginal = (int) ($transacao['original_amount'] ?? $valor);
                    $status = $transacao['status'] ?? '';

                    // apenas últimos 30 dias já garantidos no filtro
                    $totalTransacoes++;
                    $volumeLiquidoTotal += $valor;
                    $volumeBrutoTotal += $valorOriginal;
                    $totalTaxas += (int) ($transacao['fees'] ?? 0);

                    if ($status === 'PAID') {
                        $transacoesPagas++;
                    } elseif (in_array($status, ['PENDING', 'APPROVED'])) {
                        $transacoesPendentes++;
                    }

                    if (in_array($tipo, ['CREDIT', 'DEBIT'])) {
                        $qtdCartao++;
                        $taxasCartao += (int) ($transacao['fees'] ?? 0);
                        if ($tipo === 'CREDIT') {
                            $qtdCredito++;
                            $volumeCredito += $valor;
                        }
                        if ($tipo === 'DEBIT') {
                            $qtdDebito++;
                            $volumeDebito += $valor;
                        }
                        if ($status === 'PAID') {
                            $cartaoPagas++;
                        } elseif (in_array($status, ['PENDING', 'APPROVED'])) {
                            $cartaoPendentes++;
                        }
                    }

                    if ($tipo === 'PIX') {
                        $qtdPix++;
                        $volumePix += $valor;
                        $taxasPix += (int) ($transacao['fees'] ?? 0);
                    }

                    // Transações de hoje
                    $dataTrans = $transacao['created_at'] ?? '';
                    if (! empty($dataTrans) && date('Y-m-d', strtotime($dataTrans)) === date('Y-m-d')) {
                        $transacoesHoje++;
                    }
                }
            }

            // Boletos
            $qtdBoletos = 0;
            $volumeBoletos = 0;
            $taxasBoletos = 0;
            $boletosPagos = 0;
            $boletosPendentes = 0;
            $boletosVencidosValor = 0;
            try {
                $filtrosBoletos = [
                    'perPage' => 1000,
                    'filters' => json_encode([
                        'establishment.id' => $estabelecimentoId,
                        'created_at' => [
                            'min' => $dataInicio,
                            'max' => $dataFim,
                        ],
                    ]),
                ];
                $boletos = $this->boletoService->listarBoletos($filtrosBoletos);
                if (isset($boletos['data']) && is_array($boletos['data'])) {
                    foreach ($boletos['data'] as $boleto) {
                        $dataBoleto = $boleto['created_at'] ?? ($boleto['updated_at'] ?? '');
                        if (! empty($dataBoleto)) {
                            $ts = strtotime($dataBoleto);
                            if ($ts === false) {
                                continue;
                            } // Data inválida
                            // Filtro manual de segurança (redundante com o da API, mas mantém consistência)
                            if (! empty($dataInicio) && ! empty($dataFim)) {
                                $dataInicioTs = strtotime($dataInicio);
                                $dataFimTs = strtotime($dataFim.' 23:59:59');
                                if ($ts < $dataInicioTs || $ts > $dataFimTs) {
                                    continue;
                                }
                            }
                        }
                        $valor = (int) ($boleto['amount'] ?? 0);
                        $valorOriginalBoleto = (int) ($boleto['original_amount'] ?? $valor);
                        $taxa = (int) ($boleto['fees'] ?? 0);
                        $status = $boleto['status'] ?? '';
                        $qtdBoletos++;
                        $volumeBoletos += $valor;
                        $taxasBoletos += $taxa;
                        $totalTransacoes++;
                        $volumeLiquidoTotal += $valor;
                        $volumeBrutoTotal += $valorOriginalBoleto;
                        $totalTaxas += $taxa;
                        if ($status === 'PAID') {
                            $boletosPagos++;
                            $transacoesPagas++;
                        } elseif (in_array($status, ['PENDING', 'APPROVED'])) {
                            $boletosPendentes++;
                            $transacoesPendentes++;
                        }
                        // vencido e pendente
                        $venc = $boleto['expiration_at'] ?? null;
                        if ($venc && strtotime($venc) < time() && in_array($status, ['PENDING', 'PROCESSING'])) {
                            $boletosVencidosValor += $valor;
                        }

                        // Boletos criados hoje
                        if (! empty($dataBoleto) && date('Y-m-d', strtotime($dataBoleto)) === date('Y-m-d')) {
                            $transacoesHoje++;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Erro ao listar boletos para vendedor: '.$e->getMessage());
            }

            // Saldos formatados
            $saldoDisponivel = 0;
            if (isset($saldo['total']['amount'])) {
                if (empty($mesAtual) && empty($anoAtual)) {
                    // Sem filtro: mostrar total geral dos lançamentos futuros
                    $saldoDisponivel = $saldo['total']['amount'];
                } elseif (! empty($mesAtual) && ! empty($anoAtual)) {
                    // Filtro completo: mês + ano específicos
                    $mesFiltro = (int) $mesAtual;
                    $anoFiltro = (int) $anoAtual;

                    // Procurar no array de meses
                    if (isset($saldo['months']) && is_array($saldo['months'])) {
                        foreach ($saldo['months'] as $mesLancamento) {
                            if (isset($mesLancamento['month']) && isset($mesLancamento['year']) &&
                                $mesLancamento['month'] == $mesFiltro && $mesLancamento['year'] == $anoFiltro) {
                                $saldoDisponivel = $mesLancamento['amount'];
                                break;
                            }
                        }
                    }
                } elseif (! empty($anoAtual)) {
                    // Apenas ano: somar todos os meses daquele ano
                    $anoFiltro = (int) $anoAtual;

                    if (isset($saldo['months']) && is_array($saldo['months'])) {
                        foreach ($saldo['months'] as $mesLancamento) {
                            if (isset($mesLancamento['year']) && $mesLancamento['year'] == $anoFiltro) {
                                $saldoDisponivel += $mesLancamento['amount'];
                            }
                        }
                    }
                } elseif (! empty($mesAtual)) {
                    // Apenas mês: usar ano atual
                    $mesFiltro = (int) $mesAtual;
                    $anoAtualSistema = (int) date('Y');

                    if (isset($saldo['months']) && is_array($saldo['months'])) {
                        foreach ($saldo['months'] as $mesLancamento) {
                            if (isset($mesLancamento['month']) && isset($mesLancamento['year']) &&
                                $mesLancamento['month'] == $mesFiltro && $mesLancamento['year'] == $anoAtualSistema) {
                                $saldoDisponivel = $mesLancamento['amount'];
                                break;
                            }
                        }
                    }
                }
            }

            $saldos = [
                'disponivel' => 'R$ '.number_format($saldoDisponivel / 100, 2, ',', '.'),
                'transito' => 'R$ 0,00',
                'processamento' => 'R$ 0,00', // Inicializar processamento
                'bloqueado_cartao' => 'R$ 0,00',
                'bloqueado_boleto' => 'R$ 0,00',
            ];

            // Valores em trânsito (próximos 7 dias)
            if (isset($lancamentosDiarios['data'])) {
                $valorTransito = 0;
                foreach ($lancamentosDiarios['data'] as $item) {
                    $valor = $item['amount'] ?? 0;
                    $data = $item['date'] ?? '';

                    if ($data) {
                        $dataLancamento = strtotime($data);

                        // Aplicar filtro de data se especificado
                        if (! empty($mesAtual) || ! empty($anoAtual)) {
                            $dataInicioFiltro = null;
                            $dataFimFiltro = null;

                            if (! empty($mesAtual) && ! empty($anoAtual)) {
                                $dataInicioFiltro = date('Y-m-d', mktime(0, 0, 0, $mesAtual, 1, $anoAtual));
                                $dataFimFiltro = date('Y-m-t', mktime(0, 0, 0, $mesAtual, 1, $anoAtual));
                            } elseif (! empty($mesAtual)) {
                                $anoTemp = date('Y');
                                $dataInicioFiltro = date('Y-m-d', mktime(0, 0, 0, $mesAtual, 1, $anoTemp));
                                $dataFimFiltro = date('Y-m-t', mktime(0, 0, 0, $mesAtual, 1, $anoTemp));
                            } elseif (! empty($anoAtual)) {
                                $dataInicioFiltro = date('Y-m-d', mktime(0, 0, 0, 1, 1, $anoAtual));
                                $dataFimFiltro = date('Y-m-t', mktime(0, 0, 0, 12, 1, $anoAtual));
                            }

                            // Pular se não estiver no período filtrado
                            if ($dataInicioFiltro && $dataFimFiltro) {
                                $dataInicioTs = strtotime($dataInicioFiltro);
                                $dataFimTs = strtotime($dataFimFiltro.' 23:59:59');

                                if ($dataLancamento < $dataInicioTs || $dataLancamento > $dataFimTs) {
                                    continue;
                                }
                            }
                        }

                        // Valores para os próximos 7 dias são considerados "em trânsito"
                        if ($dataLancamento <= strtotime('+7 days')) {
                            $valorTransito += $valor;
                        }
                    }
                }
                $saldos['transito'] = 'R$ '.number_format($valorTransito / 100, 2, ',', '.');
            }

            // Calcular valor em processamento (transações pendentes)
            $valorProcessamento = 0;
            if (isset($transacoes['data'])) {
                foreach ($transacoes['data'] as $transacao) {
                    $status = $transacao['status'] ?? '';
                    if (in_array($status, ['PENDING', 'APPROVED', 'PROCESSING'])) {
                        $valorProcessamento += (int) ($transacao['amount'] ?? 0);
                    }
                }
            }
            $saldos['processamento'] = 'R$ '.number_format($valorProcessamento / 100, 2, ',', '.');

            $metricasGeral = [
                ['valor' => $transacoesHoje, 'label' => 'Transações Hoje', 'icone' => 'fas fa-exchange-alt', 'cor' => 'metric-icon-green'],
                ['valor' => 'R$ '.number_format($volumeBrutoTotal / 100, 2, ',', '.'),  'label' => 'Volume Bruto (30 dias)',   'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-teal'],
                ['valor' => 'R$ '.number_format($volumeLiquidoTotal / 100, 2, ',', '.'), 'label' => 'Volume Líquido (30 dias)', 'icone' => 'fas fa-chart-bar',  'cor' => 'metric-icon-blue'],
                ['valor' => 'R$ '.number_format($totalTaxas / 100, 2, ',', '.'),       'label' => 'Taxas (30 dias)',          'icone' => 'fas fa-percentage', 'cor' => 'metric-icon-red'],
                ['valor' => 'R$ '.number_format($volumePix / 100, 2, ',', '.'),        'label' => 'Volume PIX (30 dias)',     'icone' => 'fas fa-qrcode',     'cor' => 'metric-icon-green'],
                ['valor' => $qtdPix,     'label' => 'Transações PIX',     'icone' => 'fas fa-qrcode',       'cor' => 'metric-icon-green'],
                ['valor' => $qtdBoletos, 'label' => 'Transações Boleto',  'icone' => 'fas fa-file-invoice', 'cor' => 'metric-icon-blue'],
                ['valor' => $qtdCartao,  'label' => 'Transações Cartão',  'icone' => 'fas fa-credit-card',  'cor' => 'metric-icon-blue'],
                ['valor' => ($totalTransacoes > 0 ? 'R$ '.number_format(($volumeLiquidoTotal / $totalTransacoes) / 100, 2, ',', '.') : 'R$ 0,00'), 'label' => 'Ticket Médio', 'icone' => 'fas fa-ticket-alt', 'cor' => 'metric-icon-cyan'],
            ];

            $metricasCartao = [
                ['valor' => $qtdCartao, 'label' => 'Transações Cartão', 'icone' => 'fas fa-credit-card', 'cor' => 'metric-icon-blue'],
                ['valor' => $qtdCredito, 'label' => 'Transações Crédito', 'icone' => 'fas fa-credit-card', 'cor' => 'metric-icon-blue'],
                ['valor' => $qtdDebito,  'label' => 'Transações Débito',  'icone' => 'fas fa-credit-card', 'cor' => 'metric-icon-teal'],
                ['valor' => 'R$ '.number_format($volumeCredito / 100, 2, ',', '.'), 'label' => 'Volume Crédito (30 dias)', 'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-teal'],
                ['valor' => 'R$ '.number_format($volumeDebito / 100, 2, ',', '.'),  'label' => 'Volume Débito (30 dias)',  'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-teal'],
                ['valor' => 'R$ '.number_format($taxasCartao / 100, 2, ',', '.'),   'label' => 'Taxas Cartão (30 dias)',   'icone' => 'fas fa-percentage', 'cor' => 'metric-icon-red'],
                ['valor' => $cartaoPagas, 'label' => 'Cartão Pagas', 'icone' => 'fas fa-check-circle', 'cor' => 'metric-icon-green'],
                ['valor' => $cartaoPendentes, 'label' => 'Cartão Pendentes', 'icone' => 'fas fa-clock', 'cor' => 'metric-icon-orange'],
                ['valor' => ($qtdCartao > 0 ? 'R$ '.number_format((($volumeCredito + $volumeDebito) / $qtdCartao) / 100, 2, ',', '.') : 'R$ 0,00'), 'label' => 'Ticket Médio Cartão', 'icone' => 'fas fa-ticket-alt', 'cor' => 'metric-icon-cyan'],
            ];

            $metricasBoleto = [
                ['valor' => $qtdBoletos, 'label' => 'Transações Boleto', 'icone' => 'fas fa-file-invoice', 'cor' => 'metric-icon-orange'],
                ['valor' => 'R$ '.number_format($volumeBoletos / 100, 2, ',', '.'), 'label' => 'Volume Boleto (30 dias)', 'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-orange'],
                ['valor' => 'R$ '.number_format($taxasBoletos / 100, 2, ',', '.'),  'label' => 'Taxas Boleto (30 dias)',  'icone' => 'fas fa-percentage', 'cor' => 'metric-icon-red'],
                ['valor' => 'R$ '.number_format($boletosVencidosValor / 100, 2, ',', '.'), 'label' => 'Boletos Vencidos (valor)', 'icone' => 'fas fa-exclamation-triangle', 'cor' => 'metric-icon-red'],
                ['valor' => $boletosPagos, 'label' => 'Boletos Pagos', 'icone' => 'fas fa-check-circle', 'cor' => 'metric-icon-green'],
                ['valor' => $boletosPendentes, 'label' => 'Boletos Pendentes', 'icone' => 'fas fa-clock', 'cor' => 'metric-icon-orange'],
            ];

            // Para compatibilidade, manter $metricas como geral
            $metricas = $metricasGeral;

            return view('dashboard.vendedor', compact('saldos', 'metricas', 'estabelecimento', 'transacoes', 'metricasGeral', 'metricasCartao', 'metricasBoleto', 'mesAtual', 'anoAtual'));
        } catch (\Exception $e) {
            Log::error('Erro ao carregar dashboard vendedor: '.$e->getMessage());

            $saldos = [
                'disponivel' => 'R$ 0,00',
                'transito' => 'R$ 0,00',
                'bloqueado_cartao' => 'R$ 0,00',
                'bloqueado_boleto' => 'R$ 0,00',
            ];

            $metricas = [
                [
                    'valor' => '0',
                    'label' => 'Erro ao carregar dados',
                    'icone' => 'fas fa-exclamation-triangle',
                    'cor' => 'metric-icon-red',
                ],
            ];

            return view('dashboard.vendedor', compact('saldos', 'metricas'));
        }
    }
}
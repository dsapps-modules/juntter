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

        try {
            $mes = $request->input('mes') ?? date('m');
            $ano = $request->input('ano') ?? date('Y');
            $dia = new DateTime("$ano-$mes-01");

            $dataInicio = "$ano-$mes-01";
            $dataFim = $dia->format('Y-m-t');

            // Queries Base
            $queryBase = \App\Models\PaytimeTransaction::whereBetween('created_at', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);

            // 1. Agregações Gerais
            $queryTransacoesBase = clone $queryBase;
            $queryTransacoesBase->where('type', '!=', 'BILLET');

            // --- TOTAIS GERAIS (Summary Cards) ---
            $agregadoTrans = (clone $queryTransacoesBase)->selectRaw('
                COUNT(*) as count,
                SUM(amount) as amount,
                SUM(original_amount) as original_amount,
                SUM(fees) as fees,
                AVG(amount) as avg_ticket,
                AVG(installments) as avg_installments
            ')->first();

            $t_totalTransactions = $agregadoTrans->count ?? 0;
            $t_totalAmountCents = $agregadoTrans->amount ?? 0;
            $t_totalOriginalAmountCents = $agregadoTrans->original_amount ?? 0;
            $t_totalFeesCents = $agregadoTrans->fees ?? 0;
            $t_averageTicketCents = $agregadoTrans->avg_ticket ?? 0;
            $t_averageInstallments = $agregadoTrans->avg_installments ?? 0;

            // --- POR TIPO (Distribution Cards) ---
            $transByType = (clone $queryTransacoesBase)
                ->selectRaw('type, COUNT(*) as count, SUM(amount) as amount')
                ->groupBy('type')
                ->get()
                ->keyBy('type');

            $expectedTypes = ['CREDIT', 'DEBIT', 'PIX'];
            $transactionsByType = [];
            $amountByTypeCents = [];

            foreach ($expectedTypes as $type) {
                $item = $transByType->get($type);
                $transactionsByType[$type] = $item->count ?? 0;
                $amountByTypeCents[$type] = $item->amount ?? 0;
            }

            // --- POR STATUS ---
            $transByStatusRaw = (clone $queryTransacoesBase)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            $requiredStatuses = ['PAID', 'FAILED', 'REFUNDED', 'PENDING', 'APPROVED', 'PROCESSING', 'CANCELED', 'DISPUTED', 'CHARGEBACK'];
            $transactionsByStatus = [];
            foreach ($requiredStatuses as $s) {
                $transactionsByStatus[$s] = $transByStatusRaw[$s] ?? 0;
            }

            // --- BOLETOS (Type == BILLET) ---
            $queryBoletosBase = clone $queryBase;
            $queryBoletosBase->where('type', 'BILLET');

            $agregadoBoletos = (clone $queryBoletosBase)->selectRaw('
                COUNT(*) as count,
                SUM(amount) as amount,
                SUM(original_amount) as original_amount,
                SUM(fees) as fees
            ')->first();

            $b_totalBillets = $agregadoBoletos->count ?? 0;
            $b_totalAmountCents = $agregadoBoletos->amount ?? 0;
            $b_totalOriginalAmountCents = $agregadoBoletos->original_amount ?? 0;
            $b_totalFeesCents = $agregadoBoletos->fees ?? 0;

            // Total pago em boletos (Status = PAID)
            $b_totalPaidCents = (clone $queryBoletosBase)
                ->where('status', 'PAID')
                ->sum('amount');

            // Por Status Boletos
            $boletosByStatusRaw = (clone $queryBoletosBase)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            $billetsByStatus = [];
            foreach ($requiredStatuses as $s) {
                $billetsByStatus[$s] = $boletosByStatusRaw[$s] ?? 0;
            }

            // --- TOTAIS COMBINADOS ---
            $c_totalAmountCents = $t_totalAmountCents + $b_totalAmountCents;
            $c_totalOriginalAmountCents = $t_totalOriginalAmountCents + $b_totalOriginalAmountCents;
            $c_totalFeesCents = $t_totalFeesCents + $b_totalFeesCents;

            // Helpers de formatação
            $fmtMoney = fn($val) => 'R$ ' . number_format(($val ?? 0) / 100, 2, ',', '.');
            $fmtPercent = fn($val) => number_format((float) $val, 2, ',', '.') . '%';

            // --- CONSTRUÇÃO DO ARRAY $metrics ---

            // Calculos percentuais por tipo
            $amountByTypePercentFormatted = [];
            $amountByTypeFormatted = [];
            foreach ($expectedTypes as $type) {
                $amt = $amountByTypeCents[$type] ?? 0;
                $amountByTypeFormatted[$type] = $fmtMoney($amt);
                $pct = $t_totalAmountCents > 0 ? ($amt / $t_totalAmountCents) * 100 : 0;
                $amountByTypePercentFormatted[$type] = $fmtPercent($pct);
            }

            $transactionsByStatusPercent = [];
            foreach ($transactionsByStatus as $st => $cnt) {
                $pct = $t_totalTransactions > 0 ? ($cnt / $t_totalTransactions) * 100 : 0;
                $transactionsByStatusPercent[$st] = $fmtPercent($pct);
            }

            $billetsByStatusPercent = [];
            foreach ($billetsByStatus as $st => $cnt) {
                $pct = $b_totalBillets > 0 ? ($cnt / $b_totalBillets) * 100 : 0;
                $billetsByStatusPercent[$st] = $fmtPercent($pct);
            }

            // Saldo - Em um dashboard admin real, isso viria de uma API de Gateway ou tabela de saldos.
            // Para manter a compatibilidade com a view que espera 'balance':
            $balanceFormatted = 'Consultar Extrato';

            $metrics = [
                // Transações (Cartão + Pix)
                'total_amount_formatted' => $fmtMoney($t_totalAmountCents),
                'total_fees_formatted' => $fmtMoney($t_totalFeesCents),
                'total_original_amount_formatted' => $fmtMoney($t_totalOriginalAmountCents),

                'total_transactions' => $t_totalTransactions,
                'average_ticket_formatted' => $fmtMoney($t_averageTicketCents),
                'average_installments' => round($t_averageInstallments, 1),
                'balance' => $balanceFormatted,

                'amount_by_type_formatted' => $amountByTypeFormatted,
                'amount_by_type_percent_formatted' => $amountByTypePercentFormatted,
                'transactions_by_type' => $transactionsByType,
                'transactions_by_status' => $transactionsByStatus,
                'transactions_by_status_percent' => $transactionsByStatusPercent,

                // Boletos
                'billets_total' => $b_totalBillets,
                'billets_total_amount_formatted' => $fmtMoney($b_totalAmountCents),
                'billets_total_original_amount_formatted' => $fmtMoney($b_totalOriginalAmountCents),
                'billets_total_fees_formatted' => $fmtMoney($b_totalFeesCents),
                'billets_total_paid_formatted' => $fmtMoney($b_totalPaidCents),
                'billets_by_status' => $billetsByStatus,
                'billets_by_status_percent' => $billetsByStatusPercent,

                // Combinado (Tudo incluindo Boletos)
                'combined_total_amount_formatted' => $fmtMoney($c_totalAmountCents),
                'combined_total_original_amount_formatted' => $fmtMoney($c_totalOriginalAmountCents),
                'combined_total_fees_formatted' => $fmtMoney($c_totalFeesCents),
            ];

            return view('dashboard.admin', compact('metrics', 'mes', 'ano'));

        } catch (\Exception $e) {
            Log::error('Erro no adminDashboard: ' . $e->getMessage());
            // Retornar view com métricas zeradas (fallback básico)
            $metrics = [
                'total_amount_formatted' => 'R$ 0,00',
                'combined_total_amount_formatted' => 'R$ 0,00',
                'transactions_by_type' => [],
                'transactions_by_status' => [],
                // ... minimize fallback to avoid undef index errors in view
            ];
            return view('dashboard.admin', compact('metrics', 'mes', 'ano'));
        }
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
            Log::warning('Erro ao buscar estabelecimentos: ' . $e->getMessage());
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
        $volumePorMetodo = ['CREDIT' => 0, 'DEBIT' => 0, 'PIX' => 0, 'BILLET' => 0];
        $taxasPorMetodo = ['CREDIT' => 0, 'DEBIT' => 0, 'PIX' => 0, 'BILLET' => 0];
        $transacoesHoje = 0;
        $transacoesPagas = 0;
        $transacoesFalhadas = 0;
        $transacoesProcessadasIds = []; // Para evitar duplicatas
        $transacoesPorTipo = [
            'CREDIT' => 0,
            'DEBIT' => 0,
            'PIX' => 0,
            'BILLET' => 0,
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
                    } elseif (!empty($mes) && !empty($ano)) {
                        // Filtro completo: mês + ano específicos
                        $mesFiltro = (int) $mes;
                        $anoFiltro = (int) $ano;

                        // Procurar no array de meses
                        if (isset($saldo['months']) && is_array($saldo['months'])) {
                            foreach ($saldo['months'] as $mesLancamento) {
                                if (
                                    isset($mesLancamento['month']) && isset($mesLancamento['year']) &&
                                    $mesLancamento['month'] == $mesFiltro && $mesLancamento['year'] == $anoFiltro
                                ) {
                                    $saldoDisponivel = $mesLancamento['amount'];
                                    break;
                                }
                            }
                        }
                    } elseif (!empty($ano)) {
                        // Apenas ano: somar todos os meses daquele ano
                        $anoFiltro = (int) $ano;

                        if (isset($saldo['months']) && is_array($saldo['months'])) {
                            foreach ($saldo['months'] as $mesLancamento) {
                                if (isset($mesLancamento['year']) && $mesLancamento['year'] == $anoFiltro) {
                                    $saldoDisponivel += $mesLancamento['amount'];
                                }
                            }
                        }
                    } elseif (!empty($mes)) {
                        // Apenas mês: usar ano atual
                        $mesFiltro = (int) $mes;
                        $anoAtual = (int) date('Y');

                        if (isset($saldo['months']) && is_array($saldo['months'])) {
                            foreach ($saldo['months'] as $mesLancamento) {
                                if (
                                    isset($mesLancamento['month']) && isset($mesLancamento['year']) &&
                                    $mesLancamento['month'] == $mesFiltro && $mesLancamento['year'] == $anoAtual
                                ) {
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
                            if (!empty($mes) || !empty($ano)) {
                                $dataInicioFiltro = null;
                                $dataFimFiltro = null;

                                if (!empty($mes) && !empty($ano)) {
                                    $dataInicioFiltro = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $ano));
                                    $dataFimFiltro = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
                                } elseif (!empty($mes)) {
                                    $anoTemp = date('Y');
                                    $dataInicioFiltro = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $anoTemp));
                                    $dataFimFiltro = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $anoTemp));
                                } elseif (!empty($ano)) {
                                    $dataInicioFiltro = date('Y-m-d', mktime(0, 0, 0, 1, 1, $ano));
                                    $dataFimFiltro = date('Y-m-t', mktime(0, 0, 0, 12, 1, $ano));
                                }

                                // Pular se não estiver no período filtrado
                                if ($dataInicioFiltro && $dataFimFiltro) {
                                    $dataInicioTs = strtotime($dataInicioFiltro);
                                    $dataFimTs = strtotime($dataFimFiltro . ' 23:59:59');

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

                if (!empty($mes) || !empty($ano)) {
                    // Aplicar filtro baseado no que foi especificado
                    if (!empty($mes) && !empty($ano)) {
                        // Mês e ano específicos
                        $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $ano));
                        $dataFim = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
                    } elseif (!empty($mes)) {
                        // Só mês especificado - usar ano atual
                        $ano = date('Y');
                        $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $ano));
                        $dataFim = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
                    } elseif (!empty($ano)) {
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
                    if (!empty($mes) || !empty($ano)) {
                        if (!empty($mes) && !empty($ano)) {
                            $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $ano));
                            $dataFim = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
                        } elseif (!empty($mes)) {
                            $ano = date('Y');
                            $dataInicio = date('Y-m-d', mktime(0, 0, 0, $mes, 1, $ano));
                            $dataFim = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
                        } elseif (!empty($ano)) {
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
                            if (!empty($dataBoleto)) {
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
                            $volumePorMetodo['BILLET'] += $valor;
                            $taxasPorMetodo['BILLET'] += $taxa;
                            $transacoesPorTipo['BILLET']++;
                            $totalTransacoes++;

                            // De hoje
                            if (!empty($dataBoleto) && date('Y-m-d', strtotime($dataBoleto)) === date('Y-m-d')) {
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
                    Log::warning("Erro ao buscar boletos do estabelecimento {$estabelecimentoId}: " . $e->getMessage());
                }
            } catch (\Exception $e) {
                Log::warning("Erro ao buscar dados do estabelecimento {$estabelecimentoId}: " . $e->getMessage());

                continue;
            }
        }

        // Formatar saldos para exibição (valores em centavos)
        $saldos = [
            'disponivel' => 'R$ ' . number_format($saldosConsolidados['disponivel'] / 100, 2, ',', '.'),
            'transito' => 'R$ ' . number_format($saldosConsolidados['transito'] / 100, 2, ',', '.'),
            'processamento' => 'R$ ' . number_format($saldosConsolidados['processamento'] / 100, 2, ',', '.'),
            'bloqueado_cartao' => 'R$ ' . number_format($saldosConsolidados['bloqueado_cartao'] / 100, 2, ',', '.'),
            'bloqueado_boleto' => 'R$ ' . number_format($saldosConsolidados['bloqueado_boleto'] / 100, 2, ',', '.'),
        ];

        // Calcular taxa de sucesso
        $totalTransacoesProcessadas = $transacoesPagas + $transacoesFalhadas;

        // Métricas do dashboard tipadas (sem Taxa de Sucesso global)
        $metricas = [
            // GERAL
            ['valor' => count($estabelecimentosIds), 'label' => 'Total de Estabelecimentos', 'icone' => 'fas fa-building', 'cor' => 'metric-icon-blue', 'tipo' => 'GERAL', 'metodo' => null],
            ['valor' => $transacoesHoje, 'label' => 'Transações Hoje', 'icone' => 'fas fa-exchange-alt', 'cor' => 'metric-icon-green', 'tipo' => 'GERAL', 'metodo' => null],
            ['valor' => 'R$ ' . number_format($volumeBruto / 100, 2, ',', '.'), 'label' => 'Volume Bruto (30 dias)', 'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-teal', 'tipo' => 'GERAL', 'metodo' => null],
            ['valor' => 'R$ ' . number_format($volumeLiquido / 100, 2, ',', '.'), 'label' => 'Volume Líquido (30 dias)', 'icone' => 'fas fa-chart-bar', 'cor' => 'metric-icon-blue', 'tipo' => 'GERAL', 'metodo' => null],
            ['valor' => 'R$ ' . number_format($totalTaxas / 100, 2, ',', '.'), 'label' => 'Taxas (30 dias)', 'icone' => 'fas fa-percentage', 'cor' => 'metric-icon-red', 'tipo' => 'GERAL', 'metodo' => null],
            ['valor' => 'R$ ' . number_format($volumePorMetodo['PIX'] / 100, 2, ',', '.'), 'label' => 'Volume PIX (30 dias)', 'icone' => 'fas fa-qrcode', 'cor' => 'metric-icon-green', 'tipo' => 'GERAL', 'metodo' => 'PIX'],

            ['valor' => $transacoesPorTipo['PIX'], 'label' => 'Transações PIX', 'icone' => 'fas fa-qrcode', 'cor' => 'metric-icon-green', 'tipo' => 'GERAL', 'metodo' => 'PIX'],
            ['valor' => $transacoesPorTipo['BILLET'], 'label' => 'Transações Boleto', 'icone' => 'fas fa-file-invoice', 'cor' => 'metric-icon-blue', 'tipo' => 'GERAL', 'metodo' => 'BILLET'],
            ['valor' => $transacoesPorTipo['CREDIT'] + $transacoesPorTipo['DEBIT'], 'label' => 'Transações Cartão', 'icone' => 'fas fa-credit-card', 'cor' => 'metric-icon-blue', 'tipo' => 'GERAL', 'metodo' => 'CREDIT'],

            // CARTÃO
            ['valor' => $transacoesPorTipo['CREDIT'] + $transacoesPorTipo['DEBIT'], 'label' => 'Transações Cartão', 'icone' => 'fas fa-credit-card', 'cor' => 'metric-icon-blue', 'tipo' => 'CARTAO', 'metodo' => null],
            ['valor' => $transacoesPorTipo['CREDIT'], 'label' => 'Transações Crédito', 'icone' => 'fas fa-credit-card', 'cor' => 'metric-icon-blue', 'tipo' => 'CARTAO', 'metodo' => 'CREDIT'],
            ['valor' => $transacoesPorTipo['DEBIT'], 'label' => 'Transações Débito', 'icone' => 'fas fa-credit-card', 'cor' => 'metric-icon-teal', 'tipo' => 'CARTAO', 'metodo' => 'DEBIT'],
            ['valor' => 'R$ ' . number_format($volumePorMetodo['CREDIT'] / 100, 2, ',', '.'), 'label' => 'Volume Crédito (30 dias)', 'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-teal', 'tipo' => 'CARTAO', 'metodo' => 'CREDIT'],
            ['valor' => 'R$ ' . number_format($volumePorMetodo['DEBIT'] / 100, 2, ',', '.'), 'label' => 'Volume Débito (30 dias)', 'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-teal', 'tipo' => 'CARTAO', 'metodo' => 'DEBIT'],
            ['valor' => 'R$ ' . number_format(($taxasPorMetodo['CREDIT'] + $taxasPorMetodo['DEBIT']) / 100, 2, ',', '.'), 'label' => 'Taxas Cartão (30 dias)', 'icone' => 'fas fa-percentage', 'cor' => 'metric-icon-red', 'tipo' => 'CARTAO', 'metodo' => null],

            // BOLETO (se houver)
            ['valor' => $transacoesPorTipo['BILLET'] ?? 0, 'label' => 'Transações Boleto', 'icone' => 'fas fa-file-invoice', 'cor' => 'metric-icon-orange', 'tipo' => 'BOLETO', 'metodo' => 'BILLET'],
            ['valor' => 'R$ ' . number_format(($volumePorMetodo['BILLET'] ?? 0) / 100, 2, ',', '.'), 'label' => 'Volume Boleto (30 dias)', 'icone' => 'fas fa-chart-line', 'cor' => 'metric-icon-orange', 'tipo' => 'BOLETO', 'metodo' => 'BILLET'],
            ['valor' => 'R$ ' . number_format(($taxasPorMetodo['BILLET'] ?? 0) / 100, 2, ',', '.'), 'label' => 'Taxas Boleto (30 dias)', 'icone' => 'fas fa-percentage', 'cor' => 'metric-icon-red', 'tipo' => 'BOLETO', 'metodo' => 'BILLET'],
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
        // Obter mês e ano do filtro
        $mes = $request->input('mes') ?? date('m');
        $ano = $request->input('ano') ?? date('Y');
        $estabelecimentoId = auth()->user()?->vendedor?->estabelecimento_id;

        try {
            // Buscar dados do estabelecimento
            $estabelecimento = $this->estabelecimentoService->buscarEstabelecimento($estabelecimentoId);

            $dataInicio = date('Y-m-d', mktime(0, 0, 0, (int) $mes, 1, (int) $ano));
            $dataFim = date('Y-m-t', mktime(0, 0, 0, (int) $mes, 1, (int) $ano));

            // Base Query com filtros de data e estabelecimento
            $queryBase = \App\Models\PaytimeTransaction::where('establishment_id', $estabelecimentoId)
                ->whereBetween('created_at', [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);

            // 1. Agregações Gerais
            $queryTransacoesBase = clone $queryBase;
            $queryTransacoesBase->where('type', '!=', 'BILLET');

            // --- TOTAIS GERAIS (Summary Cards) ---
            $agregadoTrans = (clone $queryTransacoesBase)->selectRaw('
                COUNT(*) as count,
                SUM(amount) as amount,
                SUM(original_amount) as original_amount,
                SUM(fees) as fees,
                AVG(amount) as avg_ticket,
                AVG(installments) as avg_installments
            ')->first();

            $t_totalTransactions = $agregadoTrans->count ?? 0;
            $t_totalAmountCents = $agregadoTrans->amount ?? 0;
            $t_totalOriginalAmountCents = $agregadoTrans->original_amount ?? 0;
            $t_totalFeesCents = $agregadoTrans->fees ?? 0;
            $t_averageTicketCents = $agregadoTrans->avg_ticket ?? 0;

            // --- POR TIPO (Distribution Cards) ---
            $transByType = (clone $queryTransacoesBase)
                ->selectRaw('type, COUNT(*) as count, SUM(amount) as amount')
                ->groupBy('type')
                ->get()
                ->keyBy('type');

            $expectedTypes = ['CREDIT', 'DEBIT', 'PIX'];
            $transactionsByType = [];
            $amountByTypeCents = [];

            foreach ($expectedTypes as $type) {
                $item = $transByType->get($type);
                $transactionsByType[$type] = $item->count ?? 0;
                $amountByTypeCents[$type] = $item->amount ?? 0;
            }

            // --- POR STATUS ---
            $transByStatusRaw = (clone $queryTransacoesBase)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            $requiredStatuses = ['PAID', 'FAILED', 'REFUNDED', 'PENDING', 'APPROVED', 'PROCESSING', 'CANCELED', 'DISPUTED', 'CHARGEBACK'];
            $transactionsByStatus = [];
            foreach ($requiredStatuses as $s) {
                $transactionsByStatus[$s] = $transByStatusRaw[$s] ?? 0;
            }

            // --- BOLETOS (Type == BILLET) ---
            $queryBoletosBase = clone $queryBase;
            $queryBoletosBase->where('type', 'BILLET');

            $agregadoBoletos = (clone $queryBoletosBase)->selectRaw('
                COUNT(*) as count,
                SUM(amount) as amount,
                SUM(original_amount) as original_amount,
                SUM(fees) as fees
            ')->first();

            $b_totalBillets = $agregadoBoletos->count ?? 0;
            $b_totalAmountCents = $agregadoBoletos->amount ?? 0;
            $b_totalOriginalAmountCents = $agregadoBoletos->original_amount ?? 0;
            $b_totalFeesCents = $agregadoBoletos->fees ?? 0;

            // --- TOTAIS COMBINADOS ---
            $c_totalAmountCents = $t_totalAmountCents + $b_totalAmountCents;
            $c_totalOriginalAmountCents = $t_totalOriginalAmountCents + $b_totalOriginalAmountCents;
            $c_totalFeesCents = $t_totalFeesCents + $b_totalFeesCents;

            // Helpers de formatação
            $fmtMoney = fn($val) => 'R$ ' . number_format(($val ?? 0) / 100, 2, ',', '.');
            $fmtPercent = fn($val) => number_format((float) $val, 2, ',', '.') . '%';

            // --- CONSTRUÇÃO DO ARRAY $metrics ---
            $amountByTypePercentFormatted = [];
            $amountByTypeFormatted = [];
            foreach ($expectedTypes as $type) {
                $amt = $amountByTypeCents[$type] ?? 0;
                $amountByTypeFormatted[$type] = $fmtMoney($amt);
                $amountByTypePercentFormatted[$type] = $t_totalAmountCents > 0
                    ? $fmtPercent(($amt / $t_totalAmountCents) * 100)
                    : '0,00%';
            }

            $transactionsByStatusPercent = [];
            foreach ($requiredStatuses as $s) {
                $count = $transactionsByStatus[$s] ?? 0;
                $transactionsByStatusPercent[$s] = $t_totalTransactions > 0
                    ? $fmtPercent(($count / $t_totalTransactions) * 100)
                    : '0,00%';
            }

            $metrics = [
                'total_transactions' => number_format($t_totalTransactions, 0, ',', '.'),
                'total_amount_formatted' => $fmtMoney($t_totalAmountCents),
                'total_original_amount_formatted' => $fmtMoney($t_totalOriginalAmountCents),
                'total_fees_formatted' => $fmtMoney($t_totalFeesCents),
                'average_ticket_formatted' => $fmtMoney($t_averageTicketCents),
                'transactions_by_type' => $transactionsByType,
                'amount_by_type_formatted' => $amountByTypeFormatted,
                'amount_by_type_percent_formatted' => $amountByTypePercentFormatted,
                'transactions_by_status' => $transactionsByStatus,
                'transactions_by_status_percent' => $transactionsByStatusPercent,
                'billets_total_amount_formatted' => $fmtMoney($b_totalAmountCents),
                'billets_total_original_amount_formatted' => $fmtMoney($b_totalOriginalAmountCents),
                'billets_total_fees_formatted' => $fmtMoney($b_totalFeesCents),
                'balance' => 'Consultar Extrato',
            ];

            // 2. Saldos (API)
            $filtrosSaldo = [
                'extra_headers' => [
                    'establishment_id' => $estabelecimentoId,
                ],
            ];

            try {
                $saldoApi = $this->transacaoService->lancamentosFuturos($filtrosSaldo);
                $lancamentosDiarios = $this->transacaoService->lancamentosFuturosDiarios($filtrosSaldo);
            } catch (\Exception $e) {
                $saldoApi = [];
                $lancamentosDiarios = [];
            }

            $saldoDisponivel = $saldoApi['total']['amount'] ?? 0;
            $valorTransito = 0;
            if (isset($lancamentosDiarios['data'])) {
                foreach ($lancamentosDiarios['data'] as $item) {
                    $dataLancamento = strtotime($item['date'] ?? '');
                    if ($dataLancamento && $dataLancamento <= strtotime('+7 days') && $dataLancamento >= time()) {
                        $valorTransito += ($item['amount'] ?? 0);
                    }
                }
            }
            $valorProcessamento = (clone $queryBase)->whereIn('status', ['PENDING', 'APPROVED', 'PROCESSING'])->sum('amount');

            $saldos = [
                'disponivel' => $fmtMoney($saldoDisponivel),
                'transito' => $fmtMoney($valorTransito),
                'processamento' => $fmtMoney($valorProcessamento),
                'bloqueado_cartao' => 'R$ 0,00',
                'bloqueado_boleto' => 'R$ 0,00',
            ];

            // 3. Buscar Lista de Transações (Tabela)
            $transacoesDb = (clone $queryBase)
                ->orderBy('created_at', 'DESC')
                ->limit(100)
                ->get();

            $transacoes = [
                'data' => $transacoesDb->toArray(),
                'total' => (clone $queryBase)->count()
            ];

            return view('dashboard.vendedor', [
                'metrics' => $metrics,
                'saldos' => $saldos,
                'estabelecimento' => $estabelecimento,
                'transacoes' => $transacoes,
                'mes' => $mes,
                'ano' => $ano,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao carregar dashboard vendedor: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao carregar os dados do dashboard.');
        }
    }
}
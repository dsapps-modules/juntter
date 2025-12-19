@extends('templates.dashboard-template')
@section('title', 'Dashboard')

@php
    $breadcrumbItems = [['label' => 'Vendas', 'icon' => 'fas fa-chart-line', 'url' => '#']];
@endphp

@section('content')

    <x-breadcrumb :items="$breadcrumbItems" :filtroData="[
            'mesAtual' => $mes,
            'anoAtual' => $ano,
        ]" :rightSub="isset($estabelecimento)
            ? ($estabelecimento['first_name'] ?? ($estabelecimento['name'] ?? 'Estabelecimento')) .
            ' • ID ' .
            ($estabelecimento['id'] ?? 'N/A')
            : null" />

    <!-- Saldo Cards (Prioridade para o Vendedor) -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="saldo-card saldo-disponivel fade-in-up" data-delay="0.1s">
                <div class="saldo-content">
                    <div class="saldo-valor">{{ $saldos['disponivel'] ?? 'R$ 0,00' }}</div>
                    <div class="saldo-label">
                        <i class="fas fa-info-circle me-1"></i>
                        Lançamentos Futuros
                    </div>
                </div>
                <div class="saldo-icon">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-3">
            <div class="saldo-card saldo-transito fade-in-up" data-delay="0.2s">
                <div class="saldo-content">
                    <div class="saldo-valor">{{ $saldos['transito'] ?? 'R$ 0,00' }}</div>
                    <div class="saldo-label">
                        <i class="fas fa-info-circle me-1"></i>
                        Saldo em trânsito
                    </div>
                </div>
                <div class="saldo-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-3">
            <div class="saldo-card saldo-processamento fade-in-up" data-delay="0.3s">
                <div class="saldo-content">
                    <div class="saldo-valor">{{ $saldos['processamento'] ?? 'R$ 0,00' }}</div>
                    <div class="saldo-label">
                        <i class="fas fa-info-circle me-1"></i>
                        Em Processamento
                    </div>
                </div>
                <div class="saldo-icon">
                    <i class="fas fa-spinner"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Visão Geral (Mesmo Visual Admin) -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header d-flex align-items-center">
            <h5 class="mb-0">Visão geral de transações</h5>
            <button class="btn btn-sm btn-outline-secondary ml-auto" type="button" data-toggle="collapse"
                data-target="#painel-visao-geral" aria-expanded="true" aria-controls="painel-visao-geral">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        <div id="painel-visao-geral" class="collapse show pt-3 px-3">
            <div class="row mb-2">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['total_amount_formatted']" icon="fas fa-wallet"
                        iconClass="bg-primary text-dark" label="Faturamento Líquido" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['total_original_amount_formatted']" icon="fas fa-clock"
                        iconClass="bg-info text-white" label="Faturamento Bruto" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['total_fees_formatted']" icon="fas fa-spinner"
                        iconClass="bg-warning text-dark" label="Descontos / Taxas" />
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['total_transactions']" icon="fas fa-receipt"
                        iconClass="bg-secondary text-white" label="Total de Transações" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['average_ticket_formatted']" icon="fas fa-ticket-alt"
                        iconClass="bg-success text-white" label="Ticket Médio" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['balance']" icon="fas fa-layer-group" iconClass="bg-dark text-white"
                        label="Saldo em Conta" />
                </div>
            </div>
        </div>
    </div>

    <!-- Distribuição (Mesmo Visual Admin) -->
    <div class="card mb-3 shadow-sm">
        <div class="card-header d-flex align-items-center">
            <h5 class="mb-0">Distribuição por meio de pagamento</h5>
            <button class="btn btn-sm btn-outline-secondary ml-auto" type="button" data-toggle="collapse"
                data-target="#painel-distribuicao-pagamento" aria-expanded="true">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        <div id="painel-distribuicao-pagamento" class="collapse show pt-3 px-3">
            <!-- Crédito -->
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_formatted']['CREDIT']" icon="fas fa-credit-card"
                        iconClass="bg-primary text-dark" label="Cartão de Crédito" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_percent_formatted']['CREDIT']"
                        icon="fas fa-credit-card" iconClass="bg-primary text-dark" label="Percentual Crédito" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_type']['CREDIT']" icon="fas fa-credit-card"
                        iconClass="bg-primary text-dark" label="Qtd Crédito" />
                </div>
            </div>
            <!-- Débito -->
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_formatted']['DEBIT']" icon="fas fa-university"
                        iconClass="bg-success text-white" label="Cartão de Débito" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_percent_formatted']['DEBIT']"
                        icon="fas fa-university" iconClass="bg-success text-white" label="Percentual Débito" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_type']['DEBIT']" icon="fas fa-university"
                        iconClass="bg-success text-white" label="Qtd Débito" />
                </div>
            </div>
            <!-- Pix -->
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_formatted']['PIX']" icon="fas fa-bolt"
                        iconClass="bg-info text-white" label="Pix" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_percent_formatted']['PIX']" icon="fas fa-bolt"
                        iconClass="bg-info text-white" label="Percentual Pix" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_type']['PIX']" icon="fas fa-bolt"
                        iconClass="bg-info text-white" label="Qtd Pix" />
                </div>
            </div>
            <!-- Boleto -->
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['billets_total_amount_formatted']" icon="fas fa-file-invoice-dollar"
                        iconClass="bg-warning text-dark" label="Volume Boleto" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['billets_total_amount_formatted']" icon="fas fa-file-invoice-dollar"
                        iconClass="bg-warning text-dark" label="Volume Bruto Boleto" />
                </div>
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['billets_total_fees_formatted']" icon="fas fa-file-invoice-dollar"
                        iconClass="bg-warning text-dark" label="Taxas Boleto" />
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Transações -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Últimas transações ({{ $mes }}/{{ $ano }})</h5>
                </div>
                <div class="card-body p-0">
                    @php $lista = $transacoes['data'] ?? []; @endphp
                    @if (empty($lista))
                        <div class="p-4 text-center text-muted">Nenhuma transação encontrada para este período.</div>
                    @else
                        <div class="table-responsive">
                            <table id="tabela-transacoes" class="table table-hover table-striped mb-0">
                                <thead>
                                    <tr class="table-header-juntter">
                                        <th class="ps-3">ID</th>
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th class="text-end pe-3">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lista as $transacao)
                                                        <tr>
                                                            <td class="ps-3">
                                                                <small class="text-muted font-monospace">
                                                                    {{ substr($transacao['external_id'] ?? 'N/A', 0, 8) }}...
                                                                </small>
                                                            </td>
                                                            <td>
                                                                @php $type = $transacao['type'] ?? ''; @endphp
                                                                @if ($type === 'PIX')
                                                                    <span class="badge badge-info"><i class="fas fa-qrcode me-1"></i>PIX</span>
                                                                @elseif($type === 'CREDIT')
                                                                    <span class="badge badge-primary"><i
                                                                            class="fas fa-credit-card me-1"></i>Crédito</span>
                                                                @elseif($type === 'DEBIT')
                                                                    <span class="badge badge-success"><i
                                                                            class="fas fa-credit-card me-1"></i>Débito</span>
                                                                @elseif($type === 'BILLET' || $type === 'BOLETO')
                                                                    <span class="badge badge-warning"><i
                                                                            class="fas fa-file-invoice me-1"></i>Boleto</span>
                                                                @else
                                                                    <span class="badge badge-secondary">{{ $type }}</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <div>
                                                                    <strong class="text-success">R$
                                                                        {{ number_format(($transacao['amount'] ?? 0) / 100, 2, ',', '.') }}</strong>
                                                                    @if (isset($transacao['fees']) && $transacao['fees'] > 0)
                                                                        <br><small class="text-muted">Taxa: R$
                                                                            {{ number_format(($transacao['fees'] ?? 0) / 100, 2, ',', '.') }}</small>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="text-muted">
                                                                    {{ \Carbon\Carbon::parse($transacao['created_at'] ?? now())->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                @php $status = $transacao['status'] ?? ''; @endphp
                                                                @if ($status === 'PAID')
                                                                    <span class="badge badge-success"><i class="fas fa-check me-1"></i>Pago</span>
                                                                @elseif($status === 'PENDING')
                                                                    <span class="badge badge-warning"><i class="fas fa-clock me-1"></i>Pendente</span>
                                                                @elseif($status === 'FAILED')
                                                                    <span class="badge badge-danger"><i class="fas fa-times me-1"></i>Falhou</span>
                                                                @elseif($status === 'CANCELED')
                                                                    <span class="badge badge-secondary"><i class="fas fa-ban me-1"></i>Cancelado</span>
                                                                @elseif($status === 'REFUNDED')
                                                                    <span class="badge badge-info"><i class="fas fa-undo me-1"></i>Estornado</span>
                                                                @elseif($status === 'APPROVED')
                                                                    <span class="badge badge-success"><i
                                                                            class="fas fa-check-circle me-1"></i>Aprovado</span>
                                                                @else
                                                                    <span class="badge badge-secondary">{{ $status }}</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-end pe-3">
                                                                <a href="{{ ($transacao['type'] ?? '') === 'BILLET' || ($transacao['type'] ?? '') === 'BOLETO'
                                        ? route('cobranca.boleto.detalhes', $transacao['external_id'])
                                        : route('cobranca.transacao.detalhes', $transacao['external_id']) }}"
                                                                    class="btn btn-sm btn-outline-info">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
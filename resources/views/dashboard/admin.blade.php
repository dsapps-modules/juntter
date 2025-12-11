@extends('templates.dashboard-template')
@section('title', 'Dashboard')

@php
    $breadcrumbItems = [['label' => 'Administração', 'icon' => 'fas fa-cogs', 'url' => '#']];
@endphp

@section('content')

    <x-breadcrumb :items="$breadcrumbItems" :filtroData="[
        'mesAtual' => $mes,
        'anoAtual' => $ano,
    ]" />

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center">
            <h5 class="mb-0">Visão geral de transações</h5>
            <button class="btn btn-sm btn-outline-secondary ml-auto" type="button" data-toggle="collapse"
                data-target="#painel-visao-geral" aria-expanded="true" aria-controls="painel-visao-geral">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        <div id="painel-visao-geral" class="collapse show pt-3 px-3">
            <div class="row mb-2" id="linha_1">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['total_amount_formatted']" icon="fas fa-wallet" iconClass="bg-primary text-dark"
                        label="Faturamento Líquido" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['total_original_amount_formatted'] ?? null" icon="fas fa-clock" iconClass="bg-info text-white"
                        label="Faturamento Bruto" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['total_fees_formatted'] ?? null" icon="fas fa-spinner" iconClass="bg-warning text-dark"
                        label="Descontos / Taxas" />
                </div>
            </div>

            <div class="row" id="linha_2">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['total_transactions']" icon="fas fa-receipt" iconClass="bg-secondary text-white"
                        label="Total de Transações" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['average_ticket_formatted'] ?? null" icon="fas fa-ticket-alt" iconClass="bg-success text-white"
                        label="Ticket Médio" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['average_installments'] ?? null" icon="fas fa-layer-group" iconClass="bg-dark text-white"
                        label="Média de parcelas" />
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center">
            <h5 class="mb-0">Distribuição por meio de pagamento</h5>
            <button class="btn btn-sm btn-outline-secondary ml-auto" type="button" data-toggle="collapse"
                data-target="#painel-distribuicao-pagamento" aria-expanded="true" aria-controls="painel-distribuicao-pagamento">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        <div id="painel-distribuicao-pagamento" class="collapse show pt-3 px-3">
            <div class="row" id="linha_3">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_formatted']['CREDIT']" icon="fas fa-credit-card"
                        iconClass="bg-primary text-dark" label="Cartão de Crédito" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_formatted']['DEBIT'] ?? null" icon="fas fa-university"
                        iconClass="bg-success text-white" label="Cartão de Débito" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_formatted']['PIX'] ?? null" icon="fas fa-bolt"
                        iconClass="bg-info text-white" label="Pix" />
                </div>
            </div>

            <div class="row" id="linha_4">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_percent_formatted']['CREDIT']" icon="fas fa-percent"
                        iconClass="bg-primary text-dark" label="Cartão de Crédito" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_percent_formatted']['DEBIT'] ?? null" icon="fas fa-chart-pie"
                        iconClass="bg-success text-white" label="Cartão de Débito" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['amount_by_type_percent_formatted']['PIX'] ?? null" icon="fas fa-bolt"
                        iconClass="bg-info text-white" label="Pix" />
                </div>
            </div>

            <div class="row" id="linha_5">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_type']['CREDIT']" icon="fas fa-credit-card"
                        iconClass="bg-primary text-dark" label="Cartão de Crédito" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_type']['DEBIT'] ?? null" icon="fas fa-university"
                        iconClass="bg-success text-white" label="Cartão de Débito" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_type']['PIX'] ?? null" icon="fas fa-bolt"
                        iconClass="bg-info text-white" label="Pix" />
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center">
            <h5 class="mb-0">Visão de status de pagamentos</h5>
            <button class="btn btn-sm btn-outline-secondary ml-auto" type="button" data-toggle="collapse"
                data-target="#painel-status-pagamentos" aria-expanded="true" aria-controls="painel-status-pagamentos">
                <i class="fas fa-chevron-up"></i>
            </button>
        </div>
        <div id="painel-status-pagamentos" class="collapse show pt-3 px-3">
            <div class="row" id="linha_6">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_status']['PAID']" icon="fas fa-check-circle"
                        iconClass="bg-success text-white" label="Pagamento Efetivado" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_status']['FAILED'] ?? null" icon="fas fa-times-circle"
                        iconClass="bg-danger text-white" label="Pagamento Cancelado" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_status']['REFUNDED'] ?? null" icon="fas fa-undo"
                        iconClass="bg-warning text-dark" label="Pagamento Devolvido" />
                </div>
            </div>

            <div class="row" id="linha_7">
                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_status_percent']['PAID']" icon="fas fa-check-circle"
                        iconClass="bg-success text-white" label="Pagamento Efetivado" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_status_percent']['FAILED'] ?? null" icon="fas fa-times-circle"
                        iconClass="bg-danger text-white" label="Pagamento Cancelado" />
                </div>

                <div class="col-lg-4 col-md-6 mb-3">
                    <x-util.dash-card :amount="$metrics['transactions_by_status_percent']['REFUNDED'] ?? null" icon="fas fa-undo"
                        iconClass="bg-warning text-dark" label="Pagamento Devolvido" />
                </div>
            </div>
        </div>
    </div>


@endsection

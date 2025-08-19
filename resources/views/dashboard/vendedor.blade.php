@extends('templates.dashboard-template')

@section('title', 'Dashboard')

@section('content')
<x-dashboard-main 
    :title="'Dashboard Vendedor'"
    :saldos="$saldos"
    :metricas="$metricas"
    :metricasGeral="$metricasGeral"
    :metricasCartao="$metricasCartao"
    :metricasBoleto="$metricasBoleto"
    :mesAtual="$mesAtual"
    :anoAtual="$anoAtual"
    :breadcrumbItems="[
        [
            'label' => 'Vendas',
            'icon' => 'fas fa-chart-line',
            'url' => '#',
          
        ]
        
    ]"
    :rightSub="isset($estabelecimento) ? (($estabelecimento['first_name'] ?? $estabelecimento['name'] ?? 'Estabelecimento') . ' • ID ' . ($estabelecimento['id'] ?? 'N/A')) : null"
    :showSaldos="auth()->user()?->isAdminLoja()"
/>



    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Transações do Estabelecimento (últimos 30 dias)</h5>
                </div>
                <div class="card-body p-0">
                    @php
                        $lista = $transacoes['data'] ?? [];
                    @endphp
                    @if(empty($lista))
                        <div class="p-4 text-center text-muted">Nenhuma transação encontrada.</div>
                    @else
                        <div class="table-responsive">
                            <table id="tabela-transacoes" class="table table-hover table-striped">
                                <thead>
                                    <tr class="table-header-juntter">
                                        <th></th>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                      
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lista as $transacao)
                                        <tr>
                                            <td></td>
                                            <td>
                                                <small class="text-muted font-monospace">
                                                    {{ substr($transacao['_id'] ?? 'N/A', 0, 8) }}...
                                                </small>
                                            </td>
                                            <td>
                                                @if(isset($transacao['type']))
                                                    @if($transacao['type'] === 'PIX')
                                                        <span class="badge badge-info">
                                                            <i class="fas fa-qrcode me-1"></i>PIX
                                                        </span>
                                                    @elseif($transacao['type'] === 'CREDIT')
                                                        <span class="badge badge-primary">
                                                            <i class="fas fa-credit-card me-1"></i>Crédito
                                                            @if(isset($transacao['installments']) && $transacao['installments'] > 1)
                                                                <small>({{ $transacao['installments'] }}x)</small>
                                                            @endif
                                                        </span>
                                                    @elseif($transacao['type'] === 'DEBIT')
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-credit-card me-1"></i>Débito
                                                        </span>
                                                    @elseif($transacao['type'] === 'BOLETO')
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-file-invoice me-1"></i>Boleto
                                                        </span>
                                                    @else
                                                        <span class="badge badge-secondary">{{ $transacao['type'] }}</span>
                                                    @endif
                                                @else
                                                    <span class="badge badge-secondary">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div>
                                                    <strong class="text-success">
                                                        R$ {{ number_format(($transacao['amount'] ?? 0) / 100, 2, ',', '.') }}
                                                    </strong>
                                                    @if(isset($transacao['fees']) && $transacao['fees'] > 0)
                                                        <br><small class="text-muted">Taxa: R$ {{ number_format(($transacao['fees'] ?? 0) / 100, 2, ',', '.') }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <span class="text-muted">
                                                    {{ \Carbon\Carbon::parse($transacao['created_at'] ?? now())->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(isset($transacao['status']))
                                                    @if($transacao['status'] === 'PAID')
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-check me-1"></i>Pago
                                                        </span>
                                                    @elseif($transacao['status'] === 'PENDING')
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-clock me-1"></i>Pendente
                                                        </span>
                                                    @elseif($transacao['status'] === 'FAILED')
                                                        <span class="badge badge-danger">
                                                            <i class="fas fa-times me-1"></i>Falhou
                                                        </span>
                                                    @elseif($transacao['status'] === 'CANCELED')
                                                        <span class="badge badge-secondary">
                                                            <i class="fas fa-ban me-1"></i>Cancelado
                                                        </span>
                                                    @elseif($transacao['status'] === 'REFUNDED')
                                                        <span class="badge badge-info">
                                                            <i class="fas fa-undo me-1"></i>Estornado
                                                        </span>
                                                    @elseif($transacao['status'] === 'APPROVED')
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-check-circle me-1"></i>Aprovado
                                                        </span>
                                                    @else
                                                        <span class="badge badge-secondary">{{ $transacao['status'] }}</span>
                                                    @endif
                                                @else
                                                    <span class="badge badge-secondary">Desconhecido</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('cobranca.transacao.detalhes', $transacao['_id'] ?? '') }}"
                                                       class="btn btn-sm btn-outline-info" title="Ver detalhes">
                                                        <i class="fas fa-eye"></i>
                                                        Ver detalhes
                                                    </a>
                                                </div>
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
</div>
@push('scripts')

@endpush
@endsection
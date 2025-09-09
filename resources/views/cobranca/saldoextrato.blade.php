@extends('templates.dashboard-template')

@section('title', 'Saldo e Extrato')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Cobranças', 'icon' => 'fas fa-credit-card', 'url' => route('cobranca.index')],
        ['label' => 'Saldo e Extrato', 'icon' => 'fas fa-chart-bar', 'url' => '#']
    ]"
/>

<!-- Seção Saldo -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-wallet me-2 text-primary"></i>
                        Extrato
                        @if(!empty($mesAtual) || !empty($anoAtual))
                            <small class="text-muted">
                                @if(!empty($mesAtual) && !empty($anoAtual))
                                    - {{ date('F/Y', mktime(0, 0, 0, $mesAtual, 1, $anoAtual)) }}
                                @elseif(!empty($anoAtual))
                                    - {{ $anoAtual }}
                                @elseif(!empty($mesAtual))
                                    - {{ date('F', mktime(0, 0, 0, $mesAtual, 1)) }} {{ date('Y') }}
                                @endif
                            </small>
                        @endif
                    </h5>
                    
                    <!-- Filtro de Data -->
                    <div class="d-flex align-items-center gap-2">
                        <form method="GET" action="{{ request()->url() }}" class="d-flex align-items-center gap-1">
                            <select name="mes" class="form-select form-select-sm" style="width: 100px; font-size: 0.8rem;">
                                <option value="" {{ empty($mesAtual) ? 'selected' : '' }}>Todos</option>
                                <option value="1" {{ $mesAtual == 1 ? 'selected' : '' }}>Janeiro</option>
                                <option value="2" {{ $mesAtual == 2 ? 'selected' : '' }}>Fevereiro</option>
                                <option value="3" {{ $mesAtual == 3 ? 'selected' : '' }}>Março</option>
                                <option value="4" {{ $mesAtual == 4 ? 'selected' : '' }}>Abril</option>
                                <option value="5" {{ $mesAtual == 5 ? 'selected' : '' }}>Maio</option>
                                <option value="6" {{ $mesAtual == 6 ? 'selected' : '' }}>Junho</option>
                                <option value="7" {{ $mesAtual == 7 ? 'selected' : '' }}>Julho</option>
                                <option value="8" {{ $mesAtual == 8 ? 'selected' : '' }}>Agosto</option>
                                <option value="9" {{ $mesAtual == 9 ? 'selected' : '' }}>Setembro</option>
                                <option value="10" {{ $mesAtual == 10 ? 'selected' : '' }}>Outubro</option>
                                <option value="11" {{ $mesAtual == 11 ? 'selected' : '' }}>Novembro</option>
                                <option value="12" {{ $mesAtual == 12 ? 'selected' : '' }}>Dezembro</option>
                            </select>
                            <select name="ano" class="form-select form-select-sm" style="width: 100px; font-size: 0.8rem;">
                                @for ($i = date('Y'); $i >= date('Y')-2; $i--)
                                    <option value="{{ $i }}" {{ $anoAtual == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                            <button type="submit" class="btn btn-warning btn-sm ml-2" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                                <i class="fas fa-filter"></i>
                            </button>
                            @if(!empty($mesAtual) || !empty($anoAtual))
                                <a href="{{ route('cobranca.saldoextrato') }}" class="btn btn-outline-secondary btn-sm ml-1" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;" title="Limpar Filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                
                <!-- Cards de Saldo -->
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="saldo-card saldo-disponivel">
                            <div class="saldo-content">
                                <div class="saldo-valor">R$ {{ number_format(($saldo['total']['amount'] ?? 0) / 100, 2, ',', '.') }}</div>
                                <div class="saldo-label">Total em lançamentos futuros</div>
                            </div>
                            <div class="saldo-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="saldo-card saldo-bloqueado-boleto">
                            <div class="saldo-content">
                                <div class="saldo-valor">R$ {{ number_format(($saldo['thirtyDays']['amount'] ?? 0) / 100, 2, ',', '.') }}</div>
                                <div class="saldo-label">Próximos 30 dias</div>
                            </div>
                            <div class="saldo-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="saldo-card saldo-bloqueado">
                            <div class="saldo-content">
                                <div class="saldo-valor">R$ {{ number_format(($saldo['sevenDays']['amount'] ?? 0) / 100, 2, ',', '.') }}</div>
                                <div class="saldo-label">Próximos 7 dias</div>
                            </div>
                            <div class="saldo-icon">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="saldo-card saldo-transito">
                            <div class="saldo-content">
                                <div class="saldo-valor">{{ count($saldo['calendar'] ?? []) }}</div>
                                <div class="saldo-label">Datas de lançamento</div>
                            </div>
                            <div class="saldo-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Seção Dados Consolidados -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="fw-bold mb-0">
                    <i class="fas fa-chart-line me-2 text-primary"></i>
                    Dados Consolidados
                </h5>
            </div>
            <div class="card-body p-4">
                <!-- Projeção por Mês -->
                @if(isset($projecaoMensal) && count($projecaoMensal) > 0)
                    <div>
                        <h6 class="fw-bold mb-3">
                            Projeção por Mês
                            @if(!empty($mesAtual) || !empty($anoAtual))
                                <small class="text-muted">
                                    @if(!empty($mesAtual) && !empty($anoAtual))
                                        - {{ date('F/Y', mktime(0, 0, 0, $mesAtual, 1, $anoAtual)) }}
                                    @elseif(!empty($anoAtual))
                                        - {{ $anoAtual }}
                                    @elseif(!empty($mesAtual))
                                        - {{ date('F', mktime(0, 0, 0, $mesAtual, 1)) }} {{ date('Y') }}
                                    @endif
                                </small>
                            @endif
                        </h6>
                        <div class="row justify-content-center">
                            @foreach($projecaoMensal as $mes)
                                <div class="col-md-2 mb-2">
                                    <div class="text-center p-3 {{ $mes['is_current'] ? 'bg-warning text-white' : 'bg-light' }} rounded shadow-sm">
                                        <small class="text-muted d-block {{ $mes['is_current'] ? 'text-black' : '' }}">
                                            {{ $mes['formatted_date'] }}
                                            @if($mes['is_current'])
                                                <br><small class="badge bg-warning text-dark">Atual</small>
                                            @endif
                                        </small>
                                        <strong class="{{ $mes['is_current'] ? 'text-white' : 'text-primary' }} fs-6">
                                            {{ $mes['formatted_amount'] }}
                                        </strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-info-circle text-muted fa-2x mb-3"></i>
                        <p class="text-muted mb-0">Nenhuma projeção mensal disponível.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Seção Extrato Detalhado -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-list me-2 text-primary"></i>
                        Extrato Detalhado
                        @if(isset($filtros['data_inicio']))
                            - {{ \Carbon\Carbon::parse($filtros['data_inicio'])->format('d/m/Y') }}
                        @else
                            - Hoje
                        @endif
                    </h5>
                
                    <span class="badge bg-primary">{{ count($extrato['data'] ?? []) }} transações</span>
                </div>
                <div class="d-flex justify-content-end">
                    <a href="https://login.juntter.com.br/client/pix" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-plus me-1"></i>
                        Sacar Meu Dinheiro
                    </a>
                </div>
               
            </div>
           
            <div class="card-body p-4">
                <!-- Filtros dentro do extrato -->
                <div class="mb-4">
                    <form method="GET" action="{{ route('cobranca.saldoextrato') }}" id="formFiltros">
                        <div class="row align-items-end">
                                                    <div class="col-md-2 mb-3">
                            <label for="gateway_authorization" class="form-label fw-bold">Gateway:</label>
                            <select class="form-select" name="gateway_authorization" id="gateway_authorization">
                                <option value="">Todos</option>
                                <option value="PAYTIME" {{ $filtros['gateway_authorization'] == 'PAYTIME' ? 'selected' : '' }}>PAYTIME</option>
                                <option value="ZOOP" {{ $filtros['gateway_authorization'] == 'ZOOP' ? 'selected' : '' }}>ZOOP</option>
                                <option value="PAGSEGURO" {{ $filtros['gateway_authorization'] == 'PAGSEGURO' ? 'selected' : '' }}>PAGSEGURO</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="data_inicio" class="form-label fw-bold">Data:</label>
                            <select class="form-select" name="data_inicio" id="data_inicio">
                                <option value="">Hoje</option>
                                @if(isset($saldo['calendar']))
                                    @foreach($saldo['calendar'] as $data)
                                        <option value="{{ $data['date'] }}" 
                                                {{ $filtros['data_inicio'] == $data['date'] ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::parse($data['date'])->format('d/m/Y') }} 
                                            (R$ {{ number_format($data['amount'] / 100, 2, ',', '.') }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label for="search" class="form-label fw-bold">Buscar:</label>
                            <input type="text" class="form-control" name="search" id="search" 
                                   placeholder="Buscar..." value="{{ $filtros['search'] ?? '' }}">
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label for="perPage" class="form-label fw-bold">Por página:</label>
                            <select class="form-select" name="perPage" id="perPage">
                                <option value="10" {{ $filtros['perPage'] == 10 ? 'selected' : '' }}>10</option>
                                <option value="20" {{ $filtros['perPage'] == 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ $filtros['perPage'] == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $filtros['perPage'] == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <button type="submit" class="btn btn-warning text-white w-100">
                                <i class="fas fa-search me-2"></i>
                                Filtrar
                            </button>
                        </div>
                        </div>
                    </form>
                </div>

                @if(isset($extrato['data']) && count($extrato['data']) > 0)
                    <div class="table-responsive">
                        <table id="saldoExtratoTable" class="table table-hover table-striped">
                            <thead class="table-header-juntter">
                                <tr>
                                    <th></th>
                                    <th>Data</th>
                                    <th>Modalidade</th>
                                    <th>Bandeira</th>
                                 
                                    <th>Valor Original</th>
                                    <th>Valor Líquido</th>
                                    <th>Parcela</th>
                                    <th>Data Liberação</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($extrato['data'] as $transacao)
                                    <tr>
                                        <td></td>
                                        <td>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($transacao['transaction_date'])->format('d/m/Y H:i') }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $transacao['transaction_modality'] }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $transacao['brand_name'] }}</span>
                                        </td>
                                              
                                        <td>
                                            <strong>R$ {{ number_format($transacao['transaction_original_amount'] / 100, 2, ',', '.') }}</strong>
                                        </td>
                                        <td>
                                            <strong class="text-success">R$ {{ number_format($transacao['transaction_amount'] / 100, 2, ',', '.') }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning">{{ $transacao['installment'] }}x</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($transacao['date'])->format('d/m/Y') }}
                                            </small>
                                        </td>
                                        <td>
                                            <a href="{{ route('cobranca.transacao.detalhes', $transacao['transaction_id']) }}?from=saldoextrato" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>
                                                Ver Detalhes
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-info-circle text-muted fa-2x mb-3"></i>
                        <p class="text-muted mb-0">Nenhuma transação encontrada para os filtros aplicados.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection


@extends('templates.dashboard-template')

@section('title', 'Detalhes do Plano')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Cobranças', 'icon' => 'fas fa-credit-card', 'url' => route('cobranca.index')],
        ['label' => 'Planos Comerciais', 'icon' => 'fas fa-list-alt', 'url' => route('cobranca.planos')],
        ['label' => 'Detalhes', 'icon' => 'fas fa-eye', 'url' => '#']
    ]"
/>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">{{ $plano['name'] ?? 'Plano Comercial' }}</h3>
                        <p class="text-muted mb-0">Detalhes do plano comercial</p>
                    </div>
                    <div>
                        <a href="{{ route('cobranca.planos') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </div>

                <!-- Informações Principais -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="info-card bg-light rounded-3 p-4 mb-3">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-info-circle me-2"></i>Informações Principais
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">ID do Plano</small>
                                        <strong>{{ $plano['id'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Nome</small>
                                        <strong>{{ $plano['name'] ?? 'N/A' }}</strong>
                                    </div>
                                    @if($plano['description'])
                                        <div class="info-item mb-2">
                                            <small class="text-muted d-block">Descrição</small>
                                            <strong>{{ $plano['description'] }}</strong>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Gateway ID</small>
                                        <strong>{{ $plano['gateway_id'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Tipo</small>
                                        <strong>{{ $plano['type'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Modalidade</small>
                                        <strong>
                                            @if($plano['modality'] === 'ONLINE')
                                                Online
                                            @else
                                                Presencial
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="status-card rounded-3 p-4 mb-3">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-chart-line me-2"></i>Status e Configurações
                            </h6>
                            <div class="status-item mb-3">
                                <small class="text-muted d-block">Status</small>
                                @if(isset($plano['active']))
                                    @if($plano['active'])
                                        <span class="badge badge-success fs-6">Ativo</span>
                                    @else
                                        <span class="badge badge-danger fs-6">Inativo</span>
                                    @endif
                                @else
                                    <span class="badge badge-secondary fs-6">N/A</span>
                                @endif
                            </div>
                            <div class="status-item mb-3">
                                <small class="text-muted d-block">Antecipação</small>
                                @if(isset($plano['allow_anticipation']))
                                    @if($plano['allow_anticipation'])
                                        <span class="badge badge-success fs-6">Permitida</span>
                                    @else
                                        <span class="badge badge-warning fs-6">Não Permitida</span>
                                    @endif
                                @else
                                    <span class="badge badge-secondary fs-6">N/A</span>
                                @endif
                            </div>
                            <div class="status-item">
                                <small class="text-muted d-block">Modalidade</small>
                                @if(isset($plano['modality']))
                                    @if($plano['modality'] === 'ONLINE')
                                        <span class="badge badge-info fs-6">Online</span>
                                    @else
                                        <span class="badge badge-primary fs-6">Presencial</span>
                                    @endif
                                @else
                                    <span class="badge badge-secondary fs-6">N/A</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categorias -->
                @if(isset($plano['categories']) && count($plano['categories']) > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-tags me-2"></i>Categorias do Plano
                            </h6>
                            <div class="row">
                                @foreach($plano['categories'] as $categoria)
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded-3 p-3 bg-white">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="fw-bold mb-1">{{ $categoria['name'] }}</h6>
                                                    <small class="text-muted">ID: {{ $categoria['id'] }}</small>
                                                </div>
                                                <span class="badge badge-secondary">{{ $categoria['gateway_key'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Taxas por bandeira -->
                @if(isset($plano['flags']) && count($plano['flags']) > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-credit-card me-2"></i>Taxas por Bandeira
                            </h6>
                            
                            <!-- Select para escolher bandeira -->
                            <div class="mb-4">
                                <label for="bandeiraSelect" class="form-label fw-bold">Selecione a Bandeira:</label>
                                <select class="form-select" id="bandeiraSelect" style="max-width: 300px;">
                                    @foreach($plano['flags'] as $index => $flag)
                                        <option value="{{ $index }}" {{ $index === 0 ? 'selected' : '' }}>
                                            {{ $flag['name'] }} {{ $flag['active'] ? '(Ativo)' : '(Inativo)' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Detalhes das taxas da bandeira selecionada -->
                            @foreach($plano['flags'] as $index => $flag)
                                <div class="bandeira-detalhes {{ $index === 0 ? '' : 'd-none' }}" id="bandeira-{{ $index }}">
                                    <!-- Header da Bandeira -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="info-card bg-white rounded-3 p-4 border">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h5 class="fw-bold mb-1">{{ $flag['name'] }}</h5>
                                                        <small class="text-muted">ID: {{ $flag['id'] }}</small>
                                                    </div>
                                                    <span class="badge badge-{{ $flag['active'] ? 'success' : 'danger' }} fs-6">
                                                        {{ $flag['active'] ? 'Ativo' : 'Inativo' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Taxas por tipo de pagamento -->
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="info-card bg-light rounded-3 p-4">
                                                <h6 class="fw-bold text-primary mb-3">
                                                    <i class="fas fa-percentage me-2"></i>Taxas por Tipo de Pagamento
                                                </h6>
                                                <div class="row">
                                                    <!-- PIX -->
                                                    <div class="col-md-4 mb-3">
                                                        <div class="info-item text-center p-3 bg-white rounded-3 border">
                                                            <div class="text-muted small mb-2 fw-bold">PIX</div>
                                                            <div class="row g-2">
                                                                <div class="col-6">
                                                                    <small class="text-muted d-block">Standard</small>
                                                                    <strong class="text-primary">{{ number_format($flag['standard']['pix'], 2) }}%</strong>
                                                                </div>
                                                                <div class="col-6">
                                                                    <small class="text-muted d-block">Markup</small>
                                                                    <strong class="text-success">{{ number_format($flag['markup']['pix'], 2) }}%</strong>
                                                                </div>
                                                            </div>
                                                            <hr class="my-2">
                                                            <div class="text-muted small">Final</div>
                                                            <strong class="h6 text-primary">{{ number_format($flag['fees']['pix'], 2) }}%</strong>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Débito -->
                                                    <div class="col-md-4 mb-3">
                                                        <div class="info-item text-center p-3 bg-white rounded-3 border">
                                                            <div class="text-muted small mb-2 fw-bold">Débito</div>
                                                            <div class="row g-2">
                                                                <div class="col-6">
                                                                    <small class="text-muted d-block">Standard</small>
                                                                    <strong class="text-primary">{{ number_format($flag['standard']['debit'], 2) }}%</strong>
                                                                </div>
                                                                <div class="col-6">
                                                                    <small class="text-muted d-block">Markup</small>
                                                                    <strong class="text-success">{{ number_format($flag['markup']['debit'], 2) }}%</strong>
                                                                </div>
                                                            </div>
                                                            <hr class="my-2">
                                                            <div class="text-muted small">Final</div>
                                                            <strong class="h6 text-primary">{{ number_format($flag['fees']['debit'], 2) }}%</strong>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Crédito 1x -->
                                                    <div class="col-md-4 mb-3">
                                                        <div class="info-item text-center p-3 bg-white rounded-3 border">
                                                            <div class="text-muted small mb-2 fw-bold">Crédito 1x</div>
                                                            <div class="row g-2">
                                                                <div class="col-6">
                                                                    <small class="text-muted d-block">Standard</small>
                                                                    <strong class="text-primary">{{ number_format($flag['standard']['credit']['1x'], 2) }}%</strong>
                                                                </div>
                                                                <div class="col-6">
                                                                    <small class="text-muted d-block">Markup</small>
                                                                    <strong class="text-success">{{ number_format($flag['markup']['credit']['1x'], 2) }}%</strong>
                                                                </div>
                                                            </div>
                                                            <hr class="my-2">
                                                            <div class="text-muted small">Final</div>
                                                            <strong class="h6 text-primary">{{ number_format($flag['fees']['credit']['1x'], 2) }}%</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Todas as parcelas de crédito -->
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="info-card bg-light rounded-3 p-4">
                                                <h6 class="fw-bold text-primary mb-3">
                                                    <i class="fas fa-list-ol me-2"></i>Todas as Parcelas de Crédito
                                                </h6>
                                                <div class="row">
                                                    @foreach($flag['parcelas_compactadas'] as $parcela)
                                                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                                                            <div class="info-item text-center p-3 bg-white rounded-3 border">
                                                                <div class="text-muted small mb-2 fw-bold">{{ $parcela['parcela'] }}</div>
                                                                <div class="h5 fw-bold text-primary mb-0">{{ number_format($parcela['taxa'], 2) }}%</div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Informações de Data -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-calendar me-2"></i>Informações de Data
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Criado em</small>
                                        <strong>{{ \Carbon\Carbon::parse($plano['created_at'])->format('d/m/Y H:i:s') }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Última atualização</small>
                                        <strong>{{ \Carbon\Carbon::parse($plano['updated_at'])->format('d/m/Y H:i:s') }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

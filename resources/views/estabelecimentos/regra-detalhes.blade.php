@extends('templates.dashboard-template')

@section('title', 'Detalhes da Regra de Split')

@section('breadcrumb')
<x-breadcrumb 
    :items="[
        ['label' => 'Estabelecimentos', 'icon' => 'fas fa-building', 'url' => route('admin.dashboard')],
        ['label' => 'Detalhes', 'icon' => 'fas fa-eye', 'url' => route('estabelecimentos.show', $estabelecimento['id'])],
        ['label' => 'Regra de Split', 'icon' => 'fas fa-share-alt', 'url' => '#']
    ]"
/>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <!-- Cabeçalho da Página -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-primary mb-1">
                        <i class="fas fa-share-alt me-2"></i>Regra de Split
                    </h4>
                    <p class="text-muted mb-0 small">
                        Estabelecimento: <strong>{{ $estabelecimento['first_name'] }}</strong>
                    </p>
                </div>
                <div>
                    <a href="{{ route('estabelecimentos.show', $estabelecimento['id']) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>

            <!-- Cards de Informações -->
            <div class="row">
                <!-- Card Principal -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-lg rounded-4 mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-cog me-2"></i>Informações da Regra
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Título e Status -->
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-primary">Título da Regra</label>
                                        <p class="form-control-plaintext fs-5">{{ $regra['title'] }}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-primary">Status</label>
                                        <p class="form-control-plaintext">
                                            @if($regra['active'])
                                                <span class="badge bg-success fs-6">
                                                    <i class="fas fa-check-circle me-1"></i>Ativa
                                                </span>
                                            @else
                                                <span class="badge bg-secondary fs-6">
                                                    <i class="fas fa-times-circle me-1"></i>Inativa
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Configurações -->
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="info-card bg-light rounded-3 p-3 mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-credit-card text-info me-2"></i>
                                            <label class="form-label fw-bold mb-0">Modalidade</label>
                                        </div>
                                        <span class="badge bg-info fs-6">{{ $regra['modality'] }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-card bg-light rounded-3 p-3 mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-shopping-cart text-secondary me-2"></i>
                                            <label class="form-label fw-bold mb-0">Canal</label>
                                        </div>
                                        <span class="badge bg-secondary fs-6">{{ $regra['channel'] }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-card bg-light rounded-3 p-3 mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-percentage text-warning me-2"></i>
                                            <label class="form-label fw-bold mb-0">Tipo de Divisão</label>
                                        </div>
                                        <span class="badge bg-warning fs-6">{{ $regra['division'] }}</span>
                                    </div>
                                </div>
                            </div>

                            @if(isset($regra['installment']))
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="info-card bg-light rounded-3 p-3 mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-calendar-alt text-dark me-2"></i>
                                            <label class="form-label fw-bold mb-0">Parcelas</label>
                                        </div>
                                        <span class="badge bg-light text-dark fs-6">{{ $regra['installment'] }}x</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-card bg-light rounded-3 p-3 mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-chart-pie text-primary me-2"></i>
                                            <label class="form-label fw-bold mb-0">Percentual Principal</label>
                                        </div>
                                        <span class="badge bg-primary fs-6">{{ $regra['establishment_percentage'] }}%</span>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Estabelecimentos Participantes -->
                    <div class="card border-0 shadow-lg rounded-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0 fw-bold">
                                <i class="fas fa-users me-2"></i>Estabelecimentos Participantes
                                <span class="badge bg-light text-dark ms-2">{{ count($regra['establishments']) }}</span>
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr class="table-success">
                                            <th>Estabelecimento</th>
                                            <th>Valor</th>
                                            <th>Prioridade</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($regra['establishments'] as $participante)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $participante['establishment']['first_name'] }}</strong>
                                                    @if($participante['establishment']['last_name'])
                                                        <br><small class="text-muted">{{ $participante['establishment']['last_name'] }}</small>
                                                    @endif
                                                    <br><small class="text-muted">ID: {{ $participante['establishment']['id'] }}</small>
                                                    <br><small class="text-muted">Doc: {{ $participante['establishment']['document'] }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-success fs-6">
                                                    {{ $participante['value'] }}{{ $regra['division'] == 'PERCENTAGE' ? '%' : ' centavos' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info fs-6">{{ $participante['priority'] }}</span>
                                            </td>
                                            <td>
                                                @if($participante['active'])
                                                    <span class="badge bg-success fs-6">
                                                        <i class="fas fa-check-circle me-1"></i>Ativo
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary fs-6">
                                                        <i class="fas fa-times-circle me-1"></i>Inativo
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Estabelecimento Principal -->
                    <div class="card border-0 shadow-lg rounded-4 mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0 fw-bold">
                                <i class="fas fa-crown me-2"></i>Estabelecimento Principal
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="avatar-placeholder bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                    <i class="fas fa-building fa-2x"></i>
                                </div>
                            </div>
                            <div class="info-item mb-3">
                                <label class="form-label fw-bold text-primary mb-1">Nome</label>
                                <p class="form-control-plaintext mb-0">{{ $regra['establishment']['first_name'] }}</p>
                            </div>
                            <div class="info-item mb-3">
                                <label class="form-label fw-bold text-primary mb-1">Nome Fantasia</label>
                                <p class="form-control-plaintext mb-0">
                                    {{ $regra['establishment']['last_name'] ?? 'Não informado' }}
                                </p>
                            </div>
                            <div class="info-item mb-3">
                                <label class="form-label fw-bold text-primary mb-1">Documento</label>
                                <p class="form-control-plaintext mb-0">{{ $regra['establishment']['document'] }}</p>
                            </div>
                            <div class="info-item">
                                <label class="form-label fw-bold text-primary mb-1">ID</label>
                                <p class="form-control-plaintext mb-0">{{ $regra['establishment']['id'] }}</p>
                            </div>
                        </div>
                    </div>

                    
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .info-card {
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .info-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .info-item strong {
        color: #495057;
        font-size: 0.95rem;
    }

    .avatar-placeholder {
        background: linear-gradient(135deg, #007bff, #0056b3);
    }
</style>
@endsection 
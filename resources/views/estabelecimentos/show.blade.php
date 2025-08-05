@extends('templates.dashboard-template')

@section('title', 'Detalhes do Estabelecimento')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">{{ $estabelecimento['name'] ?? 'Estabelecimento' }}</h3>
                        <p class="text-muted mb-0">Detalhes do estabelecimento</p>
                    </div>
                    <div>
                        <a href="{{ route('estabelecimentos.edit', $estabelecimento['id']) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </div>
                
                <!-- Informações Principais -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="info-card bg-light rounded-3 p-4 mb-3">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-building me-2"></i>Informações Principais
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">ID do Estabelecimento</small>
                                        <strong>{{ $estabelecimento['id'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Nome/Razão Social</small>
                                        <strong>{{ $estabelecimento['first_name'] ?? $estabelecimento['name'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Nome Fantasia</small>
                                        <strong>{{ $estabelecimento['last_name'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Documento</small>
                                        <strong>{{ $estabelecimento['document'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Email</small>
                                        <strong>{{ $estabelecimento['email'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Telefone</small>
                                        <strong>{{ $estabelecimento['phone_number'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="status-card rounded-3 p-4 mb-3">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-chart-line me-2"></i>Status e Risco
                            </h6>
                            <div class="status-item mb-3">
                                <small class="text-muted d-block">Status</small>
                                @if(isset($estabelecimento['status']))
                                    @if($estabelecimento['status'] === 'APPROVED')
                                        <span class="badge badge-success fs-6">Aprovado</span>
                                    @elseif($estabelecimento['status'] === 'PENDING')
                                        <span class="badge badge-warning fs-6">Pendente</span>
                                    @elseif($estabelecimento['status'] === 'REJECTED')
                                        <span class="badge badge-danger fs-6">Rejeitado</span>
                                    @else
                                        <span class="badge badge-secondary fs-6">{{ $estabelecimento['status'] }}</span>
                                    @endif
                                @else
                                    <span class="badge badge-secondary fs-6">N/A</span>
                                @endif
                            </div>
                            <div class="status-item">
                                <small class="text-muted d-block">Nível de Risco</small>
                                @if(isset($estabelecimento['risk']))
                                    @if($estabelecimento['risk'] === 'LOW')
                                        <span class="badge badge-success fs-6">Baixo</span>
                                    @elseif($estabelecimento['risk'] === 'MEDIUM')
                                        <span class="badge badge-warning fs-6">Médio</span>
                                    @elseif($estabelecimento['risk'] === 'HIGH')
                                        <span class="badge badge-danger fs-6">Alto</span>
                                    @else
                                        <span class="badge badge-secondary fs-6">{{ $estabelecimento['risk'] }}</span>
                                    @endif
                                @else
                                    <span class="badge badge-secondary fs-6">N/A</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Endereço -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-map-marker-alt me-2"></i>Endereço
                            </h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Rua</small>
                                        <strong>{{ $estabelecimento['address']['street'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Bairro</small>
                                        <strong>{{ $estabelecimento['address']['neighborhood'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Cidade</small>
                                        <strong>{{ $estabelecimento['address']['city'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Estado</small>
                                        <strong>{{ $estabelecimento['address']['state'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <div class="info-item">
                                        <small class="text-muted d-block">CEP</small>
                                        <strong>{{ $estabelecimento['address']['zip_code'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informações Empresariais -->
                @if(isset($estabelecimento['format']) || isset($estabelecimento['revenue']) || isset($estabelecimento['gmv']))
                <div class="row">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-briefcase me-2"></i>Informações Empresariais
                            </h6>
                            <div class="row">
                                @if(isset($estabelecimento['format']))
                                <div class="col-md-4">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Tipo Societário</small>
                                        <strong>{{ $estabelecimento['format'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                                @endif
                                @if(isset($estabelecimento['revenue']))
                                <div class="col-md-4">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Receita Mensal</small>
                                        <strong>R$ {{ number_format($estabelecimento['revenue'], 2, ',', '.') ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                                @endif
                                @if(isset($estabelecimento['gmv']))
                                <div class="col-md-4">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">GMV</small>
                                        <strong>R$ {{ number_format($estabelecimento['gmv'], 2, ',', '.') ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
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
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.status-card {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border: 1px solid #dee2e6;
}

.info-item strong {
    color: #495057;
    font-size: 0.95rem;
}

.badge.fs-6 {
    font-size: 0.875rem !important;
    padding: 0.5rem 0.75rem;
}
</style>
@endsection 
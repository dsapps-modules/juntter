@extends('templates.dashboard-template')

@section('title', 'Planos Comerciais')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Cobranças', 'icon' => 'fas fa-credit-card', 'url' => route('cobranca.index')],
        ['label' => 'Planos Comerciais', 'icon' => 'fas fa-list-alt', 'url' => '#']
    ]"
/>



<!-- Header -->
<div class="row align-items-center mb-4">
    <div class="col-12 text-center">
        <h1 class="h3 mb-2 fw-bold">Plano Contratado</h1>
        <p class="text-muted mb-3">Plano comercial ativo da sua empresa</p>
    </div>
</div>

<!-- Tabela de planos -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <div class="text-center">
                    <h5 class="card-title fw-bold mb-2">
                        <i class="fas fa-building me-2 text-primary"></i>
                        Plano Comercial Ativo
                    </h5>
                    <p class="text-muted mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Informações do plano contratado pela sua empresa
                    </p>
                </div>
            </div>
            <div class="card-body p-4">
                <!-- Debug temporário -->
                
                @if(isset($planoContratado) && $planoContratado)
                    <!-- Card do Plano Contratado -->
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card mb-4 rounded-border">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-crown mr-2"></i>
                                        {{ $planoContratado['name'] ?? 'Plano Comercial' }}
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if(isset($planoContratado['description']) && $planoContratado['description'])
                                        <p class="text-muted mb-3">{{ $planoContratado['description'] }}</p>
                                    @endif
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted small">Tipo</label>
                                            <p class="mb-0">
                                                <span class="badge badge-secondary">{{ $planoContratado['type'] ?? 'N/A' }}</span>
                                            </p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted small">Modalidade</label>
                                            <p class="mb-0">
                                                @if(isset($planoContratado['modality']) && $planoContratado['modality'] === 'ONLINE')
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-globe me-1"></i>Online
                                                    </span>
                                                @else
                                                    <span class="badge badge-primary">
                                                        <i class="fas fa-store me-1"></i>Presencial
                                                    </span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted small">Antecipação</label>
                                            <p class="mb-0">
                                                @if(isset($planoContratado['allow_anticipation']) && $planoContratado['allow_anticipation'])
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check me-1"></i>Sim
                                                    </span>
                                                @else
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-times me-1"></i>Não
                                                    </span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-muted small">Status</label>
                                            <p class="mb-0">
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle me-1"></i>Ativo
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    @if(isset($planoContratado['created_at']))
                                        <hr>
                                        <div class="text-center">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Contratado em: {{ \Carbon\Carbon::parse($planoContratado['created_at'])->format('d/m/Y H:i') }}
                                            </small>
                                        </div>
                                    @endif
                                    
                                    <div class="text-center mt-3">
                                        <a href="{{ route('cobranca.plano.detalhes', $planoContratado['id']) }}" 
                                           class="btn btn-warning">
                                            <i class="fas fa-eye mr-2"></i>Ver Detalhes
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Estado vazio -->
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5 class="text-muted">Nenhum plano contratado</h5>
                        <p class="text-muted">Sua empresa ainda não possui um plano comercial ativo.</p>
                        <a href="#" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Contratar Plano
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>



@endsection




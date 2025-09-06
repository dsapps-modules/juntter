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

<!-- Alert -->
{{-- <div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info bg-info text-white border-0 rounded-3 shadow-sm">
            <i class="fas fa-info-circle me-2"></i>
            Visualize os planos comerciais disponíveis para integração com gateways configurados.
        </div>
    </div>
</div> --}}

<!-- Header -->
<div class="row align-items-center mb-4">
    <div class="col-12 text-center">
        <h1 class="h3 mb-2 fw-bold">Planos Comerciais</h1>
        <p class="text-muted mb-3">Planos disponíveis para integração com gateways</p>
    </div>
</div>

<!-- Tabela de planos -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <div class="text-center">
                    <h5 class="card-title fw-bold mb-2">
                        <i class="fas fa-list-alt me-2 text-primary"></i>
                        Lista de Planos Comerciais
                    </h5>
                    <p class="text-muted mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Clique em "Visualizar Detalhes" para ver informações completas do plano
                    </p>
                </div>
            </div>
            <div class="card-body p-4">
                @if(isset($planos['data']) && count($planos['data']) > 0)
                    <!-- Tabela Juntter Style -->
                    <div class="table-responsive">
                        <table id="planosTable" class="table table-hover table-striped">
                            <thead>
                                <tr class="table-header-juntter">
                                    <th></th>
                                    <th>ID</th>
                                    <th>Nome do Plano</th>
                                     
                                    <th>Modalidade</th>
                                    <th>Tipo</th>
                                    <th>Antecipação</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($planos['data'] as $plano)
                                    <tr>
                                        <td></td>
                                        <td>
                                            <small class="text-muted font-monospace">
                                                #{{ $plano['id'] }}
                                            </small>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $plano['name'] }}</strong>
                                                @if($plano['description'])
                                                    <br><small class="text-muted">{{ $plano['description'] }}</small>
                                                @endif
                                            </div>
                                        </td>
                                   
                                        <td>
                                            @if($plano['modality'] === 'ONLINE')
                                                <span class="badge badge-info">
                                                    <i class="fas fa-globe me-1"></i>Online
                                                </span>
                                            @else
                                                <span class="badge badge-primary">
                                                    <i class="fas fa-store me-1"></i>Presencial
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-dark">{{ $plano['type'] }}</span>
                                        </td>
                                        <td>
                                            @if($plano['allow_anticipation'])
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check me-1"></i>Sim
                                                </span>
                                            @else
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-times me-1"></i>Não
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($plano['active'])
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle me-1"></i>Ativo
                                                </span>
                                            @else
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-times-circle me-1"></i>Inativo
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ \Carbon\Carbon::parse($plano['created_at'])->format('d/m/Y H:i') }}
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('cobranca.plano.detalhes', $plano['id']) }}" 
                                                   class="btn btn-sm btn-outline-info" title="Visualizar Detalhes">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <!-- Estado vazio -->
                    <div class="text-center py-5">
                        <i class="fas fa-list-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum plano encontrado</h5>
                        <p class="text-muted">Não há planos comerciais disponíveis no momento.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>



@endsection




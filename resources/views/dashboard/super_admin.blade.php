@extends('templates.dashboard-template')

@section('title', 'Super Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card dashboard-card">
            <div class="card-body">
                <h2 class="card-title mb-4">
                    <i class="fas fa-crown text-warning me-2"></i>
                    Dashboard Super Administrador
                </h2>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-users me-2"></i>
                                    Gerenciar Usuários
                                </h5>
                                <p class="card-text">Visualizar e gerenciar todos os usuários do sistema</p>
                                <a href="#" class="btn btn-light">Acessar</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Relatórios
                                </h5>
                                <p class="card-text">Visualizar relatórios e estatísticas do sistema</p>
                                <a href="#" class="btn btn-light">Acessar</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-cog me-2"></i>
                                    Configurações
                                </h5>
                                <p class="card-text">Configurar parâmetros do sistema</p>
                                <a href="#" class="btn btn-light">Acessar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
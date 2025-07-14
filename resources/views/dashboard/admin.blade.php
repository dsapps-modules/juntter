@extends('templates.dashboard-template')

@section('title', 'Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card dashboard-card">
            <div class="card-body">
                <h2 class="card-title mb-4">
                    <i class="fas fa-user-shield text-primary me-2"></i>
                    Dashboard Administrador
                </h2>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-store me-2"></i>
                                    Gerenciar Vendedores
                                </h5>
                                <p class="card-text">Visualizar e gerenciar vendedores</p>
                                <a href="#" class="btn btn-dark">Acessar</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    Pedidos
                                </h5>
                                <p class="card-text">Gerenciar pedidos e vendas</p>
                                <a href="#" class="btn btn-light">Acessar</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-secondary text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Estatísticas
                                </h5>
                                <p class="card-text">Visualizar estatísticas de vendas</p>
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










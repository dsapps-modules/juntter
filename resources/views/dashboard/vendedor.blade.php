@extends('templates.dashboard-template')

@section('title', 'Vendedor')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card dashboard-card">
            <div class="card-body">
                <h2 class="card-title mb-4">
                    <i class="fas fa-store text-success me-2"></i>
                    Dashboard Vendedor
                </h2>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-plus me-2"></i>
                                    Novo Produto
                                </h5>
                                <p class="card-text">Cadastrar novos produtos</p>
                                <a href="#" class="btn btn-light">Acessar</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-box me-2"></i>
                                    Meus Produtos
                                </h5>
                                <p class="card-text">Gerenciar produtos cadastrados</p>
                                <a href="#" class="btn btn-light">Acessar</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-shopping-bag me-2"></i>
                                    Vendas
                                </h5>
                                <p class="card-text">Visualizar histórico de vendas</p>
                                <a href="#" class="btn btn-dark">Acessar</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
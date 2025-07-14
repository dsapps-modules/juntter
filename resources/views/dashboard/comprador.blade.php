@extends('templates.dashboard-template')

@section('title', 'Comprador')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card dashboard-card">
            <div class="card-body">
                <h2 class="card-title mb-4">
                    <i class="fas fa-shopping-cart text-info me-2"></i>
                    Dashboard Comprador
                </h2>
                
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-search me-2"></i>
                                    Buscar Produtos
                                </h5>
                                <p class="card-text">Encontrar produtos disponíveis</p>
                                <a href="#" class="btn btn-light">Acessar</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-heart me-2"></i>
                                    Favoritos
                                </h5>
                                <p class="card-text">Produtos salvos como favoritos</p>
                                <a href="#" class="btn btn-dark">Acessar</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-history me-2"></i>
                                    Histórico
                                </h5>
                                <p class="card-text">Histórico de compras</p>
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
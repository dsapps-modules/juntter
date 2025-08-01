@extends('templates.dashboard-template')

@section('title', 'Saldo e Extrato')

@section('content')
<!-- Seção Extrato -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Extrato</h5>
                
                <!-- Cards de Saldo -->
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="saldo-card saldo-disponivel">
                            <div class="saldo-content">
                                <div class="saldo-valor">R$ 180,50</div>
                                <div class="saldo-label">Saldo disponível</div>
                            </div>
                            <div class="saldo-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="saldo-card saldo-bloqueado-boleto">
                            <div class="saldo-content">
                                <div class="saldo-valor">R$ 00,00</div>
                                <div class="saldo-label">Saldo bloqueado boleto</div>
                            </div>
                            <div class="saldo-icon">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="saldo-card saldo-bloqueado">
                            <div class="saldo-content">
                                <div class="saldo-valor">R$ 50,50</div>
                                <div class="saldo-label">Saldo bloqueado cartão</div>
                            </div>
                            <div class="saldo-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="saldo-card saldo-transito">
                            <div class="saldo-content">
                                <div class="saldo-valor">R$ 00,00</div>
                                <div class="saldo-label">Saldo em trânsito</div>
                            </div>
                            <div class="saldo-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Seção Filtrar por período -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Filtrar por período:</h5>
                
                <div class="row align-items-end">
                    <div class="col-md-4 mb-3">
                        <label for="dataInicio" class="form-label fw-bold">início:</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="dataInicio">
                            <span class="input-group-text bg-white border-start-0">
                                <i class="fas fa-calendar text-muted"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="dataTermino" class="form-label fw-bold">Término:</label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="dataTermino">
                            <span class="input-group-text bg-white border-start-0">
                                <i class="fas fa-calendar text-muted"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <button class="btn btn-warning text-white w-100" id="btnBuscarExtrato">
                            <i class="fas fa-search me-2"></i>
                            Buscar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Scripts consolidados no dashboard.js -->
@endsection 
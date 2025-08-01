@extends('templates.dashboard-template')

@section('title', 'Planos de Cobrança')

@section('content')
<!-- Breadcrumb -->
<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent p-0 mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-primary text-decoration-none">Juntter</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="#" class="text-primary text-decoration-none">Cobrança</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Planos de Cobrança</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Alert -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning bg-warning text-white border-0 rounded-3 shadow-sm">
            <i class="fas fa-list-alt me-2"></i>
            Gerencie seus planos de cobrança recorrente.
        </div>
    </div>
</div>

<!-- Header com botão -->
<div class="row align-items-center mb-4">
    <div class="col-12 text-center">
        <h1 class="h3 mb-2 fw-bold">Planos de Cobrança</h1>
        <p class="text-muted mb-3">Configure e gerencie seus planos de cobrança</p>
        <button class="btn btn-novo-pagamento shadow-sm" 
                data-bs-toggle="modal" 
                data-bs-target="#modalPlano">
            <i class="fas fa-plus me-2"></i>
            Novo Plano
        </button>
    </div>
</div>

<!-- Tabela de planos -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <!-- Tabela Juntter Style -->
                <div class="table-responsive">
                    <table id="planosTable" class="table table-hover table-striped">
                        <thead>
                            <tr class="table-header-juntter">
                                <th>Nome do Plano</th>
                                <th>Valor</th>
                                <th>Periodicidade</th>
                                <th>Duração</th>
                                <th>Status</th>
                                <th>Clientes</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Plano Básico</strong></td>
                                <td><strong class="text-success">R$ 99,00</strong></td>
                                <td><span class="badge badge-info">Mensal</span></td>
                                <td><span class="text-muted">12 meses</span></td>
                                <td><span class="badge badge-success">Ativo</span></td>
                                <td><span class="text-muted">15 clientes</span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Plano Premium</strong></td>
                                <td><strong class="text-warning">R$ 199,00</strong></td>
                                <td><span class="badge badge-info">Mensal</span></td>
                                <td><span class="text-muted">Indefinido</span></td>
                                <td><span class="badge badge-success">Ativo</span></td>
                                <td><span class="text-muted">8 clientes</span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Plano Trimestral</strong></td>
                                <td><strong class="text-danger">R$ 250,00</strong></td>
                                <td><span class="badge badge-info">Trimestral</span></td>
                                <td><span class="text-muted">4 meses</span></td>
                                <td><span class="badge badge-warning">Inativo</span></td>
                                <td><span class="text-muted">3 clientes</span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Plano -->
<div class="modal fade" id="modalPlano" tabindex="-1" aria-labelledby="modalPlanoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalPlanoLabel">
                    <i class="fas fa-list-alt me-2"></i>Planos de cobrança
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formPlano">
                    <!-- Campos principais -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="nomePlano" class="form-label fw-bold">Nome</label>
                            <input type="text" class="form-control" id="nomePlano" placeholder="Nome do plano" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="numParcelas" class="form-label fw-bold">Núm. parcelas</label>
                            <select class="form-select" id="numParcelas" required>
                                <option value="indeterminado">Indeterminado</option>
                                <option value="1">1x</option>
                                <option value="2">2x</option>
                                <option value="3">3x</option>
                                <option value="6">6x</option>
                                <option value="12">12x</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="periodicidadePlano" class="form-label fw-bold">Periodicidade</label>
                            <select class="form-select" id="periodicidadePlano" required>
                                <option value="mensal">Mensal</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="semestral">Semestral</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                    </div>

                    <!-- Métodos de pagamento -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="fw-bold mb-3">Métodos de Pagamento</h6>
                        </div>
                        
                        <!-- Boleto -->
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="checkbox" id="boletoAtivo" checked>
                                    <label class="form-check-label fw-bold" for="boletoAtivo">
                                        Boleto
                                    </label>
                                </div>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="precoBoleto" placeholder="0,00">
                            </div>
                        </div>

                        <!-- Crédito -->
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="checkbox" id="creditoAtivo" checked>
                                    <label class="form-check-label fw-bold" for="creditoAtivo">
                                        Crédito
                                    </label>
                                </div>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="precoCredito" placeholder="0,00">
                            </div>
                        </div>

                        <!-- Limite de parcelas para crédito -->
                        <div class="col-md-6 mb-3">
                            <label for="limiteParcelas" class="form-label fw-bold">Limite máximo de parcelas permitido</label>
                            <select class="form-select" id="limiteParcelas">
                                <option value="avista">à vista</option>
                                <option value="2x">2x</option>
                                <option value="3x">3x</option>
                                <option value="6x">6x</option>
                                <option value="12x">12x</option>
                            </select>
                        </div>
                    </div>

                    <!-- Mais informações -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="fw-bold text-uppercase small text-muted mb-3">MAIS INFORMAÇÕES</h6>
                            <div class="mb-3">
                                <label for="observacoesPlano" class="form-label fw-bold">Observações</label>
                                <textarea class="form-control" id="observacoesPlano" rows="3" placeholder="Observações sobre o plano..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    Salvar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Scripts consolidados no dashboard.js -->
@endsection 
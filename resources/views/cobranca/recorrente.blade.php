@extends('templates.dashboard-template')

@section('title', 'Cobrança Recorrente')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Cobranças', 'icon' => 'fas fa-credit-card', 'url' => route('cobranca.index')],
        ['label' => 'Recorrente', 'icon' => 'fas fa-sync-alt', 'url' => '#']
    ]"
/>

<!-- Alert -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning bg-warning text-white border-0 rounded-3 shadow-sm">
            <i class="fas fa-sync-alt me-2"></i>
            Faça cobranças recorrentes no cartão de crédito de seus clientes.
        </div>
    </div>
</div>

<!-- Header com botão -->
<div class="row align-items-center mb-4">
    <div class="col-12 text-center">
        <h1 class="h3 mb-2 fw-bold">Cobrança Recorrente</h1>
        <p class="text-muted mb-3">Gerencie suas cobranças recorrentes</p>
        <button class="btn btn-novo-pagamento shadow-sm" 
                data-bs-toggle="modal" 
                data-bs-target="#modalCobrancaRecorrente">
            <i class="fas fa-plus me-2"></i>
            Novo Pagamento Recorrente
        </button>
    </div>
</div>

<!-- Tabela de cobranças recorrentes -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <!-- Tabela Juntter Style -->
                <div class="table-responsive">
                    <table id="cobrancasRecorrentesTable" class="table table-hover table-striped">
                        <thead>
                            <tr class="table-header-juntter">
                                <th>Cliente</th>
                                <th>Valor</th>
                                <th>Periodicidade</th>
                                <th>Parcelas</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>João Silva</strong></td>
                                <td><strong class="text-success">R$ 150,00</strong></td>
                                <td><span class="badge badge-info">Mensal</span></td>
                                <td><span class="text-muted">12x</span></td>
                                <td><span class="text-muted">15/01/2024</span></td>
                                <td><span class="badge badge-success">Ativo</span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Maria Santos</strong></td>
                                <td><strong class="text-warning">R$ 200,00</strong></td>
                                <td><span class="badge badge-info">Mensal</span></td>
                                <td><span class="text-muted">6x</span></td>
                                <td><span class="text-muted">20/01/2024</span></td>
                                <td><span class="badge badge-warning">Pendente</span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Pedro Oliveira</strong></td>
                                <td><strong class="text-danger">R$ 300,00</strong></td>
                                <td><span class="badge badge-info">Trimestral</span></td>
                                <td><span class="text-muted">4x</span></td>
                                <td><span class="text-muted">10/02/2024</span></td>
                                <td><span class="badge badge-danger">Cancelado</span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
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

<!-- Modal Cobrança Recorrente -->
<div class="modal fade" id="modalCobrancaRecorrente" tabindex="-1" aria-labelledby="modalCobrancaRecorrenteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalCobrancaRecorrenteLabel">
                    <i class="fas fa-sync-alt me-2"></i>Cobrança recorrente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                 <form id="formCobrancaRecorrente">
                     <div class="row">
                         <!-- Coluna Esquerda -->
                         <div class="col-md-6">
                             <div class="mb-3">
                                 <label for="clienteRecorrente" class="form-label fw-bold">Buscar cliente</label>
                                 <select class="form-select" id="clienteRecorrente" required>
                                     <option value="">Selecione o Paciente</option>
                                     <option value="1">João Silva</option>
                                     <option value="2">Maria Santos</option>
                                     <option value="3">Pedro Oliveira</option>
                                 </select>
                             </div>

                             <div class="mb-3">
                                 <label for="valorParcela" class="form-label fw-bold">Valor da parcela</label>
                                 <input type="text" class="form-control" id="valorParcela" placeholder="R$ 0,00" required>
                             </div>

                             <div class="mb-3">
                                 <label for="observacoesRecorrente" class="form-label fw-bold">Observações</label>
                                 <textarea class="form-control" id="observacoesRecorrente" rows="3" placeholder="Observações sobre a cobrança recorrente..."></textarea>
                             </div>
                         </div>

                         <!-- Coluna Direita -->
                         <div class="col-md-6">
                             <div class="mb-3">
                                 <label for="planosRecorrente" class="form-label fw-bold">Planos</label>
                                 <select class="form-select" id="planosRecorrente" required>
                                     <option value="">Selecione um plano</option>
                                     <option value="mensal">Plano Mensal</option>
                                     <option value="trimestral">Plano Trimestral</option>
                                     <option value="semestral">Plano Semestral</option>
                                     <option value="anual">Plano Anual</option>
                                 </select>
                             </div>

                             <div class="mb-3">
                                 <label for="vencimentoRecorrente" class="form-label fw-bold">Vencimento (cobrar em)</label>
                                 <div class="input-group">
                                     <input type="date" class="form-control" id="vencimentoRecorrente" required>
                                     <span class="input-group-text">
                                         <i class="fas fa-calendar"></i>
                                     </span>
                                 </div>
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


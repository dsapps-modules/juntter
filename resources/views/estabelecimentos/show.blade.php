@extends('templates.dashboard-template')

@section('title', 'Detalhes do Estabelecimento')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Estabelecimentos', 'icon' => 'fas fa-building', 'url' => route('estabelecimentos.show', $estabelecimento['id'])],
        ['label' => 'Detalhes', 'icon' => 'fas fa-eye', 'url' => '#']
    ]"
/>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
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
                            <i class="fas fa-home me-2"></i>
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

                <!-- Gerenciamento de Split Pré -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 text-primary">
                                    <i class="fas fa-share-alt me-2"></i>Regras de Split 
                                </h6>
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovaRegra">
                                    <i class="fas fa-plus me-2 mr-2"></i>Nova Regra
                                </button>
                            </div>

                                                         <div class="table-responsive">
                                 <table id="regrasSplitTable" class="table table-hover table-striped">
                                     <thead>
                                         <tr class="table-header-juntter">
                                             <th></th>
                                             <th>Título</th>
                                             <th>Modalidade</th>
                                             <th>Canal</th>
                                             <th>Divisão</th>
                                             <th>Parcelas</th>
                                             <th>Status</th>
                                             <th>Ações</th>
                                         </tr>
                                     </thead>
                                                                           <tbody>
                                          @if(isset($regrasSplit['data']) && count($regrasSplit['data']) > 0)
                                              @foreach($regrasSplit['data'] as $regra)
                                                  <tr>
                                                      <td></td>
                                                      <td><strong>{{ $regra['title'] }}</strong></td>
                                                      <td>
                                                          <span class="badge bg-info">{{ $regra['modality'] }}</span>
                                                      </td>
                                                      <td>
                                                          <span class="badge bg-secondary">{{ $regra['channel'] }}</span>
                                                      </td>
                                                      <td>
                                                          <span class="badge bg-warning">{{ $regra['division'] }}</span>
                                                      </td>
                                                      <td>
                                                          @if(isset($regra['installment']))
                                                              <span class="badge bg-light text-dark">{{ $regra['installment'] }}x</span>
                                                          @else
                                                              <span class="badge bg-light text-dark">À vista</span>
                                                          @endif
                                                      </td>
                                                      <td>
                                                          @if($regra['active'])
                                                              <span class="badge bg-success">
                                                                  <i class="fas fa-check-circle me-1"></i>Ativa
                                                              </span>
                                                          @else
                                                              <span class="badge bg-secondary">
                                                                  <i class="fas fa-times-circle me-1"></i>Inativa
                                                              </span>
                                                          @endif
                                                      </td>
                                                      <td>
                                                          <div class="btn-group" role="group">
                                                              <a href="{{ route('estabelecimentos.split-pre.show', ['id' => $estabelecimento['id'], 'splitId' => $regra['id']]) }}" 
                                                                 class="btn btn-sm btn-outline-info mr-1" 
                                                                 title="Visualizar detalhes">
                                                                  <i class="fas fa-eye"></i>
                                                              </a>
                                                              <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                      data-bs-toggle="modal" 
                                                                      data-bs-target="#modalDeletarRegra{{ $regra['id'] }}"
                                                                      title="Excluir regra">
                                                                  <i class="fas fa-trash"></i>
                                                              </button>
                                                          </div>
                                                      </td>
                                                  </tr>
                                              @endforeach
                                          @else
                                              <tr>
                                                  <td colspan="8" class="text-center py-4">
                                                      <i class="fas fa-info-circle text-muted"></i>
                                                      <p class="text-muted mt-2 mb-0">Nenhuma regra de split encontrada</p>
                                                  </td>
                                              </tr>
                                          @endif
                                      </tbody>
                                 </table>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

 <!-- Modais de Confirmação de Exclusão -->
 @if(isset($regrasSplit['data']) && count($regrasSplit['data']) > 0)
     @foreach($regrasSplit['data'] as $regra)

         
         <!-- Modal Deletar Regra -->
         <div class="modal fade" id="modalDeletarRegra{{ $regra['id'] }}" tabindex="-1" aria-labelledby="modalDeletarRegraLabel{{ $regra['id'] }}" aria-hidden="true">
             <div class="modal-dialog modal-sm">
                 <div class="modal-content">
                     <div class="modal-header bg-danger text-white">
                         <h5 class="modal-title fw-bold" id="modalDeletarRegraLabel{{ $regra['id'] }}">
                             <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão
                         </h5>
                         <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                     </div>
                     <div class="modal-body">
                         <p class="mb-0">
                             <strong>Tem certeza que deseja excluir a regra:</strong><br>
                             <span class="text-primary fw-bold">"{{ $regra['title'] }}"</span>?
                         </p>
                         <small class="text-muted d-block mt-2">
                             Esta ação não pode ser desfeita.
                         </small>
                     </div>
                     <div class="modal-footer bg-light">
                         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                             <i class="fas fa-times me-2"></i>Cancelar
                         </button>
                         <form method="POST" action="{{ route('estabelecimentos.split-pre.destroy', ['id' => $estabelecimento['id'], 'splitId' => $regra['id']]) }}" style="display: inline;">
                             @csrf
                             @method('DELETE')
                             <button type="submit" class="btn btn-danger">
                                 <i class="fas fa-trash me-2"></i>Excluir Regra
                             </button>
                         </form>
                     </div>
                 </div>
             </div>
         </div>
     @endforeach
 @endif

<!-- Modal Nova Regra de Split -->
<div class="modal fade" id="modalNovaRegra" tabindex="-1" aria-labelledby="modalNovaRegraLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold"  id="modalNovaRegraLabel">
                    <i class="fas fa-share-alt me-2"></i>Nova Regra de Split Pré
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('estabelecimentos.split-pre.store', $estabelecimento['id']) }}">
                @csrf
                <div class="modal-body">
                    <!-- Informações da Regra -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold">
                                <i class="fas fa-cog me-2"></i>Configurações da Regra
                            </h6>
                            <small class="text-muted d-block">Configure os parâmetros básicos da regra de split</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="title" class="form-label fw-bold">
                                            Título da Regra <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="title" name="title"
                                            placeholder="Ex: Comissão de Vendas Online" required>
                                        <small class="text-muted d-block">Nome descritivo para identificar a regra</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="modality" class="form-label fw-bold">
                                            Modalidade <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="modality" name="modality" required>
                                            <option value="">Selecione a modalidade...</option>
                                            <option value="ALL">Todas as Modalidades</option>
                                            <option value="CREDIT">Apenas Crédito</option>
                                            <option value="DEBIT">Apenas Débito</option>
                                            <option value="PIX">Apenas PIX</option>
                                        </select>
                                        <small class="text-muted d-block">Define quais tipos de transação a regra se aplica</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="channel" class="form-label fw-bold">
                                            Canal de Venda <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="channel" name="channel" required>
                                            <option value="">Selecione o canal...</option>
                                            <option value="ALL">Todos os Canais</option>
                                            <option value="CHIP">Apenas Chip</option>
                                            <option value="TAP">Apenas Tap</option>
                                            <option value="SMART">Apenas Smart</option>
                                            <option value="ONLINE">Apenas Online</option>
                                        </select>
                                        <small class="text-muted d-block">Define em quais canais a regra será aplicada</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="division" class="form-label fw-bold">
                                            Tipo de Divisão <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="division" name="division" required>
                                            <option value="">Selecione o tipo...</option>
                                            <option value="PERCENTAGE">Porcentagem (%)</option>
                                            <option value="CURRENCY">Valor Fixo (R$)</option>
                                        </select>
                                        <small class="text-muted d-block">Como o valor será dividido entre os participantes</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="installment" class="form-label fw-bold">
                                            Número de Parcelas
                                        </label>
                                        <input type="number" class="form-control" id="installment" name="installment"
                                            min="1" max="12" placeholder="Ex: 3 para parcelado">
                                        <small class="text-muted d-block">Opcional. Deixe em branco para vendas à vista</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                                            <label class="form-check-label fw-bold" for="active">
                                                Regra Ativa
                                            </label>
                                            <small class="text-muted d-block">A regra será aplicada automaticamente</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estabelecimentos Participantes -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0 fw-bold">
                                <i class="fas fa-user me-2"></i>Estabelecimento Participante
                            </h6>
                            <small class="text-muted d-block">Configure o estabelecimento secundário que receberá parte do valor</small>
                        </div>
                        <div class="card-body">
                            <div id="establishments-container">
                                <div class="establishment-item border rounded p-3 mb-3 bg-light">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">
                                                Estabelecimento <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select establishment-select" name="establishments[0][id]" required>
                                                <option value="">Selecione um estabelecimento...</option>
                                                @if(isset($estabelecimentos['data']))
                                                @foreach($estabelecimentos['data'] as $estab)
                                                @if($estab['id'] != $estabelecimento['id'])
                                                <option value="{{ $estab['id'] }}">
                                                    {{ $estab['first_name'] ?? $estab['name'] }} ({{ $estab['id'] }})
                                                </option>
                                                @endif
                                                @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold">
                                                Valor <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" class="form-control" name="establishments[0][value]"
                                                min="1" max="100" placeholder="Ex: 30" required>
                                            <small class="text-muted d-block">% ou valor fixo conforme o tipo de divisão</small>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" name="establishments[0][active]" checked>
                                                <label class="form-check-label fw-bold">Ativo</label>
                                                <small class="text-muted d-block">Participante ativo na regra</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Criar Regra de Split
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .info-card {
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .info-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .status-card {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 1px solid #dee2e6;
    }

    .info-item strong {
        font-size: 0.95rem;
    }

    .badge.fs-6 {
        font-size: 0.875rem !important;
        padding: 0.5rem 0.75rem;
    }

    .split-rule-card {
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .split-rule-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .establishment-item {
        background-color: #f8f9fa;
        transition: all 0.3s ease;
    }

     .establishment-item:hover {
     background-color: #e9ecef;
 }
 
 /* Garantir que campos não saiam para fora */
 .form-control, .form-select {
     max-width: 100%;
     box-sizing: border-box;
 }
 
 .establishment-item .row {
     margin: 0;
 }
 
 .establishment-item .col-md-6,
 .establishment-item .col-md-3 {
     padding: 0 10px;
 }
 

 </style>
 


@endsection
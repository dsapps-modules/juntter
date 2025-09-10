@extends('templates.dashboard-template')

@section('title', 'Detalhes do Link de Pagamento Boleto')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Links de Pagamento Boleto', 'icon' => 'fas fa-file-invoice', 'url' => route('links-pagamento-boleto.index')],
        ['label' => 'Detalhes', 'icon' => 'fas fa-eye', 'url' => '#']
    ]"
/>

            <!-- Session Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <!-- Header com ações -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-file-invoice text-warning mr-2"></i>
                        Link de Pagamento Boleto
                    </h2>
                    <p class="text-muted mb-0">{{ $linkPagamento->descricao ?: 'Sem descrição' }}</p>
                </div>
                <div class="d-flex">
                    <a href="{{ route('links-pagamento-boleto.edit', $linkPagamento->id) }}" class="btn btn-warning mr-2">
                        <i class="fas fa-edit mr-2"></i>Editar
                    </a>
                    <a href="{{ route('links-pagamento-boleto.index') }}" class="btn btn-secondary">
                        <i class="fas fa-home mr-2"></i>
                    </a>
                </div>
            </div>

            <!-- Cards de Informações -->
            <div class="row">
                <!-- Informações Básicas -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                Informações Básicas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Valor</label>
                                        <div class="h4 text-success">{{ $linkPagamento->valor_formatado }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Status</label>
                                        <div>
                                            @switch($linkPagamento->status)
                                                @case('ATIVO')
                                                    <span class="badge bg-success fs-6">Ativo</span>
                                                    @break
                                                @case('INATIVO')
                                                    <span class="badge bg-secondary fs-6">Inativo</span>
                                                    @break
                                                @case('EXPIRADO')
                                                    <span class="badge bg-warning fs-6">Expirado</span>
                                                    @break
                                                @case('PAID')
                                                    <span class="badge bg-info fs-6">Pago</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary fs-6">{{ $linkPagamento->status }}</span>
                                            @endswitch
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Data de Vencimento</label>
                                        <div>
                                            @if($linkPagamento->data_vencimento)
                                                <span class="badge bg-info">{{ $linkPagamento->data_vencimento->format('d/m/Y') }}</span>
                                            @else
                                                <span class="text-muted">Não definida</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Tipo de Pagamento</label>
                                        <div>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-file-invoice me-1"></i>Boleto
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Quem paga as taxas</label>
                                        <div>
                                            @if($linkPagamento->juros === 'CLIENT')
                                                <span class="badge bg-info">Cliente</span>
                                            @else
                                                <span class="badge bg-warning">Estabelecimento</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Data limite para pagamento</label>
                                        <div>
                                            @if($linkPagamento->data_limite_pagamento)
                                                <span class="badge bg-secondary">{{ $linkPagamento->data_limite_pagamento->format('d/m/Y') }}</span>
                                            @else
                                                <span class="text-muted">Não definida</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if($linkPagamento->descricao)
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Descrição</label>
                                <div>{{ $linkPagamento->descricao }}</div>
                            </div>
                            @endif
                            @if($linkPagamento->data_expiracao)
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">Data de Expiração do Link</label>
                                <div>{{ $linkPagamento->data_expiracao->format('d/m/Y H:i') }}</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Instruções do Boleto -->
                    @if($linkPagamento->instrucoes_boleto)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt mr-2"></i>
                                Instruções do Boleto
                            </h5>
                        </div>
                        <div class="card-body">
                            @php $instrucoes = $linkPagamento->instrucoes_boleto; @endphp
                            <div class="row">
                                @if(isset($instrucoes['descricao']))
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Descrição</label>
                                        <div>{{ $instrucoes['descricao'] }}</div>
                                    </div>
                                </div>
                                @endif
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">É carnê?</label>
                                        <div>
                                            @if($instrucoes['carne'] ?? false)
                                                <span class="badge bg-primary">Sim</span>
                                            @else
                                                <span class="badge bg-secondary">Não</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Multa por atraso</label>
                                        <div>{{ number_format($instrucoes['multa'] ?? 0, 2, ',', '.') }}%</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Juros ao mês</label>
                                        <div>{{ number_format($instrucoes['juros'] ?? 0, 2, ',', '.') }}%</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Desconto</label>
                                        <div>{{ number_format($instrucoes['desconto'] ?? 0, 2, ',', '.') }}%</div>
                                    </div>
                                </div>
                            </div>
                            @if(isset($instrucoes['data_limite_desconto']))
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Data limite para desconto</label>
                                        <div>{{ \Carbon\Carbon::parse($instrucoes['data_limite_desconto'])->format('d/m/Y') }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Link de Pagamento -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-link mr-2"></i>
                                Link de Pagamento
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted">URL do Link</label>
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control" 
                                           value="{{ $linkPagamento->url_completa }}" 
                                           readonly 
                                           id="linkUrl">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            onclick="copiarLink()">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Compartilhe este link com seus clientes para receber pagamentos via boleto</small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ $linkPagamento->url_completa }}" 
                                   target="_blank" 
                                   class="btn btn-success">
                                    <i class="fas fa-external-link-alt mr-2"></i>
                                    Testar Link
                                </a>
                                <button class="btn btn-outline-primary" onclick="copiarLink()">
                                    <i class="fas fa-copy mr-2"></i>
                                    Copiar Link
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Dados do Cliente -->
                    @if($linkPagamento->dados_cliente && $linkPagamento->dados_cliente['preenchidos'])
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user mr-2"></i>
                                Dados do Cliente
                            </h5>
                        </div>
                        <div class="card-body">
                            @php $cliente = $linkPagamento->dados_cliente['preenchidos']; @endphp
                            <div class="row">
                                @if(isset($cliente['nome']) || isset($cliente['sobrenome']))
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Nome</label>
                                        <div>{{ ($cliente['nome'] ?? '') . ' ' . ($cliente['sobrenome'] ?? '') }}</div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($cliente['email']))
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Email</label>
                                        <div>{{ $cliente['email'] }}</div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="row">
                                @if(isset($cliente['telefone']))
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">Telefone</label>
                                        <div>{{ $cliente['telefone'] }}</div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($cliente['documento']))
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-muted">CPF/CNPJ</label>
                                        <div>{{ $cliente['documento'] }}</div>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @if(isset($cliente['endereco']))
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="fw-bold text-muted mb-2">Endereço</h6>
                                    <div class="text-muted">
                                        {{ $cliente['endereco']['rua'] ?? '' }} 
                                        {{ $cliente['endereco']['numero'] ?? '' }}
                                        {{ $cliente['endereco']['complemento'] ? ', ' . $cliente['endereco']['complemento'] : '' }}<br>
                                        {{ $cliente['endereco']['bairro'] ?? '' }} - 
                                        {{ $cliente['endereco']['cidade'] ?? '' }}/{{ $cliente['endereco']['estado'] ?? '' }}<br>
                                        CEP: {{ $cliente['endereco']['cep'] ?? '' }}
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Estatísticas -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Estatísticas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <div class="h3 text-primary mb-1">0</div>
                                <div class="text-muted">Boletos Emitidos</div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <div class="h3 text-success mb-1">R$ 0,00</div>
                                <div class="text-muted">Total Arrecadado</div>
                            </div>
                        </div>
                    </div>

                    <!-- Ações Rápidas -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt mr-2"></i>
                                Ações Rápidas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm toggle-status" 
                                        data-link-id="{{ $linkPagamento->id }}"
                                        data-current-status="{{ $linkPagamento->status }}">
                                    @if($linkPagamento->status === 'ATIVO')
                                        <i class="fas fa-pause mr-2"></i>Desativar Link
                                    @else
                                        <i class="fas fa-play mr-2"></i>Ativar Link
                                    @endif
                                </button>
                                <a href="{{ route('links-pagamento-boleto.edit', $linkPagamento->id) }}" 
                                   class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-edit mr-2"></i>Editar Link
                                </a>
                                <button class="btn btn-outline-danger btn-sm delete-link" 
                                        data-link-id="{{ $linkPagamento->id }}">
                                    <i class="fas fa-trash mr-2"></i>Excluir Link
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Informações Técnicas -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-cog mr-2"></i>
                                Informações Técnicas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold text-muted small">ID do Link</label>
                                <div class="small font-monospace">{{ $linkPagamento->id }}</div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-muted small">Código Único</label>
                                <div class="small font-monospace">{{ $linkPagamento->codigo_unico }}</div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-muted small">Criado em</label>
                                <div class="small">{{ $linkPagamento->created_at->format('d/m/Y H:i:s') }}</div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-muted small">Atualizado em</label>
                                <div class="small">{{ $linkPagamento->updated_at->format('d/m/Y H:i:s') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação para Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este link de pagamento boleto?</p>
                <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copiarLink() {
    const linkInput = document.getElementById('linkUrl');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Mostrar feedback
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check mr-2"></i>Copiado!';
    button.classList.remove('btn-outline-secondary', 'btn-outline-primary');
    button.classList.add('btn-success');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}

$(document).ready(function() {
    // Toggle status do link
    $('.toggle-status').click(function() {
        const linkId = $(this).data('link-id');
        const currentStatus = $(this).data('current-status');
        const newStatus = currentStatus === 'ATIVO' ? 'INATIVO' : 'ATIVO';
        
        $.ajax({
            url: `/links-pagamento-boleto/${linkId}/status`,
            method: 'PATCH',
            data: {
                status: newStatus,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Erro ao alterar status: ' + response.error);
                }
            },
            error: function() {
                alert('Erro ao alterar status do link');
            }
        });
    });

    // Exclusão de link
    $('.delete-link').click(function() {
        const linkId = $(this).data('link-id');
        
        $('#deleteForm').attr('action', `/links-pagamento-boleto/${linkId}`);
        $('#deleteModal').modal('show');
    });
});
</script>
@endpush

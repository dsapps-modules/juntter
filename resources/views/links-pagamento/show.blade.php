@extends('templates.dashboard-template')

@section('title', 'Detalhes do Link de Pagamento')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Links de Pagamento', 'icon' => 'fas fa-link', 'url' => route('links-pagamento.index')],
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
                        <i class="fas fa-link text-primary mr-2"></i>
                        Link de Pagamento
                    </h2>
                    <p class="text-muted mb-0">{{ $linkPagamento->descricao ?: 'Sem descrição' }}</p>
                </div>
                <div class="d-flex">
                    <a href="{{ route('links-pagamento.edit', $linkPagamento->id) }}" class="btn btn-warning mr-2">
                        <i class="fas fa-edit mr-2"></i>Editar
                    </a>
                    <a href="{{ route('links-pagamento.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Coluna Única -->
                <div class="col-12">
                    <!-- Card de Informações Básicas -->
                    <div class="card mb-4 rounded-border" style="height: 320px;">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                Informações do Link
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Valor</label>
                                    <p class="mb-0">
                                        <span class="badge badge-success" style="font-size: 1rem;">{{ $linkPagamento->valor_formatado }}</span>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Status</label>
                                    <p class="mb-0">
                                        @switch($linkPagamento->status)
                                            @case('ATIVO')
                                                <span class="badge badge-success">Ativo</span>
                                                @break
                                            @case('INATIVO')
                                                <span class="badge badge-secondary">Inativo</span>
                                                @break
                                            @case('EXPIRADO')
                                                <span class="badge badge-warning">Expirado</span>
                                                @break
                                            @case('PAID')
                                                <span class="badge badge-info">Pago</span>
                                                @break
                                            @default
                                                <span class="badge badge-secondary">{{ $linkPagamento->status }}</span>
                                        @endswitch
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Código Único</label>
                                    <p class="mb-0">
                                        <code class="bg-light px-2 py-1 rounded">{{ $linkPagamento->codigo_unico }}</code>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Criado em</label>
                                    <p class="mb-0">{{ $linkPagamento->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                                @if($linkPagamento->data_expiracao)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Expira em</label>
                                    <p class="mb-0">{{ $linkPagamento->data_expiracao->format('d/m/Y H:i') }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                        <!-- Card do Link de Pagamento -->
                    <div class="card mb-4 rounded-border">
                        <div class="card-header text-center">
                            <h5 class="mb-0">
                                <i class="fas fa-external-link-alt mr-2"></i>
                                Link de Pagamento
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-link text-primary fs-1"></i>
                            </div>
                            <p class="text-muted small mb-3">Compartilhe este link com seus clientes</p>
                            
                            <div class="input-group no-wrap mb-3">
                                <input type="text" class="form-control" id="linkInput" value="{{ $linkPagamento->url_completa }}" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-primary" type="button" onclick="copiarLink()">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-wrap">
                                <a href="{{ $linkPagamento->url_completa }}" target="_blank" class="btn btn-primary mr-3">
                                    <i class="fas fa-external-link-alt mr-2"></i>Testar Link
                                </a>
                                
                                @if($linkPagamento->status === 'ATIVO')
                                    <button type="button" class="btn btn-outline-warning mr-3" onclick="alterarStatus('INATIVO')">
                                        <i class="fas fa-pause mr-2"></i>Desativar
                                    </button>
                                @else
                                    <button type="button" class="btn btn-outline-success mr-3" onclick="alterarStatus('ATIVO')">
                                        <i class="fas fa-play mr-2"></i>Ativar
                                    </button>
                                @endif
                                
                                <button type="button" class="btn btn-outline-danger" onclick="confirmarExclusao()">
                                    <i class="fas fa-trash mr-2"></i>Excluir
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Card de Configurações -->
                    <div class="card mb-4 rounded-border">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-cog mr-2"></i>
                                Configurações de Pagamento
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Taxas</label>
                                    <p class="mb-0">
                                        @if($linkPagamento->juros == 'CLIENT')
                                            <span class="text-info">
                                                <i class="fas fa-user mr-1"></i>Cliente paga as taxas
                                            </span>
                                        @else
                                            <span class="text-success">
                                                <i class="fas fa-building mr-1"></i>Estabelecimento paga as taxas
                                            </span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Parcelamento</label>
                                    <p class="mb-0">
                                        @if(empty($linkPagamento->parcelas) || $linkPagamento->parcelas == 1)
                                            <span class="badge badge-secondary">Apenas à vista</span>
                                        @else
                                            <span class="badge badge-primary">Até {{ $linkPagamento->parcelas }}x</span>
                                        @endif
                                    </p>
                                </div>
                          
                            </div>
                        </div>
                    </div>

                
                    <div class="card mb-4 rounded-border">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user mr-2"></i>
                                Dados do Cliente
                            </h5>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div class="row">
                                @if(isset($linkPagamento->dados_cliente['preenchidos']['nome']))
                                <div class="col-md-3 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user text-primary mr-3" style="font-size: 1rem; width: 20px;"></i>
                                        <div class="flex-grow-1">
                                            <label class="form-label text-muted small mb-1">Nome</label>
                                            <div>
                                               
                                                @if(isset($linkPagamento->dados_cliente['preenchidos']['nome']) && $linkPagamento->dados_cliente['preenchidos']['nome'])
                                                    <div class="mt-1">
                                                        <small class="text-success"><i class="fas fa-check-circle"></i> Pré-preenchido: {{ $linkPagamento->dados_cliente['preenchidos']['nome'] }}</small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user text-primary mr-3" style="font-size: 1rem; width: 20px;"></i>
                                        <div class="flex-grow-1">
                                            <label class="form-label text-muted small mb-1">Sobrenome</label>
                                            <div>
                                                
                                                @if(isset($linkPagamento->dados_cliente['preenchidos']['sobrenome']) && $linkPagamento->dados_cliente['preenchidos']['sobrenome'])
                                                    <div class="mt-1">
                                                        <small class="text-success"><i class="fas fa-check-circle"></i> Pré-preenchido: {{ $linkPagamento->dados_cliente['preenchidos']['sobrenome'] }}</small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($linkPagamento->dados_cliente['preenchidos']['email']))
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-envelope text-primary mr-3" style="font-size: 1rem; width: 20px;"></i>
                                        <div class="flex-grow-1">
                                            <label class="form-label text-muted small mb-1">Email</label>
                                            <div>
                                              
                                                @if(isset($linkPagamento->dados_cliente['preenchidos']['email']) && $linkPagamento->dados_cliente['preenchidos']['email'])
                                                    <div class="mt-1">
                                                        <small class="text-success"><i class="fas fa-check-circle"></i> Pré-preenchido: {{ $linkPagamento->dados_cliente['preenchidos']['email'] }}</small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($linkPagamento->dados_cliente['preenchidos']['telefone']))
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-phone text-primary mr-3" style="font-size: 1rem; width: 20px;"></i>
                                        <div class="flex-grow-1">
                                            <label class="form-label text-muted small mb-1">Telefone</label>
                                            <div>
                                               
                                                @if(isset($linkPagamento->dados_cliente['preenchidos']['telefone']) && $linkPagamento->dados_cliente['preenchidos']['telefone'])
                                                    <div class="mt-1">
                                                        <small class="text-success"><i class="fas fa-check-circle"></i> Pré-preenchido: {{ $linkPagamento->dados_cliente['preenchidos']['telefone'] }}</small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($linkPagamento->dados_cliente['preenchidos']['documento']))
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-id-card text-primary mr-3" style="font-size: 1rem; width: 20px;"></i>
                                        <div class="flex-grow-1">
                                            <label class="form-label text-muted small mb-1">Documento</label>
                                            <div>
                                               
                                                @if(isset($linkPagamento->dados_cliente['preenchidos']['documento']) && $linkPagamento->dados_cliente['preenchidos']['documento'])
                                                    <div class="mt-1">
                                                        <small class="text-success"><i class="fas fa-check-circle"></i> Pré-preenchido: {{ $linkPagamento->dados_cliente['preenchidos']['documento'] }}</small>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if(isset($linkPagamento->dados_cliente['preenchidos']['endereco']) && is_array($linkPagamento->dados_cliente['preenchidos']['endereco']))
                                <div class="col-md-12 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-map-marker-alt text-primary mr-3" style="font-size: 1rem; width: 20px;"></i>
                                        <div class="flex-grow-1">
                                            <label class="form-label text-muted small mb-1">Endereço</label>
                                            <div>
                                               
                                                @if(isset($linkPagamento->dados_cliente['preenchidos']['endereco']) && is_array($linkPagamento->dados_cliente['preenchidos']['endereco']))
                                                    @php
                                                        $endereco = $linkPagamento->dados_cliente['preenchidos']['endereco'];
                                                        $enderecoCompleto = [];
                                                        if(!empty($endereco['rua'])) $enderecoCompleto[] = $endereco['rua'];
                                                        if(!empty($endereco['numero'])) $enderecoCompleto[] = $endereco['numero'];
                                                        if(!empty($endereco['bairro'])) $enderecoCompleto[] = $endereco['bairro'];
                                                        if(!empty($endereco['cidade'])) $enderecoCompleto[] = $endereco['cidade'];
                                                        if(!empty($endereco['estado'])) $enderecoCompleto[] = $endereco['estado'];
                                                        if(!empty($endereco['cep'])) $enderecoCompleto[] = 'CEP: ' . $endereco['cep'];
                                                    @endphp
                                                    @if(!empty($enderecoCompleto))
                                                        <div class="mt-1">
                                                            <small class="text-success"><i class="fas fa-check-circle"></i> Pré-preenchido: {{ implode(', ', $enderecoCompleto) }}</small>
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                   
                

                    <!-- Card de URLs -->
                    <div class="card mb-4 rounded-border">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-link mr-2"></i>
                                URLs de Configuração
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">URL de Retorno</label>
                                    @if($linkPagamento->url_retorno)
                                        <a href="{{ $linkPagamento->url_retorno }}" target="_blank" class="d-block text-break small">
                                            {{ $linkPagamento->url_retorno }}
                                        </a>
                                    @else
                                        <span class="text-muted small">Não configurada</span>
                                    @endif
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">URL do Webhook</label>
                                    @if($linkPagamento->url_webhook)
                                        <a href="{{ $linkPagamento->url_webhook }}" target="_blank" class="d-block text-break small">
                                            {{ $linkPagamento->url_webhook }}
                                        </a>
                                    @else
                                        <span class="text-muted small">Não configurado</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação para Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Confirmar Exclusão
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o link "<strong>{{ $linkPagamento->titulo }}</strong>"?</p>
                <p class="text-danger small">
                    <i class="fas fa-info-circle mr-1"></i>
                    Esta ação não pode ser desfeita.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
                <form action="{{ route('links-pagamento.destroy', $linkPagamento->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash mr-2"></i>Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copiarLink() {
    const linkInput = document.getElementById('linkInput');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // Para dispositivos móveis
    document.execCommand('copy');
    
    // Mostrar feedback
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i>';
    btn.classList.remove('btn-outline-primary', 'btn-outline-secondary');
    btn.classList.add('btn-success');
    
    // Mostrar mensagem de sucesso
    showToast('Link copiado com sucesso!', 'success');
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-primary');
    }, 2000);
}

function alterarStatus(novoStatus) {
    const acao = novoStatus === 'ATIVO' ? 'ativar' : 'desativar';
    if (!confirm(`Tem certeza que deseja ${acao} este link?`)) {
        return;
    }
    
    fetch(`/links-pagamento/{{ $linkPagamento->id }}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ status: novoStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao alterar status: ' + data.error);
        }
    })
    .catch(error => {
        alert('Erro ao alterar status do link');
    });
}

function confirmarExclusao() {
    $('#deleteModal').modal('show');
}

function showToast(message, type = 'success') {
    // Criar elemento do toast
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} mr-2"></i>
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    // Adicionar ao body
    document.body.appendChild(toast);
    
    // Remover após 3 segundos
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}
</script>
@endpush

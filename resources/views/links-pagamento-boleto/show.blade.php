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
                                        <span class="badge badge-success" style="font-size: 1rem;">R$ {{ number_format($linkPagamento->valor, 2, ',', '.') }}</span>
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
                                <i class="fas fa-file-invoice text-warning fs-1"></i>
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
                                
                                <button type="button" class="btn btn-outline-danger" onclick="excluirLink()">
                                    <i class="fas fa-trash mr-2"></i>Excluir
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Card de Informações do Boleto -->
                    <div class="card mb-4 rounded-border">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-file-invoice mr-2"></i>
                                Informações do Boleto
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Data de Vencimento</label>
                                    <p class="mb-0">{{ $linkPagamento->data_vencimento ? $linkPagamento->data_vencimento->format('d/m/Y') : 'Não informado' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Data Limite de Pagamento</label>
                                    <p class="mb-0">{{ $linkPagamento->data_limite_pagamento ? $linkPagamento->data_limite_pagamento->format('d/m/Y') : 'Não informado' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Quem paga as taxas</label>
                                    <p class="mb-0">
                                        @if($linkPagamento->juros === 'CLIENT')
                                            <span class="badge badge-info">Cliente</span>
                                        @else
                                            <span class="badge badge-warning">Estabelecimento</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Parcelas</label>
                                    <p class="mb-0">À vista (1x)</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card de Instruções do Boleto -->
                    @if($linkPagamento->instrucoes_boleto)
                    <div class="card mb-4 rounded-border">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list mr-2"></i>
                                Instruções do Boleto
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if($linkPagamento->instrucoes_boleto['description'])
                                <div class="col-12 mb-3">
                                    <label class="form-label text-muted small">Descrição</label>
                                    <p class="mb-0">{{ $linkPagamento->instrucoes_boleto['description'] }}</p>
                                </div>
                                @endif
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted small">Multa por atraso</label>
                                    <p class="mb-0">{{ $linkPagamento->instrucoes_boleto['late_fee']['amount'] ?? '0' }}%</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted small">Juros ao mês</label>
                                    <p class="mb-0">{{ $linkPagamento->instrucoes_boleto['interest']['amount'] ?? '0' }}%</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted small">Desconto</label>
                                    <p class="mb-0">{{ $linkPagamento->instrucoes_boleto['discount']['amount'] ?? '0' }}%</p>
                                </div>
                                @if($linkPagamento->instrucoes_boleto['discount']['limit_date'])
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Data limite para desconto</label>
                                    <p class="mb-0">{{ \Carbon\Carbon::parse($linkPagamento->instrucoes_boleto['discount']['limit_date'])->format('d/m/Y') }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Card de Dados do Cliente -->
                    @if($linkPagamento->dados_cliente && isset($linkPagamento->dados_cliente['preenchidos']))
                    @php
                        $dadosCliente = $linkPagamento->dados_cliente['preenchidos'];
                        $endereco = $dadosCliente['endereco'] ?? [];
                    @endphp
                    <div class="card mb-4 rounded-border">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user mr-2"></i>
                                Dados do Cliente
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Nome</label>
                                    <p class="mb-0">{{ $dadosCliente['nome'] ?? 'Não informado' }} {{ $dadosCliente['sobrenome'] ?? '' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Email</label>
                                    <p class="mb-0">{{ $dadosCliente['email'] ?? 'Não informado' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">Telefone</label>
                                    <p class="mb-0">{{ $dadosCliente['telefone'] ?? 'Não informado' }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted small">CPF/CNPJ</label>
                                    <p class="mb-0">{{ $dadosCliente['documento'] ?? 'Não informado' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card de Endereço -->
                    @if(!empty($endereco))
                    <div class="card mb-4 rounded-border">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-map-marker-alt mr-2"></i>
                                Endereço
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label text-muted small">Rua</label>
                                    <p class="mb-0">{{ $endereco['rua'] ?? 'Não informado' }}, {{ $endereco['numero'] ?? '' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted small">Complemento</label>
                                    <p class="mb-0">{{ $endereco['complemento'] ?? 'Não informado' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted small">Bairro</label>
                                    <p class="mb-0">{{ $endereco['bairro'] ?? 'Não informado' }}</p>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-muted small">Cidade</label>
                                    <p class="mb-0">{{ $endereco['cidade'] ?? 'Não informado' }}</p>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label text-muted small">Estado</label>
                                    <p class="mb-0">{{ $endereco['estado'] ?? 'Não informado' }}</p>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label text-muted small">CEP</label>
                                    <p class="mb-0">{{ $endereco['cep'] ?? 'Não informado' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Ação</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmButton">Confirmar</button>
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
    linkInput.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Mostrar feedback
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i>';
    button.classList.remove('btn-outline-primary');
    button.classList.add('btn-success');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-primary');
    }, 2000);
}

function alterarStatus(novoStatus) {
    const action = novoStatus === 'ATIVO' ? 'ativar' : 'desativar';
    const message = `Tem certeza que deseja ${action} este link de pagamento?`;
    
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmButton').onclick = function() {
        window.location.href = `{{ route('links-pagamento-boleto.status', $linkPagamento->id) }}`;
    };
    
    $('#confirmModal').modal('show');
}

function excluirLink() {
    const message = 'Tem certeza que deseja excluir este link de pagamento? Esta ação não pode ser desfeita.';
    
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmButton').onclick = function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ route('links-pagamento-boleto.destroy', $linkPagamento->id) }}`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    };
    
    $('#confirmModal').modal('show');
}
</script>
@endpush
@extends('templates.dashboard-template')

@section('title', 'Links de Pagamento - Cartão')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Links de Pagamento', 'icon' => 'fas fa-link', 'url' => route('links-pagamento.index')]
    ]"
/>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">Links de Pagamento - Cartão</h3>
                        <p class="text-muted mb-0">Gerencie seus links de pagamento com cartão de crédito</p>
                    </div>
                    <div>
                        <a href="{{ route('links-pagamento.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Novo Link
                        </a>
                    </div>
                </div>
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

                @if($links->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-bold">Valor</th>
                                    <th class="fw-bold">Status</th>
                                    <th class="fw-bold">Criado em</th>
                                    <th class="fw-bold">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($links as $link)
                                <tr>
                                    <td>
                                        <span class="badge bg-success fs-6">
                                            {{ $link->valor_formatado }}
                                        </span>
                                        @if($link->descricao)
                                            <br><small class="text-muted">{{ Str::limit($link->descricao, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($link->status)
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
                                                <span class="badge bg-secondary fs-6">{{ $link->status }}</span>
                                        @endswitch
                                    </td>
                                 
                                    <td>
                                        <small>{{ $link->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('links-pagamento.show', $link->id) }}" 
                                               class="btn btn-sm btn-info" 
                                               title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('links-pagamento.edit', $link->id) }}" 
                                               class="btn btn-sm btn-warning" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-secondary toggle-status" 
                                                    data-link-id="{{ $link->id }}"
                                                    data-current-status="{{ $link->status }}"
                                                    title="Alterar Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger delete-link" 
                                                    data-link-id="{{ $link->id }}"
                                                    data-link-titulo="{{ $link->titulo }}"
                                                    title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center">
                        {{ $links->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-link fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum link de pagamento criado</h5>
                        <p class="text-muted">Crie seu primeiro link para começar a receber pagamentos online</p>
                        <a href="{{ route('links-pagamento.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Criar Primeiro Link
                        </a>
                    </div>
                @endif
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
                <p>Tem certeza que deseja excluir o link "<strong id="linkTitulo"></strong>"?</p>
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
$(document).ready(function() {
    // Toggle status do link
    $('.toggle-status').click(function() {
        const linkId = $(this).data('link-id');
        const currentStatus = $(this).data('current-status');
        const newStatus = currentStatus === 'ATIVO' ? 'INATIVO' : 'ATIVO';
        
        $.ajax({
            url: `/links-pagamento/${linkId}/status`,
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
        const linkTitulo = $(this).data('link-titulo');
        
        $('#linkTitulo').text(linkTitulo);
        $('#deleteForm').attr('action', `/links-pagamento/${linkId}`);
        $('#deleteModal').modal('show');
    });
});
</script>
@endpush

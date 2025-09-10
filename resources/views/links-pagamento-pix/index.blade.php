@extends('templates.dashboard-template')

@section('title', 'Links de Pagamento - PIX')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Links de Pagamento PIX', 'icon' => 'fas fa-qrcode', 'url' => route('links-pagamento-pix.index')]
    ]"
/>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">
                            <i class="fas fa-qrcode mr-2 text-primary"></i>
                            Links de Pagamento - PIX
                        </h3>
                        <p class="text-muted mb-0">Gerencie seus links de pagamento PIX</p>
                    </div>
                    <div>
                        <a href="{{ route('links-pagamento-pix.create') }}" class="btn btn-novo-pagamento">
                            <i class="fas fa-plus me-2"></i>Novo Link PIX
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
                        <table id="linksPixTable" class="table table-hover">
                            <thead class="table-header-juntter">
                                <tr>
                                    <th></th>
                                    <th class="fw-bold">Valor</th>
                                    <th class="fw-bold">Descrição</th>
                                    <th class="fw-bold">Status</th>
                                    <th class="fw-bold">Criado em</th>
                                    <th class="fw-bold">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($links as $link)
                                <tr>
                                    <td></td>
                                    <td>
                                        <span class="badge bg-success fs-6">
                                            {{ $link->valor_formatado }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($link->descricao)
                                            <small class="text-muted">{{ Str::limit($link->descricao, 50) }}</small>
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
                                    <td data-order="{{ $link->created_at->format('Y-m-d H:i:s') }}">
                                        <small class="text-muted">{{ $link->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('links-pagamento-pix.show', $link->id) }}" 
                                               class="btn btn-sm btn-info" 
                                               title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('links-pagamento-pix.edit', $link->id) }}" 
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
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum link de pagamento PIX criado</h5>
                        <p class="text-muted">Crie seu primeiro link PIX para começar a receber pagamentos instantâneos</p>
                        <a href="{{ route('links-pagamento-pix.create') }}" class="btn btn-novo-pagamento">
                            <i class="fas fa-plus me-2"></i>
                            Criar Primeiro Link PIX
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
                <p>Tem certeza que deseja excluir este link de pagamento PIX?</p>
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
            url: `/links-pagamento-pix/${linkId}/status`,
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
        
        $('#deleteForm').attr('action', `/links-pagamento-pix/${linkId}`);
        $('#deleteModal').modal('show');
    });
});
</script>
@endpush

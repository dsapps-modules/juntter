@extends('templates.dashboard-template')

@section('title', 'Acesso do Vendedor')

@section('content')

    <x-breadcrumb :items="$breadcrumbItems" />

    <!-- Área de Cadastro -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-user-plus mr-2"></i>Conceder Acesso a Vendedor</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('vendedores.acesso.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="select_vendedor">Selecione o Vendedor (API)</label>
                        <select id="select_vendedor" class="form-control select2" required>
                            <option value="">Busque pelo nome...</option>
                        </select>
                        <input type="hidden" name="establishment_id" id="establishment_id">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Nome</label>
                        <input type="text" name="name" id="input_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>E-mail (Login)</label>
                        <input type="email" name="email" id="input_email" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Senha</label>
                        <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres"
                            required minlength="8">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Confirmar Senha</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-2"></i>Salvar Acesso</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Acessos -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-users-cog mr-2"></i>Vendedores com Acesso</h5>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Est. ID</th>
                        <th>Data Criação</th>
                        <th class="text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuariosLocais as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge badge-info">{{ $user->vendedor->estabelecimento_id ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-right">
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                    data-target="#modalEdit{{ $user->id }}" title="Editar Dados">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-warning" data-toggle="modal"
                                    data-target="#modalSenha{{ $user->id }}" title="Alterar Senha">
                                    <i class="fas fa-key"></i>
                                </button>

                                <form action="{{ route('vendedores.acesso.destroy', $user->id) }}" method="POST"
                                    class="d-inline"
                                    onclick="return confirm('Tem certeza? Isso impedirá o acesso deste vendedor.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Remover Acesso">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Modal Editar Dados -->
                        <div class="modal fade" id="modalEdit{{ $user->id }}" tabindex="-1" role="dialog"
                            aria-labelledby="modalEditLabel{{ $user->id }}" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form action="{{ route('vendedores.acesso.update', $user->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalEditLabel{{ $user->id }}">Editar Vendedor -
                                                {{ $user->name }}
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Nome</label>
                                                <input type="text" name="name" class="form-control" value="{{ $user->name }}"
                                                    required>
                                            </div>
                                            <div class="form-group">
                                                <label>E-mail</label>
                                                <input type="email" name="email" class="form-control" value="{{ $user->email }}"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-success">Salvar Alterações</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Modal Alterar Senha -->
                        <div class="modal fade" id="modalSenha{{ $user->id }}" tabindex="-1" role="dialog"
                            aria-labelledby="modalLabel{{ $user->id }}" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form action="{{ route('vendedores.acesso.update-senha', $user->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalLabel{{ $user->id }}">Alterar Senha -
                                                {{ $user->name }}
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Nova Senha</label>
                                                <input type="password" name="password" class="form-control" required
                                                    minlength="8">
                                            </div>
                                            <div class="form-group">
                                                <label>Confirmar Nova Senha</label>
                                                <input type="password" name="password_confirmation" class="form-control"
                                                    required minlength="8">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary">Salvar Nova Senha</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">Nenhum vendedor cadastrado localmente.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Inicializar Select2 com AJAX
            $('#select_vendedor').select2({
                theme: 'bootstrap4',
                placeholder: 'Busque pelo nome, documento ou email...',
                allowClear: true,
                minimumInputLength: 3,
                ajax: {
                    url: '{{ route('vendedores.acesso.search') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                }
            });

            // Preencher inputs ao selecionar vendedor
            $('#select_vendedor').on('select2:select', function (e) {
                var data = e.params.data;

                $('#establishment_id').val(data.id);

                if (data.name_clean) {
                    $('#input_name').val(data.name_clean);
                } else {
                    // Fallback se não vier limpo
                    var fullText = data.text;
                    var namePart = fullText.substring(0, fullText.lastIndexOf('(')).trim();
                    $('#input_name').val(namePart);
                }

                if (data.email) {
                    $('#input_email').val(data.email);
                } else {
                    $('#input_email').val(''); // Limpa ou deixa user digitar
                }
            });

            // Se limpar, limpa os campos
            $('#select_vendedor').on('select2:clear', function (e) {
                $('#establishment_id').val('');
                $('#input_name').val('');
                $('#input_email').val('');
            });
        });
    </script>
@endpush
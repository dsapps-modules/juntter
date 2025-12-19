@extends('templates.dashboard-template')

@section('title', 'Lista de Estabelecimentos')

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Estabelecimentos</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Estabelecimentos</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Buscar Estabelecimento</h3>
            <div class="card-tools">
                <!-- Select2 Search Box -->
                <div style="width: 300px;">
                    <select class="form-control select2" id="searchEstablishment" style="width: 100%;">
                        <option value="">Digite o nome ou documento...</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped projects">
                <thead>
                    <tr>
                        <th style="width: 25%">
                            Estabelecimento
                        </th>
                        <th style="width: 20%">
                            Documento
                        </th>
                        <th>
                            Localização
                        </th>
                        <th class="text-center">
                            Status
                        </th>
                        <th style="width: 20%">
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($estabelecimentos as $establishment)
                        <tr>
                            <td>
                                <a>
                                    {{ $establishment->display_name }}
                                </a>
                                <br />
                                <small>
                                    Criado em {{ $establishment->created_at->format('d/m/Y') }}
                                </small>
                            </td>
                            <td>
                                {{ $establishment->document }}
                            </td>
                            <td>
                                @php
                                    $address = $establishment->address_json;
                                @endphp
                                @if($address)
                                    {{ $address['city'] ?? '' }} / {{ $address['state'] ?? '' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="project-state">
                                @if ($establishment->active)
                                    <span class="badge badge-success">Ativo</span>
                                @else
                                    <span class="badge badge-danger">Inativo</span>
                                @endif
                            </td>
                            <td class="project-actions text-right">
                                <a class="btn btn-primary btn-sm"
                                    href="{{ route('estabelecimentos.show', $establishment->id) }}">
                                    <i class="fas fa-folder">
                                    </i>
                                    Detalhes
                                </a>
                                <a class="btn btn-info btn-sm" href="{{ route('estabelecimentos.edit', $establishment->id) }}">
                                    <i class="fas fa-pencil-alt">
                                    </i>
                                    Editar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Nenhum estabelecimento encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
        <div class="card-footer clearfix">
            {{ $estabelecimentos->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#searchEstablishment').select2({
                theme: 'bootstrap4',
                placeholder: 'Buscar estabelecimento...',
                allowClear: true,
                ajax: {
                    url: '{{ route('estabelecimentos.search') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 3,
            });

            // Redirect on selection
            $('#searchEstablishment').on('select2:select', function (e) {
                var data = e.params.data;
                window.location.href = "{{ url('/estabelecimentos') }}/" + data.id;
            });
        });
    </script>
@endpush
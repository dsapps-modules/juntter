@extends('templates.dashboard-template')

@section('title', 'Faturamento por Vendedor')

@section('content')

    <x-breadcrumb :items="$breadcrumbItems" :filtroData="[
            'mesAtual' => $mes,
            'anoAtual' => $ano,
        ]" />

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th style="width: 50px">#</th>
                        <th>Vendedor</th>
                        <th>Estabelecimento ID</th>
                        <th>Qtd. Transações</th>
                        <th>Total Bruto</th>
                        <th>Taxas</th>
                        <th>Total Líquido</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dados as $dado)
                        <tr>
                            <td>{{ $loop->iteration }}º</td>
                            <td><span title="{{ $dado['nome'] }}">{{ \Illuminate\Support\Str::limit($dado['nome'], 25) }}</span>
                            </td>
                            <td>{{ $dado['estabelecimento_id'] }}</td>
                            <td>{{ $dado['qtd'] }}</td>
                            <td>R$ {{ number_format($dado['total_bruto'] / 100, 2, ',', '.') }}</td>
                            <td class="text-danger">R$ {{ number_format($dado['total_taxas'] / 100, 2, ',', '.') }}</td>
                            <td class="text-success font-weight-bold">R$
                                {{ number_format($dado['total_liquido'] / 100, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Nenhum registro encontrado para este período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Clientes do Vendedor</title>
    <style>
        .documento-texto {
            mso-number-format: "\@";
        }
    </style>
</head>
<body>
    <table border="1">
        <colgroup>
            <col>
            <col width="{{ $clienteColumnWidth }}">
            <col>
            <col>
            <col>
            <col>
            <col>
        </colgroup>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Documento</th>
                <th>Transações</th>
                <th>Valor total</th>
                <th>Primeira transação</th>
                <th>Última transação</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clientes as $cliente)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $cliente['cliente'] }}</td>
                    <td class="documento-texto">{{ $cliente['documento'] }}</td>
                    <td>{{ $cliente['transacoes'] }}</td>
                    <td>{{ $cliente['valor_total'] }}</td>
                    <td>{{ $cliente['primeira_transacao'] }}</td>
                    <td>{{ $cliente['ultima_transacao'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nenhum cliente encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

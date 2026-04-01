<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Estabelecimentos</title>
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
            <col width="{{ $estabelecimentoColumnWidth }}">
            <col>
            <col>
            <col>
            <col>
            <col>
            <col>
            <col>
        </colgroup>
        <thead>
            <tr>
                <th>ID</th>
                <th>Estabelecimento</th>
                <th>Documento</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Cidade</th>
                <th>Estado</th>
                <th>Status</th>
                <th>Criado em</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($estabelecimentos as $estabelecimento)
                <tr>
                    <td>{{ $estabelecimento->id }}</td>
                    <td>{{ $estabelecimento->display_name }}</td>
                    <td class="documento-texto">{{ $estabelecimento->document }}</td>
                    <td>{{ $estabelecimento->email }}</td>
                    <td>{{ $estabelecimento->phone_number }}</td>
                    <td>{{ data_get($estabelecimento->address_json, 'city', '') }}</td>
                    <td>{{ data_get($estabelecimento->address_json, 'state', '') }}</td>
                    <td>{{ $estabelecimento->active ? 'Ativo' : 'Inativo' }}</td>
                    <td>{{ optional($estabelecimento->created_at)->format('d/m/Y H:i:s') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

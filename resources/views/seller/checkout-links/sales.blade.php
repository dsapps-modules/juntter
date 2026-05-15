<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Vendas</title></head>
<body>
    <h1>Vendas do link {{ $checkoutLink->name }}</h1>
    <table>
        <thead>
            <tr>
                <th>Pedido</th>
                <th>Data</th>
                <th>Status</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->created_at?->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ $order->status }}</td>
                    <td>R$ {{ number_format((float) $order->total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Vendas</title></head>
<body>
    <h1>Vendas do link {{ $checkoutLink->name }}</h1>
    <ul>
        @foreach($orders as $order)
            <li>{{ $order->order_number }} - {{ $order->status }} - R$ {{ number_format((float) $order->total, 2, ',', '.') }}</li>
        @endforeach
    </ul>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obrigado pela compra</title>
    <style>
        body { font-family: Arial, sans-serif; display: grid; place-items: center; min-height: 100vh; background: #f9fafb; margin: 0; color: #111827; }
        .card { background: #fff; border-radius: 18px; padding: 32px; max-width: 640px; width: calc(100% - 32px); box-shadow: 0 10px 30px rgba(15,23,42,.1); }
    </style>
</head>
<body>
    <div class="card">
        <h1>Pagamento aprovado</h1>
        @if($order)
            <p>Pedido: <strong>{{ $order->order_number }}</strong></p>
            <p>Produto: <strong>{{ $order->product->name }}</strong></p>
            <p>Valor: <strong>R$ {{ number_format((float) $order->total, 2, ',', '.') }}</strong></p>
            <p>Método: <strong>{{ strtoupper($order->payment_method) }}</strong></p>
        @else
            <p>Seu pagamento foi confirmado e estamos processando a entrega.</p>
        @endif
        <p>Checkout Juntter</p>
    </div>
</body>
</html>

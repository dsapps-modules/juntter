<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Links de checkout</title></head>
<body>
    <h1>Links de checkout</h1>
    <ul>
        @foreach($links as $link)
            <li>{{ $link->name }} - {{ route('checkout.public.show', $link->public_token) }}</li>
        @endforeach
    </ul>
</body>
</html>

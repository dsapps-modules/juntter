<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Link de checkout</title></head>
<body>
    <h1>{{ $checkoutLink->name }}</h1>
    <p>{{ route('checkout.public.show', $checkoutLink->public_token) }}</p>
</body>
</html>

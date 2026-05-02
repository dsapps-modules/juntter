<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout indisponível</title>
    <style>
        body { font-family: Arial, sans-serif; display: grid; place-items: center; min-height: 100vh; background: #f3f4f6; margin: 0; color: #111827; }
        .card { background: #fff; border-radius: 18px; padding: 32px; max-width: 520px; box-shadow: 0 10px 30px rgba(15,23,42,.1); text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Este checkout não está disponível no momento.</h1>
        <p>{{ $message ?? 'Tente novamente mais tarde.' }}</p>
    </div>
</body>
</html>

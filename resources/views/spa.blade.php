<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Checkout Juntter</title>
    @unless(app()->environment('testing'))
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @endunless
</head>
<body>
    <div id="app"></div>
</body>
</html>

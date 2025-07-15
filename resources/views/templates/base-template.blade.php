<!DOCTYPE html>
<html lang="pt-br">
    @include('templates.cabecalho')

<body class="hold-transition layout-top-nav">
    <div class="wrapper">
        <x-navbar />
        @yield('header')
        @yield('content')
        @yield('search')
    </div>
    <x-footer />
    @include('templates.rodape')
</body>
</html>
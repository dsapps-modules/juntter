<!DOCTYPE html>
<html lang="pt-br">
    @include('templates.cabecalho')

<body class="hold-transition layout-top-nav">
    <div class="wrapper">
        @yield('header')
        @yield('content')
        @yield('search')
    </div>
    @include('templates.footer')
    @include('templates.rodape')
</body>
</html>
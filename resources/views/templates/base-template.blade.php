<!DOCTYPE html>
<html lang="en">
    @include('templates.cabecalho')

        @yield('header')
        @yield('content')
        @yield('search')

    @include('templates.rodape')
</html>
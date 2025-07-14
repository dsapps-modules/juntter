@extends('templates.base-template')

@push('styles')
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">    
    <link rel="stylesheet" href="{{ asset('css/juntter-styles.css') }}">
    <style>
        a.nostyle:link, a.nostyle:visited {
            text-decoration: inherit;
            color: inherit;
            cursor: auto;
        }
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            line-height: 1.6;
            overflow-x: hidden;
        }
        /* Background padrão para páginas que não são de auth */
        body:not(.auth-page) {
            background: var(--light-gray);
        }
        /* Background gradiente para páginas de auth */
        body.auth-page {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 50%, var(--dark-color) 100%);
            min-height: 100vh;
        }
    </style>
@endpush

@section('header')
@endsection

@section('content')
    @yield('page')
@endsection

@section('search')
@endsection

@push('scripts')
    <script>
        $(document).ready(function(){
            setTimeout(function() {
                $('#success-display').hide(500)
            }, 4000);
        });
    </script>
@endpush
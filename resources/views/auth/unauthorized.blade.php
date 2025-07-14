@extends('templates.sample-template')

@section('page')
<div class="loading-overlay" id="loading">
    <div class="loading-spinner"></div>
</div>

<div class="particles-container" id="particles"></div>

<nav class="main-header navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a href="{{ route('checkout') }}" class="navbar-brand">
            <img src="{{ asset('logo/JUNTTER-MODELO-1-SF.webp') }}" alt="Juntter" class="brand-image" style="height:36px;">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a href="{{ route('checkout') }}#beneficios" class="nav-link">Benefícios</a></li>
                <li class="nav-item"><a href="{{ route('checkout') }}#precos" class="nav-link">Preços</a></li>
                <li class="nav-item"><a href="{{ route('checkout') }}#como-funciona" class="nav-link">Como Funciona</a></li>
                <li class="nav-item"><a href="{{ route('checkout') }}#depoimentos" class="nav-link">Depoimentos</a></li>
                <li class="nav-item"><a href="{{ route('checkout') }}#faq" class="nav-link">FAQ</a></li>
                <li class="nav-item"><a href="{{ route('login') }}" class="btn btn-warning ml-2 px-4">Login</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="unauthorized-container">
    <div class="unauthorized-card animate__animated animate__fadeInUp">
        <div class="unauthorized-header">
            <div class="unauthorized-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="unauthorized-title">Acesso Negado</h1>
            <p class="unauthorized-subtitle">Você não tem permissão para acessar esta página</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="unauthorized-content">
            <p>O nível de acesso da sua conta não permite visualizar este conteúdo.</p>
            <p>Entre em contato com o administrador do sistema para solicitar as permissões necessárias.</p>
        </div>

        <div class="unauthorized-actions">
            <a href="{{ route('checkout') }}" class="btn btn-primary">
                <i class="fas fa-home mr-1"></i>Voltar ao Início
            </a>
            <a href="{{ route('login') }}" class="btn btn-secondary">
                <i class="fas fa-sign-in-alt mr-1"></i>Fazer Login
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .unauthorized-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 80px);
        padding: 20px;
        position: relative;
        z-index: 10;
    }

    .unauthorized-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        max-width: 500px;
        width: 100%;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        position: relative;
        z-index: 11;
    }

    .unauthorized-header {
        margin-bottom: 30px;
    }

    .unauthorized-icon {
        font-size: 4rem;
        color: #dc3545;
        margin-bottom: 20px;
    }

    .unauthorized-title {
        font-size: 2rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
    }

    .unauthorized-subtitle {
        font-size: 1.1rem;
        color: #666;
        margin-bottom: 0;
    }

    .unauthorized-content {
        margin: 30px 0;
        text-align: left;
    }

    .unauthorized-content p {
        color: #555;
        margin-bottom: 15px;
        line-height: 1.6;
    }

    .unauthorized-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .unauthorized-actions .btn {
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .unauthorized-actions .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 768px) {
        .unauthorized-card {
            padding: 30px 20px;
        }

        .unauthorized-title {
            font-size: 1.5rem;
        }

        .unauthorized-actions {
            flex-direction: column;
        }

        .unauthorized-actions .btn {
            width: 100%;
        }
    }
</style>
@endpush 

@push('scripts')
<script>
    $(document).ready(function() {
        $('body').addClass('auth-page');
    });
</script>
@endpush 
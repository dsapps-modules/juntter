@extends('templates.base-template')

@section('header')
@endsection

@section('content')
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

<div class="recovery-container">
    <div class="recovery-card animate__animated animate__fadeInUp">
        <!-- Initial Form State -->
        <div id="formState">
            <div class="recovery-header">
                <div class="recovery-logo">
                    <i class="fas fa-key"></i>
                </div>
                <h1 class="recovery-title">Esqueceu a senha?</h1>
                <p class="recovery-subtitle">Não se preocupe! Digite seu e-mail e enviaremos instruções para redefinir sua senha.</p>
            </div>

            <div id="recoveryAlert" class="alert" style="display: none;"></div>

            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form id="recoveryForm" method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                           id="email" name="email" placeholder="Digite seu e-mail cadastrado" 
                           value="{{ old('email') }}" required>
                    <i class="fas fa-envelope input-icon"></i>
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-recovery" id="recoveryBtn">
                    <span id="recoveryBtnText">
                        <i class="fas fa-paper-plane mr-2"></i>Enviar Instruções
                    </span>
                </button>
            </form>

            <div class="form-links">
                <a href="{{ route('login') }}" class="back-link">
                    <i class="fas fa-arrow-left mr-1"></i>Voltar ao Login
                </a>
                <a href="{{ route('checkout') }}" class="back-link">
                    <i class="fas fa-home mr-1"></i>Início
                </a>
            </div>
        </div>

        <!-- Success State -->
        <div id="successState" class="success-state" style="display: none;">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="success-title">E-mail Enviado!</h2>
            <p class="success-subtitle">
                Verifique sua caixa de entrada e siga as instruções para redefinir sua senha. 
                <br><br>
                Não recebeu o e-mail? Verifique a pasta de spam ou tente novamente.
            </p>
            
            <div class="form-links">
                <a href="{{ route('login') }}" class="back-link">
                    <i class="fas fa-arrow-left mr-1"></i>Voltar ao Login
                </a>
                <br><br>
                <a href="#" class="back-link" onclick="resetForm()">
                    <i class="fas fa-redo mr-1"></i>Tentar Novamente
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    body {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 50%, var(--dark-color) 100%);
        font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        min-height: 100vh;
        overflow-x: hidden;
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/juntter-styles.css') }}">
@endpush

@push('scripts')
<!-- JavaScript unificado já carregado via app.js -->
@endpush 
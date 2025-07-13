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

<div class="login-container">
    <div class="login-card animate__animated animate__fadeInUp">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-user"></i>
            </div>
            <h1 class="login-title">Bem-vindo de volta!</h1>
            <p class="login-subtitle">Acesse sua conta para continuar vendendo</p>
        </div>

        <div id="loginAlert" class="alert" style="display: none;"></div>

        <form id="loginForm" method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                       id="email" name="email" placeholder="Digite seu e-mail" 
                       value="{{ old('email') }}" required>
                <i class="fas fa-envelope input-icon"></i>
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                       id="password" name="password" placeholder="Digite sua senha" required>
                <i class="fas fa-lock input-icon"></i>
                <button type="button" class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye" id="passwordIcon"></i>
                </button>
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="remember-me">
                <label class="custom-checkbox">
                    <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span class="checkmark"></span>
                </label>
                <span>Lembrar-me</span>
            </div>

            <button type="submit" class="btn btn-login" id="loginBtn">
                <span id="loginBtnText">Entrar</span>
            </button>
        </form>

        <div class="form-links">
            <a href="{{ route('password.request') }}" class="forgot-link">
                <i class="fas fa-key mr-1"></i>Esqueceu a senha?
            </a>
            <br>
            <a href="{{ route('checkout') }}" class="back-link">
                <i class="fas fa-arrow-left mr-1"></i>Voltar ao início
            </a>
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
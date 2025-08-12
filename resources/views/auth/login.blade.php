@extends('templates.sample-template')

@section('page')
<div class="loading-overlay" id="loading">
    <div class="loading-spinner"></div>
</div>

<div class="particles-container" id="particles"></div>

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

        <!-- Session Status -->
        <x-auth-session-status :status="session('status')" />

        <!-- Success Message -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('success') }}
                
            </div>
        @endif

        <form id="loginForm" method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                           id="email" name="email" placeholder="Digite seu e-mail" 
                           value="{{ old('email') }}" required autocomplete="username">
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                <x-input-error :messages="$errors->get('email')" />
            </div>

            <div class="form-group">
                <div class="input-wrapper">
                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                           id="password" name="password" placeholder="Digite sua senha" required autocomplete="current-password">
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordIcon"></i>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" />
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
            <!-- Registro desabilitado -->
            <br>
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

@push('scripts')
<script> 
    $(document).ready(function() {
        $('body').addClass('auth-page');
    });
 
</script>
@endpush

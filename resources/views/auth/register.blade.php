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
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 class="login-title">Criar Conta</h1>
            <p class="login-subtitle">Cadastre-se para acessar sua área de vendedor</p>
        </div>

        <form id="registerForm" method="POST" action="{{ route('register.post') }}">
            @csrf
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Nome completo" value="{{ old('name') }}" required autofocus>
                    <i class="fas fa-user input-icon"></i>
                </div>
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="E-mail" value="{{ old('email') }}" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Senha" required>
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordIcon"></i>
                    </button>
                </div>
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirme a senha" required>
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePasswordConfirmation()">
                        <i class="fas fa-eye" id="passwordConfirmationIcon"></i>
                    </button>
                </div>
                <div id="password-match-message" class="mt-2" style="display: none;"></div>
            </div>
            <button type="submit" class="btn btn-login" id="registerBtn">
                <span id="registerBtnText">Cadastrar</span>
            </button>
        </form>

        <div class="form-links">
            <a href="{{ route('login') }}" class="back-link">
                <i class="fas fa-arrow-left mr-1"></i>Já tem conta? Entrar
            </a>
            <a href="{{ route('password.request') }}" class="back-link">
                <i class="fas fa-key mr-1"></i>Esqueceu a senha?
            </a>
            <br>
            <a href="{{ route('checkout') }}" class="back-link">
                <i class="fas fa-home mr-1"></i>Voltar ao início
            </a>
        </div>
    </div>
</div>
@endsection



@push('scripts')
    <script src="{{ asset('js/cadastro.js') }}"></script>
@endpush 

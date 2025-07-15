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
                <i class="fas fa-key"></i>
            </div>
            <h1 class="login-title">Redefinir Senha</h1>
            <p class="login-subtitle">Digite sua nova senha abaixo.</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div class="form-group">
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="E-mail" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
                <i class="fas fa-envelope input-icon"></i>
                <x-input-error :messages="$errors->get('email')" />
            </div>
            <div class="form-group">
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Nova senha" required autocomplete="new-password">
                <i class="fas fa-lock input-icon"></i>
                <x-input-error :messages="$errors->get('password')" />
            </div>
            <div class="form-group">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirme a nova senha" required autocomplete="new-password">
                <i class="fas fa-lock input-icon"></i>
            </div>
            <button type="submit" class="btn btn-login">
                Redefinir Senha
            </button>
        </form>

        <div class="form-links">
            <a href="{{ route('login') }}" class="back-link">
                <i class="fas fa-arrow-left mr-1"></i>Voltar ao login
            </a>
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('body').addClass('auth-page');
            
            // Garantir que a página sempre comece no topo
            window.scrollTo(0, 0);
            
            // Também forçar scroll para o topo após o carregamento
            $(window).on('load', function() {
                window.scrollTo(0, 0);
            });
        });
    </script>
@endpush

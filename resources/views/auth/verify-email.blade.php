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
                <i class="fas fa-envelope-open"></i>
            </div>
            <h1 class="login-title">Verifique seu E-mail</h1>
            <p class="login-subtitle">Obrigado por se cadastrar! Antes de começar, você precisa verificar seu endereço de e-mail clicando no link que acabamos de enviar para você.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                Um novo link de verificação foi enviado para o endereço de e-mail que você forneceu durante o cadastro.
        </div>
    @endif

        <div class="verification-content">
            <p>Não recebeu o e-mail? Verifique sua pasta de spam ou solicite um novo link.</p>
        </div>

        <div class="verification-actions">
            <form method="POST" action="{{ route('verification.send') }}" style="display: inline;">
            @csrf
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-paper-plane mr-2"></i>Reenviar E-mail
                </button>
        </form>

            <form method="POST" action="{{ route('logout') }}" style="display: inline; margin-left: 10px;">
            @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-out-alt mr-2"></i>Sair
            </button>
        </form>
        </div>

        <div class="form-links">
            <a href="{{ route('login') }}" class="back-link">
                <i class="fas fa-arrow-left mr-1"></i>Voltar ao Login
            </a>
            <br>
            <a href="{{ route('checkout') }}" class="back-link">
                <i class="fas fa-home mr-1"></i>Voltar ao Início
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

@push('styles')
<style>
    .verification-content {
        text-align: center;
        margin: 20px 0;
        color: #666;
    }
    
    .verification-actions {
        text-align: center;
        margin: 20px 0;
    }
    
    .verification-actions .btn {
        margin: 5px;
    }
    
    .btn-outline-secondary {
        background: transparent;
        border: 2px solid #6c757d;
        color: #6c757d;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .btn-outline-secondary:hover {
        background: #6c757d;
        color: white;
        transform: translateY(-2px);
    }
</style>
@endpush

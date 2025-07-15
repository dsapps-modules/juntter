@extends('templates.sample-template')

@section('page')
<div class="loading-overlay" id="loading">
    <div class="loading-spinner"></div>
</div>

<div class="particles-container" id="particles"></div>

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

            <!-- Session Status -->
            <x-auth-session-status :status="session('status')" />

            <form id="recoveryForm" method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                           id="email" name="email" placeholder="Digite seu e-mail cadastrado" 
                           value="{{ old('email') }}" required>
                    <i class="fas fa-envelope input-icon"></i>
                    <x-input-error :messages="$errors->get('email')" />
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

@push('scripts')
<script>
    $(document).ready(function() {
        $('body').addClass('auth-page');
     
    });
  
</script>
@endpush

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
                <div class="input-wrapper">
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="E-mail" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
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
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Nova senha" required autocomplete="new-password">
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
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirme a nova senha" required autocomplete="new-password">
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePasswordConfirmation()">
                        <i class="fas fa-eye" id="passwordConfirmationIcon"></i>
                    </button>
                </div>
                <div id="password-match-message" class="mt-2" style="display: none;"></div>
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
            
            // Função para verificar se as senhas coincidem
            function checkPasswordMatch() {
                const password = $('#password').val();
                const passwordConfirmation = $('#password_confirmation').val();
                
                // Remove mensagens anteriores
                $('#password-match-message').hide();
                $('#password_confirmation').removeClass('is-valid is-invalid');
                
                // Se ambos os campos estão preenchidos
                if (password && passwordConfirmation) {
                    if (password === passwordConfirmation) {
                        // Senhas coincidem
                        $('#password_confirmation').addClass('is-valid').removeClass('is-invalid');
                        $('#password-match-message').html('<span style="color: #28a745;"><i class="fas fa-check-circle"></i> Senhas coincidem!</span>').show();
                    } else {
                        // Senhas não coincidem
                        $('#password_confirmation').addClass('is-invalid').removeClass('is-valid');
                        $('#password-match-message').html('<span style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> As senhas não coincidem!</span>').show();
                    }
                } else {
                    // Campos vazios - remove classes
                    $('#password_confirmation').removeClass('is-valid is-invalid');
                }
            }

            // Adiciona listeners para validação em tempo real
            $('#password, #password_confirmation').on('input', checkPasswordMatch);

            // Validação adicional no envio do formulário
            $('form').on('submit', function(e) {
                const password = $('#password').val();
                const passwordConfirmation = $('#password_confirmation').val();
                
                if (password !== passwordConfirmation) {
                    e.preventDefault();
                    $('#password_confirmation').addClass('is-invalid');
                    $('#password-match-message').html('<span style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> As senhas não coincidem! Por favor, corrija antes de continuar.</span>').show();
                    
                    // Foca no campo de confirmação
                    $('#password_confirmation').focus();
                    return false;
                }
            });
        });

        // Função para o primeiro campo de senha
        window.togglePassword = function() {
            const passwordField = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            if (passwordField && passwordIcon) {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    passwordIcon.className = 'fas fa-eye-slash';
                } else {
                    passwordField.type = 'password';
                    passwordIcon.className = 'fas fa-eye';
                }
            }
        };

        // Função para o segundo campo de senha (confirmação)
        window.togglePasswordConfirmation = function() {
            const passwordField = document.getElementById('password_confirmation');
            const passwordIcon = document.getElementById('passwordConfirmationIcon');
            if (passwordField && passwordIcon) {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    passwordIcon.className = 'fas fa-eye-slash';
                } else {
                    passwordField.type = 'password';
                    passwordIcon.className = 'fas fa-eye';
                }
            }
        };
    </script>
@endpush

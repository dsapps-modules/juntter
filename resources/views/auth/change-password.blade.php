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
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="login-title">Seja bem vindo!</h1>
            <p class="login-subtitle">Por segurança, você deve alterar sua senha em seu primeiro acesso</p>
        </div>

        <div id="passwordAlert" class="alert" style="display: none;"></div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('success') }}
            </div>
        @endif

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="passwordForm" method="POST" action="{{ route('password.change.post') }}">
            @csrf
            
            <div class="form-group">
                <label for="password" class="form-label fw-bold">
                    <i class="fas fa-key mr-1 text-warning"></i>Nova Senha
                </label>
                <div class="input-wrapper">
                    <input type="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           id="password" 
                           name="password" 
                           placeholder="Digite sua nova senha" 
                           required 
                           minlength="8">
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordIcon"></i>
                    </button>
                </div>
                
                <!-- Indicador de força da senha -->
                <div class="mt-2">
                    <div class="progress" style="height: 5px;">
                        <div id="password-strength-bar" class="progress-bar" style="width: 0%"></div>
                    </div>
                    <small class="text-muted">
                        <span id="password-strength-text">Digite sua senha</span>
                    </small>
                </div>
                
                <!-- Critérios de validação -->
                <div class="mt-2">
                    <small class="text-muted">
                        <div class="row">
                            <div class="col-6">
                                <i class="fas fa-circle" id="criteria-length" style="color: #ccc;"></i> Mínimo 8 caracteres<br>
                                <i class="fas fa-circle" id="criteria-uppercase" style="color: #ccc;"></i> Letra maiúscula<br>
                                <i class="fas fa-circle" id="criteria-lowercase" style="color: #ccc;"></i> Letra minúscula
                            </div>
                            <div class="col-6">
                                <i class="fas fa-circle" id="criteria-number" style="color: #ccc;"></i> Número<br>
                                <i class="fas fa-circle" id="criteria-special" style="color: #ccc;"></i> Caractere especial
                            </div>
                        </div>
                    </small>
                </div>
                
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label fw-bold">
                    <i class="fas fa-check-double mr-1 text-warning"></i>Confirmar Nova Senha
                </label>
                <div class="input-wrapper">
                    <input type="password" 
                           class="form-control @error('password_confirmation') is-invalid @enderror" 
                           id="password_confirmation" 
                           name="password_confirmation" 
                           placeholder="Confirme sua nova senha" 
                           required 
                           minlength="8">
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePasswordConfirmation()">
                        <i class="fas fa-eye" id="passwordConfirmationIcon"></i>
                    </button>
                </div>
                
                <!-- Mensagem de confirmação de senha -->
                <div id="password-match-message" class="mt-2" style="display: none;">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        <span id="password-match-text"></span>
                    </small>
                </div>
                
                <x-input-error :messages="$errors->get('password_confirmation')" />
            </div>

            <button type="submit" class="btn btn-login" id="passwordBtn" disabled>
                <span id="passwordBtnText">Alterar Senha</span>
            </button>
        </form>

        <div class="form-links">
            <small class="text-muted">
                <i class="fas fa-info-circle mr-1"></i>
                Após alterar sua senha, você será redirecionado para o login
            </small>
        </div>
    </div>
</div>
@endsection 

@push('scripts')
<script> 
    $(document).ready(function() {
        $('body').addClass('auth-page');
        
        // Validação em tempo real
        const $password = $('#password');
        const $passwordConfirmation = $('#password_confirmation');
        
        // Função para validar força da senha
        function validatePasswordStrength(password) {
            const minLength = password.length >= 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            
            return {
                minLength,
                hasUpperCase,
                hasLowerCase,
                hasNumbers,
                hasSpecialChar,
                isValid: minLength && hasUpperCase && hasLowerCase && hasNumbers && hasSpecialChar
            };
        }
        
        // Função para atualizar indicadores de força
        function updatePasswordStrength(password) {
            const strength = validatePasswordStrength(password);
            const $strengthBar = $('#password-strength-bar');
            const $strengthText = $('#password-strength-text');
            
            // Atualizar critérios visuais
            updateCriteria('criteria-length', strength.minLength);
            updateCriteria('criteria-uppercase', strength.hasUpperCase);
            updateCriteria('criteria-lowercase', strength.hasLowerCase);
            updateCriteria('criteria-number', strength.hasNumbers);
            updateCriteria('criteria-special', strength.hasSpecialChar);
            
            if ($strengthBar.length && $strengthText.length) {
                const validCount = Object.values(strength).filter(Boolean).length - 1; // -1 para excluir isValid
                const percentage = (validCount / 5) * 100;
                
                $strengthBar.css('width', percentage + '%');
                
                if (percentage <= 20) {
                    $strengthBar.removeClass().addClass('progress-bar bg-danger');
                    $strengthText.text('Muito fraca');
                } else if (percentage <= 40) {
                    $strengthBar.removeClass().addClass('progress-bar bg-warning');
                    $strengthText.text('Fraca');
                } else if (percentage <= 60) {
                    $strengthBar.removeClass().addClass('progress-bar bg-info');
                    $strengthText.text('Média');
                } else if (percentage <= 80) {
                    $strengthBar.removeClass().addClass('progress-bar bg-primary');
                    $strengthText.text('Forte');
                } else {
                    $strengthBar.removeClass().addClass('progress-bar bg-success');
                    $strengthText.text('Muito forte');
                }
            }
        }
        
        // Função para atualizar critérios visuais
        function updateCriteria(elementId, isValid) {
            const $element = $('#' + elementId);
            if ($element.length) {
                if (isValid) {
                    $element.css('color', '#28a745').removeClass('fas fa-circle').addClass('fas fa-check-circle');
                } else {
                    $element.css('color', '#ccc').removeClass('fas fa-check-circle').addClass('fas fa-circle');
                }
            }
        }
        
        // Função para validar confirmação
        function validatePasswordConfirmation() {
            const passwordValue = $password.val();
            const confirmationValue = $passwordConfirmation.val();
            const $matchMessage = $('#password-match-message');
            const $matchText = $('#password-match-text');
            
            if (confirmationValue) {
                if (passwordValue === confirmationValue) {
                    $passwordConfirmation.removeClass('is-invalid').addClass('is-valid');
                    $matchMessage.show();
                    $matchText.html('<span class="text-success"><i class="fas fa-check-circle me-1"></i>Senhas coincidem!</span>');
                } else {
                    $passwordConfirmation.addClass('is-invalid').removeClass('is-valid');
                    $matchMessage.show();
                    $matchText.html('<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Senhas não coincidem</span>');
                }
            } else {
                $passwordConfirmation.removeClass('is-invalid is-valid');
                $matchMessage.hide();
            }
        }
        
        // Habilitar botão somente quando as senhas coincidirem e respeitarem critérios mínimos
        if ($password.length) {
            $password.on('input', function() {
                updatePasswordStrength($(this).val());
                validatePasswordConfirmation();
                toggleSubmit();
            });
        }
        
        if ($passwordConfirmation.length) {
            $passwordConfirmation.on('input', function(){
                validatePasswordConfirmation();
                toggleSubmit();
            });
        }

        function toggleSubmit(){
            const pass = $password.val();
            const conf = $passwordConfirmation.val();
            const okStrength = validatePasswordStrength(pass).minLength; // mínimo: 8
            const match = pass && conf && pass === conf;
            $('#passwordBtn').prop('disabled', !(okStrength && match));
        }
    });

    function togglePasswordConfirmation() {
        const field = document.getElementById('password_confirmation');
        const icon = document.getElementById('passwordConfirmationIcon');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endpush

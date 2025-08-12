@extends('templates.dashboard-template')

@section('title', 'Alterar Senha')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Configurações', 'icon' => 'fas fa-cogs', 'url' => '#'],
        ['label' => 'Perfil', 'icon' => 'fas fa-user', 'url' => route('profile.edit')],
        ['label' => 'Alterar Senha', 'icon' => 'fas fa-key', 'url' => '#']
    ]"
/>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">
                            <i class="fas fa-key me-2 text-primary"></i>Alterar Senha
                        </h3>
                        <p class="text-muted mb-0">Mantenha sua conta segura com uma senha forte</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar ao Perfil
                    </a>
                </div>

                @if (session('status') === 'password-updated')
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        Senha atualizada com sucesso!
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if ($errors->updatePassword->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Erro ao alterar senha:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->updatePassword->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                

                <form method="post" action="{{ route('password.update') }}">
                    @csrf
                    @method('put')

                    <div class="row">
                        <div class="col-12 mb-4">
                            <label for="current_password" class="form-label fw-bold">
                                <i class="fas fa-lock me-1 text-primary"></i>Senha Atual
                            </label>
                            <div class="input-group no-wrap">
                                <input type="password" 
                                       class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" 
                                       id="current_password" 
                                       name="current_password" 
                                       required>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordField('current_password')">
                                        <i class="fas fa-eye" id="current_password_icon"></i>
                                    </button>
                                </div>
                            </div>
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-4">
                            <label for="password" class="form-label fw-bold">
                                <i class="fas fa-key me-1 text-primary"></i>Nova Senha
                            </label>
                            <div class="input-group no-wrap">
                                <input type="password" 
                                       class="form-control @error('password', 'updatePassword') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       required>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordField('password')">
                                        <i class="fas fa-eye" id="password_icon"></i>
                                    </button>
                                </div>
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
                            
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-4">
                            <label for="password_confirmation" class="form-label fw-bold">
                                <i class="fas fa-check-double me-1 text-primary"></i>Confirmar Nova Senha
                            </label>
                            <div class="input-group no-wrap">
                                <input type="password" 
                                       class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       required>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordField('password_confirmation')">
                                        <i class="fas fa-eye" id="password_confirmation_icon"></i>
                                    </button>
                                </div>
                            </div>
                                                         <!-- Mensagem de confirmação de senha -->
                             <div id="password-match-message" class="mt-2" style="display: none;">
                                 <small class="text-muted">
                                     <i class="fas fa-info-circle me-1"></i>
                                     <span id="password-match-text"></span>
                                 </small>
                             </div>
                             
                             @error('password_confirmation', 'updatePassword')
                                 <div class="invalid-feedback d-block">{{ $message }}</div>
                             @enderror
                         </div>
                     </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </a>
                        <button type="submit" id="changePasswordBtn" class="btn btn-warning text-white" disabled>
                            <i class="fas fa-save mr-2"></i>Alterar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Função para toggle de senha (global) - renomeada para evitar conflito
window.togglePasswordField = function(fieldId) {
    const $field = $('#' + fieldId);
    const $icon = $('#' + fieldId + '_icon');
    
    if ($field.length && $icon.length) {
        if ($field.attr('type') === 'password') {
            $field.attr('type', 'text');
            $icon.removeClass('fas fa-eye').addClass('fas fa-eye-slash');
        } else {
            $field.attr('type', 'password');
            $icon.removeClass('fas fa-eye-slash').addClass('fas fa-eye');
        }
    }
};

$(document).ready(function() {
    // Validação em tempo real
    const $password = $('#password');
    const $passwordConfirmation = $('#password_confirmation');
    const $currentPassword = $('#current_password');
    
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
    
    if ($currentPassword.length) {
        $currentPassword.on('input', function() {
            if ($(this).val().length > 0) {
                $(this).removeClass('is-invalid');
            }
        });
    }

    function toggleSubmit(){
        const pass = $password.val();
        const conf = $passwordConfirmation.val();
        const okStrength = validatePasswordStrength(pass).minLength; // mínimo: 8
        const match = pass && conf && pass === conf;
        $('#changePasswordBtn').prop('disabled', !(okStrength && match));
    }
});
</script>
@endpush

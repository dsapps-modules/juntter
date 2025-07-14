
$(document).ready(function() {
    // Adiciona classe auth-page ao body
    $('body').addClass('auth-page');
    
    // Garante que a página começa no topo
    $(window).scrollTop(0);
    
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
                $('#registerBtn').prop('disabled', false);
            } else {
                // Senhas não coincidem
                $('#password_confirmation').addClass('is-invalid').removeClass('is-valid');
                $('#password-match-message').html('<span style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> As senhas não coincidem!</span>').show();
                $('#registerBtn').prop('disabled', true);
            }
        } else {
            // Campos vazios - remove classes
            $('#password_confirmation').removeClass('is-valid is-invalid');
            $('#registerBtn').prop('disabled', false);
        }
    }

    // Adiciona listeners para validação em tempo real
    $('#password, #password_confirmation').on('input', checkPasswordMatch);

    // Validação adicional no envio do formulário
    $('#registerForm').on('submit', function(e) {
        const password = $('#password').val();
        const passwordConfirmation = $('#password_confirmation').val();
        
        if (password !== passwordConfirmation) {
            e.preventDefault();
            $('#password_confirmation').addClass('is-invalid');
            $('#password-match-message').html('<span style="color: #dc3545;"><i class="fas fa-exclamation-circle"></i> As senhas não coincidem! Por favor, corrija antes de continuar.</span>').show();
            $('#registerBtn').prop('disabled', true);
            
            // Foca no campo de confirmação
            $('#password_confirmation').focus();
            return false;
        }
    });

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
});
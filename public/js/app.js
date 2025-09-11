// Juntter JavaScript

// Loading Screen (fora do ready para garantir execução)
$(window).on('load', function() {
    $('#loading').fadeOut(1000);
});

$(document).ready(function() {
    // Create Particles
    function createParticles() {
        const container = document.getElementById('particles');
        if (container) {
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.width = Math.random() * 8 + 4 + 'px';
                particle.style.height = particle.style.width;
                particle.style.animationDelay = Math.random() * 8 + 's';
                container.appendChild(particle);
            }
        }
    }

    // Password Toggle
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
    }

    // Form Validation
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    function validateField(field, isValid) {
        field.removeClass('is-valid is-invalid');
        if (isValid) {
            field.addClass('is-valid');
        } else {
            field.addClass('is-invalid');
        }
    }

    // Real-time validation for email
    if ($('#email').length) {
        $('#email').on('input', function() {
            const email = $(this).val();
            if (email.length > 0) {
                validateField($(this), validateEmail(email));
            } else {
                $(this).removeClass('is-valid is-invalid');
            }
        });
    }

    // Real-time validation for password
    if ($('#password').length) {
        $('#password').on('input', function() {
            const password = $(this).val();
            if (password.length > 0) {
                validateField($(this), password.length >= 6);
            } else {
                $(this).removeClass('is-valid is-invalid');
            }
        });
    }

    // Show Alert
    function showAlert(message, type) {
        const alert = $('#loginAlert, #recoveryAlert');
        if (alert.length > 0) {
            alert.removeClass('alert-success alert-danger');
            alert.addClass('alert-' + type);
            alert.text(message);
            alert.slideDown();
            setTimeout(() => {
                alert.slideUp();
            }, 5000);
        }
    }

    // Animate numbers
    function animateNumbers() {
        $('.stat-number[data-count]').each(function() {
            const $this = $(this);
            const countTo = $this.attr('data-count');
            $({ countNum: $this.text() }).animate({
                countNum: countTo
            }, {
                duration: 2000,
                easing: 'swing',
                step: function() {
                    $this.text(Math.floor(this.countNum));
                },
                complete: function() {
                    $this.text(this.countNum);
                }
            });
        });
    }

    // FAQ Toggle
    window.toggleFaq = function(button) {
        const answer = button.nextElementSibling;
        const icon = button.querySelector('i');
        if (answer.style.display === 'block') {
            answer.style.display = 'none';
            icon.className = 'fas fa-chevron-down float-right';
        } else {
            answer.style.display = 'block';
            icon.className = 'fas fa-chevron-up float-right';
        }
    }

    // Scroll animations (com delay incremental menor)
    function checkScroll() {
        if ($('.fade-in-up').length) {
            $('.fade-in-up').each(function(i) {
                const $el = $(this);
                if ($el.hasClass('animate__animated')) return; // só anima uma vez
                const elementTop = $el.offset().top;
                const elementBottom = elementTop + $el.outerHeight();
                const viewportTop = $(window).scrollTop();
                const viewportBottom = viewportTop + $(window).height();
                if (elementBottom > viewportTop && elementTop < viewportBottom) {
                    $el.css('animation-delay', (i * 0.03) + 's'); // delay menor
                    $el.addClass('animate__animated animate__fadeInUp');
                }
            });
        }
    }

    // Navbar scroll effect
    $(window).scroll(function() {
        if ($('.main-header').length) {
            if ($(window).scrollTop() > 50) {
                $('.main-header').addClass('scrolled');
            } else {
                $('.main-header').removeClass('scrolled');
            }
        }
        checkScroll();
    });

    // Initialize
    createParticles();
    checkScroll();
    
    // Animate numbers when stats section is visible
    const statsSection = document.querySelector('.stats-section');
    if (statsSection) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateNumbers();
                    observer.unobserve(entry.target);
                }
            });
        });
        observer.observe(statsSection);
    }

    // Form submission handlers
    if ($('#loginForm').length) {
        $('#loginForm').on('submit', function(e) {
            const email = $('#email').val();
            const password = $('#password').val();
            const loginBtn = $('#loginBtn');
            const loginBtnText = $('#loginBtnText');
            // Validate
            if (!validateEmail(email)) {
                showAlert('Por favor, digite um e-mail válido.', 'danger');
                e.preventDefault();
                return;
            }
            if (password.length < 1) {
                showAlert('Por favor, digite sua senha.', 'danger');
                e.preventDefault();
                return;
            }
            // Loading state
            if (loginBtn.length > 0 && loginBtnText.length > 0) {
                loginBtn.prop('disabled', true);
                loginBtn.addClass('btn-loading');
                loginBtnText.html('<span class="spinner-border spinner-border-sm" role="status"></span>Entrando...');
            }
        });
    }
    if ($('#recoveryForm').length) {
        $('#recoveryForm').on('submit', function(e) {
            const email = $('#email').val();
            const recoveryBtn = $('#recoveryBtn');
            const recoveryBtnText = $('#recoveryBtnText');
            // Validate
            if (!validateEmail(email)) {
                showAlert('Por favor, digite um e-mail válido.', 'danger');
                e.preventDefault();
                return;
            }
            // Loading state
            if (recoveryBtn.length > 0 && recoveryBtnText.length > 0) {
                recoveryBtn.prop('disabled', true);
                recoveryBtn.addClass('btn-loading');
                recoveryBtnText.html('<span class="spinner-border spinner-border-sm" role="status"></span>Enviando...');
            }
        });
    }

   
}); 
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Seguro - Juntter</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- Checkout Styles -->
    <link href="{{ asset('css/checkout-styles.css') }}" rel="stylesheet">
    <!-- jQuery Mask Plugin -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>

<body>
    <!-- Checkout Header -->
    <header class="checkout-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-4">
                    <img src="{{ asset('img/logo/juntter_webp_640_174.webp') }}" alt="Juntter" class="checkout-logo">
                </div>
            </div>
            <div class="checkout-steps">
                <div class="step active">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Checkout</span>
                </div>
                <div class="step pending">
                    <i class="fas fa-credit-card"></i>
                    <span>Pagamento</span>
                </div>
                <div class="step pending">
                    <i class="fas fa-check-circle"></i>
                    <span>Confirmação</span>
                </div>
                <div class="security-badges">
                    <div class="security-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>SSL Seguro</span>
                    </div>
                    <div class="security-badge">
                        <i class="fas fa-lock"></i>
                        <span>Dados Protegidos</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Checkout Container -->
    <div class="checkout-container">
        <div class="row">
            <!-- Payment Form -->
            <div class="col">
                <div class="payment-card">
                    <div class="card-header">

                    </div>
                    <div class="card-body">
                        <p class="text-center text-muted">Pagamento realizado com sucesso!</p>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

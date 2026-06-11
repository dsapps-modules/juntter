<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento confirmado - Juntter</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="{{ asset('css/checkout-styles.css') }}" rel="stylesheet">
    <style>
        .payment-success-page {
            background:
                radial-gradient(circle at top left, rgba(32, 162, 82, 0.14), transparent 28%),
                radial-gradient(circle at top right, rgba(245, 196, 0, 0.16), transparent 24%),
                linear-gradient(180deg, #fbfaf6 0%, #f4efe6 100%);
        }

        .payment-success-page .checkout-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(14px);
        }

        .payment-success-page .payment-success-icon {
            background: linear-gradient(135deg, #1f9d55, #15803d);
            color: #fff;
        }

        .payment-success-page .payment-success-badge {
            background: rgba(31, 157, 85, 0.12);
            color: #166534;
        }

        .payment-success-page .payment-success-card {
            border-color: rgba(31, 157, 85, 0.16);
        }

        .payment-success-page .payment-success-item i {
            color: #1f9d55;
        }

        .payment-success-page .btn-success-primary {
            background: linear-gradient(135deg, #1f9d55, #15803d);
            border-color: transparent;
            color: #fff;
        }

        .payment-success-page .btn-success-primary:hover {
            color: #fff;
            opacity: 0.96;
        }
    </style>
</head>

<body class="payment-success-page">
    <header class="checkout-header">
        <div class="container">
            <div class="row align-items-center justify-content-between gap-3">
                <div class="col-auto">
                    <img src="{{ $sellerLogoUrl }}" alt="Juntter" class="checkout-logo"
                        onerror="this.onerror=null;this.src='/img/logo/juntter_webp_640_174.webp';">
                </div>

                <div class="col-auto">
                    <div class="checkout-steps">
                        <div class="step completed">
                            <i class="fas fa-check"></i>
                            <span>Pagamento</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="checkout-container">
        <div class="payment-success-shell">
            <section class="payment-success-card">
                <div class="payment-success-banner">
                    <div class="payment-success-icon" aria-hidden="true">
                        <i class="fas fa-circle-check"></i>
                    </div>

                    <div>
                        <div class="payment-success-badge">
                            <i class="fas fa-lock"></i>
                            Pagamento confirmado
                        </div>
                        <h1 class="payment-success-title">Obrigado pela compra</h1>
                        <p class="payment-success-lead">
                            Recebemos a confirmação do seu pagamento com sucesso.
                        </p>
                    </div>
                </div>

                <div class="payment-success-body">
                    <div class="payment-success-grid">
                        <article class="payment-success-item">
                            <i class="fas fa-thumbs-up"></i>
                            <strong>Confirmação recebida</strong>
                            <p>O pagamento foi processado e a solicitação segue para o próximo passo do fluxo.</p>
                        </article>

                        <article class="payment-success-item">
                            <i class="fas fa-clipboard-check"></i>
                            <strong>Registro atualizado</strong>
                            <p>O sistema sincronizou seus dados e todas as informações referentes ao seu pedido.</p>
                        </article>

                        <article class="payment-success-item">
                            <i class="fas fa-face-smile"></i>
                            <strong>Obrigado</strong>
                            <p>Você já pode fechar esta aba ou voltar para a loja quando quiser.</p>
                        </article>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="payment-success-actions">
                            <a href="{{ $homeUrl }}" class="btn btn-success-primary">
                                <i class="fas fa-house me-2"></i>
                                Voltar para o início
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

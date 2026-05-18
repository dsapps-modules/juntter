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
</head>

<body class="payment-success-page">
    <header class="checkout-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-4">
                    <img src="{{ $sellerLogoUrl }}" alt="Juntter" class="checkout-logo"
                        onerror="this.onerror=null;this.src='/img/logo/juntter_webp_640_174.webp';">
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
                            Recebemos a confirmação do seu pagamento com sucesso. Esta página substitui o alerta
                            temporário e serve como a confirmação final da operação.
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
                            <p>Se houver acompanhamento automático, o status será sincronizado normalmente.</p>
                        </article>

                        <article class="payment-success-item">
                            <i class="fas fa-face-smile"></i>
                            <strong>Obrigado</strong>
                            <p>Você já pode fechar esta aba ou voltar para a loja quando quiser.</p>
                        </article>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <p class="payment-success-note mb-0">
                            Se precisar de ajuda, mantenha esta confirmação como referência.
                        </p>

                        <div class="payment-success-actions">
                            <a href="{{ $homeUrl }}" class="btn btn-dark">
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

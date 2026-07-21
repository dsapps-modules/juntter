<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento não concluído - Juntter</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="{{ asset('css/checkout-styles.css') }}" rel="stylesheet">
    <style>
        .payment-error-page .payment-success-icon {
            background: linear-gradient(135deg, #f97316, #dc2626);
            color: #fff;
        }

        .payment-error-page .payment-success-badge {
            background: rgba(220, 38, 38, 0.12);
            color: #991b1b;
        }

        .payment-error-page .payment-success-card {
            border-color: rgba(220, 38, 38, 0.18);
        }

        .payment-error-page .payment-success-item i {
            color: #dc2626;
        }

        .payment-error-page .btn-error-primary {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            border-color: transparent;
            color: #fff;
        }

        .payment-error-page .btn-error-primary:hover {
            color: #fff;
            opacity: 0.96;
        }

        .checkout-brand-text {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            max-width: min(280px, calc(100vw - 40px));
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            border: 1px solid rgba(31, 26, 23, 0.1);
            background: rgba(255, 255, 255, 0.96);
            color: #1f1a17;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.02em;
            text-align: left;
            white-space: normal;
        }
    </style>
</head>

<body class="payment-success-page payment-error-page">
    @php
        $sellerBrand = $sellerBrand ?? [
            'mode' => 'logo',
            'label' => 'Juntter',
            'logoUrl' => '/img/logo/juntter_webp_640_174.webp',
        ];
    @endphp

    @php
        $retryUrl = $retryUrl ?: $homeUrl;
    @endphp

    <header class="checkout-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-4">
                    @if (($sellerBrand['mode'] ?? 'logo') === 'text')
                        <div class="checkout-brand-text" aria-label="{{ $sellerBrand['label'] }}">
                            {{ $sellerBrand['label'] }}
                        </div>
                    @else
                        <img
                            src="{{ $sellerBrand['logoUrl'] ?? '/img/logo/juntter_webp_640_174.webp' }}"
                            alt="{{ $sellerBrand['label'] ?? 'Juntter' }}"
                            class="checkout-logo"
                            onerror="this.onerror=null;this.src='/img/logo/juntter_webp_640_174.webp';">
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main class="checkout-container">
        <div class="payment-success-shell">
            <section class="payment-success-card">
                <div class="payment-success-banner">
                    <div class="payment-success-icon" aria-hidden="true">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>

                    <div>
                        <div class="payment-success-badge">
                            <i class="fas fa-shield-halved"></i>
                            Pagamento não concluído
                        </div>
                        <h1 class="payment-success-title">Não foi possível concluir o pagamento</h1>
                        <p class="payment-success-lead">
                            {{ $message }}
                        </p>
                    </div>
                </div>

                <div class="payment-success-body">
                    <div class="payment-success-grid">
                        <article class="payment-success-item">
                            <i class="fas fa-circle-exclamation"></i>
                            <strong>Falha registrada</strong>
                            <p>O processamento foi interrompido e o pagamento não foi finalizado.</p>
                        </article>

                        <article class="payment-success-item">
                            <i class="fas fa-rotate-left"></i>
                            <strong>Você pode tentar novamente</strong>
                            <p>Volte ao link de pagamento para revisar os dados e reenviar a operação.</p>
                        </article>

                        <article class="payment-success-item">
                            <i class="fas fa-headset"></i>
                            <strong>Se o problema persistir</strong>
                            <p>Entre em contato com o suporte do estabelecimento para seguir com o atendimento.</p>
                        </article>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <p class="payment-success-note mb-0">
                            Nenhuma cobrança confirmada foi exibida nesta página.
                        </p>

                        <div class="payment-success-actions">
                            <a href="{{ $retryUrl }}" class="btn btn-error-primary">
                                <i class="fas fa-rotate-left me-2"></i>
                                Tentar novamente
                            </a>
                            <a href="{{ $homeUrl }}" class="btn btn-outline-dark">
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

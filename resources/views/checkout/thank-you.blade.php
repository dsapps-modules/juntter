<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obrigado pela compra</title>
    <style>
        :root {
            --checkout-bg: #f4efe6;
            --checkout-ink: #1f1a17;
            --checkout-muted: #6d655c;
            --checkout-border: rgba(31, 26, 23, 0.1);
            --checkout-surface: rgba(255, 255, 255, 0.94);
            --checkout-shadow: 0 24px 70px rgba(46, 30, 10, 0.11);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--checkout-ink);
            background:
                radial-gradient(circle at top left, rgba(244, 196, 0, 0.16), transparent 28%),
                linear-gradient(180deg, #ffffff 0%, var(--checkout-bg) 100%);
            min-height: 100vh;
        }

        .checkout-auth-page {
            position: relative;
            min-height: 100vh;
            overflow: hidden;
            padding: 32px;
        }

        .checkout-auth-logo {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 2;
        }

        .checkout-auth-logo-image {
            display: block;
            width: 168px;
            max-width: min(168px, calc(100vw - 40px));
            height: auto;
        }

        .checkout-auth-backdrop {
            position: absolute;
            border-radius: 999px;
            filter: blur(80px);
            opacity: 0.9;
            pointer-events: none;
        }

        .checkout-auth-backdrop-left {
            top: -120px;
            left: -60px;
            width: 360px;
            height: 360px;
            background: rgba(244, 196, 0, 0.28);
        }

        .checkout-auth-backdrop-right {
            right: -80px;
            bottom: -80px;
            width: 420px;
            height: 420px;
            background: rgba(255, 255, 255, 0.9);
        }

        .checkout-card-shell {
            position: relative;
            z-index: 1;
            min-height: calc(100vh - 64px);
            display: grid;
            place-items: center;
        }

        .checkout-card {
            width: min(640px, 100%);
            background: var(--checkout-surface);
            border: 1px solid var(--checkout-border);
            box-shadow: var(--checkout-shadow);
            backdrop-filter: blur(10px);
            border-radius: 28px;
            padding: 32px;
        }

        .checkout-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #fff;
            background: #1f1a17;
        }

        h1,
        p {
            margin-top: 0;
        }

        h1 {
            margin-bottom: 12px;
            font-size: clamp(30px, 4vw, 46px);
            line-height: 1;
            letter-spacing: -0.04em;
        }

        p {
            color: var(--checkout-muted);
            line-height: 1.65;
            font-size: 16px;
        }

        .checkout-meta {
            margin-top: 20px;
            display: grid;
            gap: 10px;
        }

        .checkout-meta strong {
            color: var(--checkout-ink);
        }

        @media (max-width: 820px) {
            .checkout-auth-page {
                padding: 16px;
            }

            .checkout-auth-logo {
                top: 14px;
                right: 16px;
            }

            .checkout-auth-logo-image {
                width: 144px;
                max-width: min(144px, calc(100vw - 32px));
            }

            .checkout-card-shell {
                min-height: calc(100vh - 32px);
            }

            .checkout-card {
                padding: 24px;
                border-radius: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-auth-page">
        <div class="checkout-auth-logo">
            <img
                src="{{ $sellerLogoUrl ?? '/img/logo/juntter_webp_640_174.webp' }}"
                alt="{{ $checkoutSession->checkoutLink?->seller?->name ?? 'Juntter' }}"
                class="checkout-auth-logo-image"
                onerror="this.onerror=null;this.src='/img/logo/juntter_webp_640_174.webp';"
            >
        </div>
        <div class="checkout-auth-backdrop checkout-auth-backdrop-left" aria-hidden="true"></div>
        <div class="checkout-auth-backdrop checkout-auth-backdrop-right" aria-hidden="true"></div>

        <main class="checkout-card-shell">
            <section class="checkout-card">
                <span class="checkout-kicker">Checkout Juntter</span>
                <h1>Pagamento aprovado</h1>
                @if($order)
                    <p>Pedido: <strong>{{ $order->order_number }}</strong></p>
                    <p>Produto: <strong>{{ $order->product->name }}</strong></p>
                    <p>Valor: <strong>R$ {{ number_format((float) $order->total, 2, ',', '.') }}</strong></p>
                    <p>Método: <strong>{{ strtoupper($order->payment_method) }}</strong></p>
                @else
                    <p>Seu pagamento foi confirmado e estamos processando a entrega.</p>
                @endif
            </section>
        </main>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout indisponível</title>
    <style>
        :root {
            --checkout-bg: #f7f7f9;
            --checkout-ink: #1f1a17;
            --checkout-muted: #6d655c;
            --checkout-border: rgba(31, 26, 23, 0.1);
            --checkout-surface: rgba(255, 255, 255, 0.98);
            --checkout-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--checkout-ink);
            background: var(--checkout-bg);
            min-height: 100vh;
        }

        .checkout-auth-page {
            position: relative;
            min-height: 100vh;
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
            display: none;
        }

        .checkout-card-shell {
            position: relative;
            z-index: 1;
            min-height: calc(100vh - 64px);
            display: grid;
            place-items: center;
        }

        .checkout-card {
            width: min(520px, 100%);
            background: var(--checkout-surface);
            border: 1px solid var(--checkout-border);
            box-shadow: var(--checkout-shadow);
            border-radius: 28px;
            padding: 32px;
            text-align: center;
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
            margin: 16px 0 12px;
            font-size: clamp(28px, 4vw, 44px);
            line-height: 1;
            letter-spacing: -0.04em;
        }

        p {
            color: var(--checkout-muted);
            line-height: 1.65;
            font-size: 16px;
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
                alt="Checkout"
                class="checkout-auth-logo-image"
                onerror="this.onerror=null;this.src='/img/logo/juntter_webp_640_174.webp';"
            >
        </div>
        <div class="checkout-auth-backdrop checkout-auth-backdrop-left" aria-hidden="true"></div>
        <div class="checkout-auth-backdrop checkout-auth-backdrop-right" aria-hidden="true"></div>

        <main class="checkout-card-shell">
            <section class="checkout-card">
                <span class="checkout-kicker">Checkout Juntter</span>
                <h1>Este checkout não está disponível no momento.</h1>
                <p>{{ $message ?? 'Tente novamente mais tarde.' }}</p>
            </section>
        </main>
    </div>
</body>
</html>

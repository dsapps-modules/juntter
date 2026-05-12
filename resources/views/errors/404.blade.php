@php
    $redirectUrl = auth()->check() ? route('spa', ['any' => 'home'], false) : route('checkout', [], false);
@endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="5;url={{ $redirectUrl }}">
    <link rel="shortcut icon" href="{{ asset('img/logo/juntter_png_256.png') }}" type="image/x-icon">
    <title>Pagina nao encontrada | Juntter</title>
    <style>
        :root {
            --juntter-yellow: #ffcf00;
            --juntter-amber: #ffb800;
            --juntter-black: #000000;
            --juntter-ink: #18120a;
            --juntter-white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background:
                radial-gradient(circle at 14% 20%, rgba(255, 207, 0, 0.5), transparent 26%),
                radial-gradient(circle at 86% 16%, rgba(255, 255, 255, 0.22), transparent 22%),
                linear-gradient(135deg, var(--juntter-black) 0%, var(--juntter-amber) 68%, var(--juntter-yellow) 100%);
            color: var(--juntter-white);
            font-family: Inter, "Segoe UI", system-ui, sans-serif;
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .not-found-page {
            align-items: center;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 500px);
            gap: 56px;
            min-height: 100vh;
            padding: 48px clamp(24px, 6vw, 96px);
            position: relative;
        }

        .not-found-page::before,
        .not-found-page::after {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 999px;
            content: "";
            position: absolute;
            z-index: 0;
        }

        .not-found-page::before {
            height: 520px;
            left: -220px;
            top: -180px;
            width: 520px;
        }

        .not-found-page::after {
            bottom: -260px;
            height: 640px;
            right: -260px;
            width: 640px;
        }

        .not-found-copy,
        .not-found-visual {
            position: relative;
            z-index: 1;
        }

        .not-found-logo {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 18px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
            display: inline-flex;
            margin-bottom: 42px;
            padding: 16px 22px;
        }

        .not-found-logo img {
            display: block;
            height: 46px;
            width: auto;
        }

        .not-found-eyebrow {
            color: var(--juntter-yellow);
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 0.16em;
            margin: 0 0 18px;
            text-transform: uppercase;
        }

        h1 {
            color: var(--juntter-white);
            font-size: clamp(48px, 8vw, 96px);
            font-weight: 950;
            letter-spacing: 0;
            line-height: 1;
            margin: 0 0 24px;
            max-width: 780px;
            text-shadow: 0 8px 26px rgba(0, 0, 0, 0.3);
        }

        h1 span {
            color: var(--juntter-yellow);
            display: block;
        }

        p {
            font-size: clamp(18px, 2.2vw, 24px);
            line-height: 1.55;
            margin: 0;
            max-width: 650px;
            opacity: 0.94;
        }

        .not-found-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-top: 38px;
        }

        .not-found-button {
            background: var(--juntter-white);
            border-radius: 999px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.26);
            color: var(--juntter-ink);
            display: inline-flex;
            font-size: 18px;
            font-weight: 900;
            overflow: hidden;
            padding: 18px 34px;
            position: relative;
            text-decoration: none;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .not-found-button::before {
            background: linear-gradient(90deg, transparent, rgba(255, 207, 0, 0.35), transparent);
            content: "";
            height: 100%;
            left: -120%;
            position: absolute;
            top: 0;
            transition: left 0.45s ease;
            width: 100%;
        }

        .not-found-button:hover {
            box-shadow: 0 24px 54px rgba(0, 0, 0, 0.32);
            transform: translateY(-4px);
        }

        .not-found-button:hover::before {
            left: 120%;
        }

        .not-found-timer {
            border-left: 4px solid var(--juntter-yellow);
            color: rgba(255, 255, 255, 0.92);
            font-size: 15px;
            font-weight: 700;
            padding-left: 16px;
        }

        .not-found-visual {
            min-height: 480px;
        }

        .checkout-orbit {
            aspect-ratio: 1;
            background:
                radial-gradient(circle at center, rgba(255, 255, 255, 0.95) 0 18%, transparent 19%),
                radial-gradient(circle at center, rgba(255, 207, 0, 0.35) 0 45%, transparent 46%);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 50%;
            box-shadow: inset 0 0 80px rgba(255, 255, 255, 0.18), 0 30px 80px rgba(0, 0, 0, 0.28);
            position: relative;
            width: min(100%, 500px);
        }

        .checkout-orbit::before {
            border: 2px dashed rgba(255, 255, 255, 0.36);
            border-radius: 50%;
            content: "";
            inset: 13%;
            position: absolute;
        }

        .error-code {
            align-items: center;
            background: var(--juntter-ink);
            border: 8px solid var(--juntter-yellow);
            border-radius: 50%;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.38);
            color: var(--juntter-yellow);
            display: flex;
            font-size: clamp(64px, 9vw, 112px);
            font-weight: 950;
            inset: 27%;
            justify-content: center;
            letter-spacing: 0;
            position: absolute;
        }

        .orbit-item {
            align-items: center;
            background: rgba(255, 255, 255, 0.96);
            border-radius: 24px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.22);
            color: var(--juntter-ink);
            display: flex;
            flex-direction: column;
            font-weight: 900;
            gap: 8px;
            justify-content: center;
            min-height: 104px;
            padding: 16px;
            position: absolute;
            text-align: center;
            width: 126px;
        }

        .orbit-item strong {
            color: var(--juntter-black);
            font-size: 28px;
            line-height: 1;
        }

        .orbit-item span {
            font-size: 13px;
        }

        .orbit-item.pix {
            right: -6px;
            top: 20%;
        }

        .orbit-item.card {
            bottom: 4%;
            left: 9%;
        }

        .orbit-item.link {
            left: 2%;
            top: 8%;
        }

        .signal-line {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.75), transparent);
            border-radius: 999px;
            height: 4px;
            position: absolute;
            width: 148px;
        }

        .signal-line.one {
            right: 10%;
            top: 52%;
            transform: rotate(-18deg);
        }

        .signal-line.two {
            bottom: 24%;
            right: 24%;
            transform: rotate(38deg);
        }

        @media (max-width: 900px) {
            .not-found-page {
                grid-template-columns: 1fr;
                padding-bottom: 64px;
                padding-top: 36px;
            }

            .not-found-logo {
                margin-bottom: 28px;
            }

            .not-found-visual {
                min-height: auto;
            }

            .checkout-orbit {
                margin: 0 auto;
                max-width: 420px;
            }
        }

        @media (max-width: 560px) {
            .not-found-page {
                gap: 38px;
            }

            .not-found-actions {
                align-items: flex-start;
                flex-direction: column;
            }

            .orbit-item {
                border-radius: 18px;
                min-height: 86px;
                width: 102px;
            }

            .orbit-item strong {
                font-size: 22px;
            }

            .orbit-item span {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <main class="not-found-page">
        <section class="not-found-copy" aria-labelledby="not-found-title">
            <a class="not-found-logo" href="{{ route('checkout') }}" aria-label="Voltar para Juntter">
                <img src="{{ asset('img/logo/juntter_webp_640_174.webp') }}" alt="Juntter">
            </a>

            <p class="not-found-eyebrow">Checkout fora da rota</p>
            <h1 id="not-found-title">Esse link saiu <span>do fluxo.</span></h1>
            <p>
                A pagina que voce tentou abrir nao existe mais ou mudou de endereco.
                Em instantes, vamos levar voce de volta para um caminho seguro.
            </p>

            <div class="not-found-actions">
                <a class="not-found-button" href="{{ $redirectUrl }}">Voltar para o fluxo</a>
                <div class="not-found-timer">Redirecionamento automatico em 5 segundos.</div>
            </div>
        </section>

        <section class="not-found-visual" aria-hidden="true">
            <div class="checkout-orbit">
                <div class="error-code">404</div>
                <div class="orbit-item link">
                    <strong>URL</strong>
                    <span>nao localizada</span>
                </div>
                <div class="orbit-item pix">
                    <strong>PIX</strong>
                    <span>instantaneo</span>
                </div>
                <div class="orbit-item card">
                    <strong>18x</strong>
                    <span>checkout ativo</span>
                </div>
                <div class="signal-line one"></div>
                <div class="signal-line two"></div>
            </div>
        </section>
    </main>

    <script>
        setTimeout(function () {
            window.location.href = @json($redirectUrl);
        }, 5000);
    </script>
</body>
</html>

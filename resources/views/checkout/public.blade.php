<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $checkoutLink->name }} | Checkout Juntter</title>
    @vite(['resources/js/checkout-public.js'])
    <style>
        :root {
            --checkout-bg: #f4efe6;
            --checkout-ink: #1f1a17;
            --checkout-muted: #6d655c;
            --checkout-border: rgba(31, 26, 23, 0.1);
            --checkout-surface: rgba(255, 255, 255, 0.9);
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
                radial-gradient(circle at top left, rgba(255, 213, 128, 0.35), transparent 28%),
                radial-gradient(circle at top right, rgba(162, 126, 82, 0.12), transparent 22%),
                linear-gradient(180deg, #f9f4ea 0%, var(--checkout-bg) 100%);
            min-height: 100vh;
        }

        [hidden] {
            display: none !important;
        }

        .checkout-shell {
            max-width: 1260px;
            margin: 0 auto;
            padding: 28px 20px 56px;
        }

        .hero,
        .panel,
        .summary-card {
            background: var(--checkout-surface);
            border: 1px solid var(--checkout-border);
            box-shadow: var(--checkout-shadow);
            backdrop-filter: blur(10px);
        }

        .hero {
            border-radius: 28px;
            padding: 28px;
            margin-bottom: 24px;
        }

        .hero-top {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .eyebrow {
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
            background: {{ data_get($checkoutLink->visual_config, 'primary_color', '#1f1a17') }};
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        .hero-title {
            margin: 16px 0 10px;
            font-size: clamp(30px, 4vw, 52px);
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .hero-copy {
            margin: 0;
            max-width: 760px;
            color: var(--checkout-muted);
            line-height: 1.65;
            font-size: 16px;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 10px 14px;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(31, 26, 23, 0.08);
            font-size: 13px;
            font-weight: 600;
            color: var(--checkout-ink);
        }

        .grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.9fr);
            gap: 24px;
            align-items: start;
        }

        .panel,
        .summary-card {
            border-radius: 24px;
            padding: 22px;
        }

        .panel-stack {
            display: grid;
            gap: 18px;
        }

        .stepper {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .stepper-item {
            border: 1px solid rgba(31, 26, 23, 0.12);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.72);
            padding: 14px;
            text-align: left;
            color: var(--checkout-muted);
        }

        .stepper-item strong {
            display: block;
            font-size: 14px;
            color: var(--checkout-ink);
            margin-bottom: 4px;
        }

        .stepper-item span {
            display: block;
            font-size: 12px;
        }

        .stepper-item.is-active {
            border-color: {{ data_get($checkoutLink->visual_config, 'primary_color', '#1f1a17') }};
            box-shadow: 0 12px 24px rgba(31, 26, 23, 0.08);
        }

        .stepper-item.is-complete {
            background: rgba(19, 126, 67, 0.08);
            border-color: rgba(19, 126, 67, 0.28);
        }

        .stepper-item.is-complete strong {
            color: #0f7a43;
        }

        .form-section {
            padding: 2px 0 0;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .section-head h2 {
            margin-bottom: 4px;
            font-size: 24px;
            letter-spacing: -0.03em;
        }

        .section-head p {
            margin-bottom: 0;
            color: var(--checkout-muted);
            line-height: 1.55;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .person-switch-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-radius: 18px;
            border: 1px solid rgba(31, 26, 23, 0.08);
            background: rgba(255, 255, 255, 0.7);
            padding: 16px;
            margin-bottom: 18px;
        }

        .person-switch-copy h3 {
            margin-bottom: 4px;
            font-size: 18px;
        }

        .person-switch-copy p {
            margin-bottom: 0;
            color: var(--checkout-muted);
        }

        .person-switch {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-width: 240px;
            justify-content: flex-end;
        }

        .person-switch-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--checkout-muted);
        }

        .person-switch-track {
            position: relative;
            width: 72px;
            height: 38px;
            border-radius: 999px;
            background: rgba(31, 26, 23, 0.12);
            border: 1px solid rgba(31, 26, 23, 0.12);
            padding: 0;
        }

        .person-switch-track::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 4px 10px rgba(31, 26, 23, 0.18);
            transition: transform 0.18s ease;
        }

        .person-switch-track.is-pj {
            background: rgba(31, 26, 23, 0.88);
        }

        .person-switch-track.is-pj::after {
            transform: translateX(34px);
        }

        .person-switch-track input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .person-form {
            display: grid;
            gap: 18px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        .field--full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: 700;
            color: var(--checkout-ink);
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid rgba(31, 26, 23, 0.14);
            border-radius: 14px;
            background: #fff;
            color: var(--checkout-ink);
            padding: 13px 14px;
            font: inherit;
        }

        textarea {
            min-height: 112px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: {{ data_get($checkoutLink->visual_config, 'primary_color', '#1f1a17') }};
            box-shadow: 0 0 0 4px rgba(31, 26, 23, 0.08);
        }

        .field-error {
            min-height: 18px;
            margin: 0;
            color: #b42318;
            font-size: 12px;
        }

        .inline-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--checkout-muted);
        }

        .inline-toggle input {
            width: 18px;
            height: 18px;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .btn {
            appearance: none;
            border: 0;
            border-radius: 14px;
            padding: 14px 18px;
            font-weight: 800;
            font-size: 15px;
            cursor: pointer;
            text-align: center;
        }

        .btn-primary {
            color: #fff;
            background: {{ data_get($checkoutLink->visual_config, 'primary_color', '#1f1a17') }};
            box-shadow: 0 14px 24px rgba(31, 26, 23, 0.14);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(31, 26, 23, 0.12);
            color: var(--checkout-ink);
        }

        .btn-link {
            background: transparent;
            color: var(--checkout-muted);
            padding-inline: 0;
            text-decoration: underline;
        }

        .help-card,
        .pix-card,
        .summary-block {
            border-radius: 20px;
            border: 1px solid rgba(31, 26, 23, 0.08);
            background: rgba(255, 255, 255, 0.68);
            padding: 16px;
        }

        .checkout-logo-card {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }

        .checkout-logo-image {
            display: block;
            max-width: 100%;
            max-height: 84px;
            object-fit: contain;
        }

        .summary-title {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 18px;
        }

        .summary-title h2 {
            margin-bottom: 6px;
            font-size: 24px;
        }

        .summary-title p,
        .summary-note {
            margin-bottom: 0;
            color: var(--checkout-muted);
            line-height: 1.55;
        }

        .summary-stack {
            display: grid;
            gap: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: baseline;
            font-size: 14px;
            color: var(--checkout-muted);
        }

        .summary-row strong {
            color: var(--checkout-ink);
            text-align: right;
        }

        .summary-total {
            font-size: 20px;
            font-weight: 800;
            color: var(--checkout-ink);
            padding-top: 12px;
            border-top: 1px solid rgba(31, 26, 23, 0.08);
        }

        .summary-status {
            margin-top: 16px;
            border-radius: 16px;
            padding: 14px;
            background: rgba(31, 26, 23, 0.04);
            border: 1px solid rgba(31, 26, 23, 0.08);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .status-pill.is-success {
            color: #0f7a43;
            background: rgba(19, 126, 67, 0.1);
        }

        .status-pill.is-warn {
            color: #8a4b00;
            background: rgba(255, 153, 0, 0.14);
        }

        .status-pill.is-neutral {
            color: #3d3d3d;
            background: rgba(31, 26, 23, 0.08);
        }

        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 12px;
            background: rgba(31, 26, 23, 0.06);
            color: var(--checkout-ink);
            font-size: 13px;
            font-weight: 700;
        }

        .pix-card {
            margin-top: 18px;
        }

        .boleto-card {
            margin-top: 18px;
            background: linear-gradient(180deg, rgba(31, 26, 23, 0.02), rgba(31, 26, 23, 0.05));
            border: 1px solid rgba(31, 26, 23, 0.10);
        }

        .boleto-card__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .boleto-card__header h3 {
            margin-bottom: 4px;
            font-size: 18px;
        }

        .boleto-card__header p {
            margin: 0;
            color: var(--checkout-muted);
            font-size: 13px;
        }

        .boleto-card__grid {
            display: grid;
            gap: 10px;
            margin-bottom: 14px;
        }

        .boleto-card__row {
            display: grid;
            gap: 4px;
        }

        .boleto-card__row span {
            font-size: 13px;
            color: var(--checkout-muted);
        }

        .boleto-card__value {
            display: block;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(31, 26, 23, 0.08);
            padding: 12px 14px;
            color: var(--checkout-ink);
            word-break: break-word;
        }

        .boleto-card__value.is-link {
            display: inline-flex;
            width: fit-content;
        }

        .boleto-card__actions {
            margin-top: 0;
        }

        .pix-code {
            border-radius: 16px;
            background: #fff;
            border: 1px dashed rgba(31, 26, 23, 0.14);
            padding: 14px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            line-height: 1.65;
            white-space: pre-wrap;
            word-break: break-word;
            min-height: 116px;
        }

        .pix-qr-frame {
            display: grid;
            place-items: center;
            gap: 12px;
            border-radius: 18px;
            background: rgba(31, 26, 23, 0.03);
            border: 1px solid rgba(31, 26, 23, 0.08);
            padding: 16px;
            margin-bottom: 14px;
        }

        .pix-qr-frame svg {
            width: min(100%, 240px);
            height: auto;
            display: block;
        }

        .pix-qr-placeholder {
            color: var(--checkout-muted);
            font-size: 13px;
            text-align: center;
        }

        .pix-copy-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .pix-copy-row .btn {
            flex: 1 1 180px;
        }

        .feedback {
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 16px;
            border: 1px solid transparent;
            display: none;
        }

        .feedback.is-visible {
            display: block;
        }

        .feedback.is-error {
            background: rgba(180, 35, 24, 0.08);
            border-color: rgba(180, 35, 24, 0.16);
            color: #8b1a12;
        }

        .feedback.is-success {
            background: rgba(19, 126, 67, 0.08);
            border-color: rgba(19, 126, 67, 0.16);
            color: #0f7a43;
        }

        @media (max-width: 1080px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 820px) {
            .checkout-shell {
                padding-inline: 14px;
            }

            .hero,
            .panel,
            .summary-card {
                border-radius: 22px;
                padding: 18px;
            }

            .stepper,
            .field-grid {
                grid-template-columns: 1fr;
            }

            .stepper-item {
                min-height: 84px;
            }
        }
    </style>
</head>
<body>
@php
    $sellerLogoUrl = filled($checkoutLink->seller?->avatar_url ?? null)
        ? $checkoutLink->seller->avatar_url
        : asset('img/logo/juntter_webp_640_174.webp');

    $checkoutPublicConfig = [
        'sessionToken' => $checkoutSession->session_token,
        'publicToken' => $checkoutLink->public_token,
        'currentStep' => $checkoutSession->current_step,
        'thankYouUrl' => route('checkout.public.thank-you', $checkoutSession->session_token),
        'urls' => [
            'identification' => route('checkout.public.identification', $checkoutSession->session_token),
            'delivery' => route('checkout.public.delivery', $checkoutSession->session_token),
            'payment' => route('checkout.public.payment', $checkoutSession->session_token),
            'status' => route('checkout.public.status', $checkoutSession->session_token),
        ],
        'checkoutLink' => [
            'name' => $checkoutLink->name,
            'storeName' => data_get($checkoutLink->visual_config, 'store_name', $checkoutLink->seller->name ?? 'Checkout Juntter'),
            'primaryColor' => data_get($checkoutLink->visual_config, 'primary_color', '#1f1a17'),
            'offerMessage' => data_get($checkoutLink->visual_config, 'offer_message', 'Oferta disponível para pagamento direto no checkout.'),
            'quantity' => $checkoutLink->quantity,
            'unitPrice' => (float) $checkoutLink->unit_price,
            'totalPrice' => (float) $checkoutLink->total_price,
            'allowPix' => $checkoutLink->allow_pix,
            'allowBoleto' => $checkoutLink->allow_boleto,
            'allowCreditCard' => $checkoutLink->allow_credit_card,
        ],
        'product' => [
            'name' => $checkoutLink->product->name,
        ],
        'session' => [
            'customer_name' => $checkoutSession->customer_name,
            'customer_email' => $checkoutSession->customer_email,
            'customer_document_type' => $checkoutSession->customer_document_type,
            'customer_document' => $checkoutSession->customer_document,
            'customer_phone' => $checkoutSession->customer_phone,
            'customer_birth_date' => optional($checkoutSession->customer_birth_date)->format('Y-m-d'),
            'customer_company_name' => $checkoutSession->customer_company_name,
            'customer_state_registration' => $checkoutSession->customer_state_registration,
            'customer_is_state_registration_exempt' => (bool) $checkoutSession->customer_is_state_registration_exempt,
            'zipcode' => $checkoutSession->zipcode,
            'street' => $checkoutSession->street,
            'number' => $checkoutSession->number,
            'complement' => $checkoutSession->complement,
            'neighborhood' => $checkoutSession->neighborhood,
            'city' => $checkoutSession->city,
            'state' => $checkoutSession->state,
            'recipient_name' => $checkoutSession->recipient_name,
            'payment_method' => $checkoutSession->payment_method,
            'subtotal' => (float) $checkoutSession->subtotal,
            'discount_total' => (float) $checkoutSession->discount_total,
            'shipping_total' => (float) $checkoutSession->shipping_total,
            'total' => (float) $checkoutSession->total,
            'status' => $checkoutSession->status,
        ],
        'order' => $order ? [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'total' => (float) $order->total,
        ] : null,
        'paymentTransaction' => $paymentTransaction ? [
            'gateway_status' => $paymentTransaction->gateway_status,
            'internal_status' => $paymentTransaction->internal_status,
            'payment_method' => $paymentTransaction->payment_method,
            'pix_qr_code' => $paymentTransaction->pix_qr_code,
            'pix_copy_paste' => $paymentTransaction->pix_copy_paste,
            'pix_expires_at' => optional($paymentTransaction->pix_expires_at)->toIso8601String(),
            'boleto_url' => $paymentTransaction->boleto_url,
            'boleto_barcode' => $paymentTransaction->boleto_barcode,
            'boleto_digitable_line' => $paymentTransaction->boleto_digitable_line,
            'card_last_four' => $paymentTransaction->card_last_four,
            'card_brand' => $paymentTransaction->card_brand,
        ] : null,
    ];
@endphp

<script type="application/json" id="checkout-public-data">@json($checkoutPublicConfig)</script>

<div class="checkout-shell" id="checkout-public-app">
    <header class="hero">
        <div class="hero-top">
            <div>
                <span class="eyebrow">{{ $checkoutPublicConfig['checkoutLink']['storeName'] }}</span>
                <h1 class="hero-title">{{ $checkoutLink->name }}</h1>
                <p class="hero-copy">{{ $checkoutPublicConfig['checkoutLink']['offerMessage'] }}</p>
            </div>

            <div class="help-card checkout-logo-card" style="max-width: 320px;">
                <img src="{{ $sellerLogoUrl }}" alt="{{ $checkoutLink->seller?->name ?? 'Juntter' }}" class="checkout-logo-image">
            </div>
        </div>

    </header>

    <div class="grid">
        <section class="panel">
            <div class="stepper" aria-label="Etapas do checkout">
                <button type="button" class="stepper-item" data-step-button="identification">
                    <strong>1. Identificação</strong>
                    <span>Dados do comprador</span>
                </button>
                <button type="button" class="stepper-item" data-step-button="delivery">
                    <strong>2. Entrega</strong>
                    <span>Endereço de recebimento</span>
                </button>
                <button type="button" class="stepper-item" data-step-button="payment">
                    <strong>3. Pagamento</strong>
                    <span>Pix, boleto ou cartão</span>
                </button>
            </div>

            <div class="feedback" data-feedback></div>

            <div class="panel-stack">
                <section class="form-section" data-step-panel="identification">
                    <div class="section-head">
                        <div>
                            <h2>Identificação</h2>
                        </div>
                    </div>

                    <div class="person-switch-card">
                        <div class="person-switch-copy">
                            <h3 data-person-switch-title>Pessoa Física</h3>
                        </div>

                        <div class="person-switch">
                            <span class="person-switch-label">PF</span>
                            <div class="person-switch-track" data-person-switch-track>
                                <input id="person_type_switch" type="checkbox" role="switch" aria-label="Alternar para pessoa jurídica" data-person-type-switch>
                            </div>
                            <span class="person-switch-label">PJ</span>
                        </div>
                    </div>

                    <form id="checkout-identification-pf-form" class="person-form" data-checkout-form="identification" data-person-form="pf">
                        @csrf
                        <input type="hidden" name="customer_document_type" value="cpf">
                        <div class="field-grid">
                            <div class="field field--full">
                                <label for="customer_name_pf">Nome completo</label>
                                <input id="customer_name_pf" name="customer_name" value="{{ $checkoutSession->customer_name }}" required>
                                <p class="field-error" data-error-for="customer_name"></p>
                            </div>

                            <div class="field">
                                <label for="customer_email_pf">E-mail</label>
                                <input id="customer_email_pf" name="customer_email" type="email" value="{{ $checkoutSession->customer_email }}" required>
                                <p class="field-error" data-error-for="customer_email"></p>
                            </div>

                            <div class="field">
                                <label for="customer_phone_pf">Telefone</label>
                                <input id="customer_phone_pf" name="customer_phone" value="{{ $checkoutSession->customer_phone }}" inputmode="numeric" maxlength="15" placeholder="(11) 99999-9999" required>
                                <p class="field-error" data-error-for="customer_phone"></p>
                            </div>

                            <div class="field">
                                <label for="customer_document_pf">CPF</label>
                                <input id="customer_document_pf" name="customer_document" value="{{ $checkoutSession->customer_document }}" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" required>
                                <p class="field-error" data-error-for="customer_document"></p>
                            </div>

                            <div class="field">
                                <label for="customer_birth_date_pf">Data de nascimento</label>
                                <input id="customer_birth_date_pf" name="customer_birth_date" type="date" value="{{ optional($checkoutSession->customer_birth_date)->format('Y-m-d') }}">
                                <p class="field-error" data-error-for="customer_birth_date"></p>
                            </div>
                        </div>

                        <div class="actions">
                            <button class="btn btn-primary" type="submit">Salvar pessoa física</button>
                        </div>
                    </form>

                    <form id="checkout-identification-pj-form" class="person-form" data-checkout-form="identification" data-person-form="pj" hidden>
                        @csrf
                        <input type="hidden" name="customer_document_type" value="cnpj">
                        <div class="field-grid">
                            <div class="field field--full">
                                <label for="customer_company_name_pj">Razão social</label>
                                <input id="customer_company_name_pj" name="customer_company_name" value="{{ $checkoutSession->customer_company_name }}" required>
                                <p class="field-error" data-error-for="customer_company_name"></p>
                            </div>

                            <div class="field field--full">
                                <label for="customer_name_pj">Nome do responsável</label>
                                <input id="customer_name_pj" name="customer_name" value="{{ $checkoutSession->customer_name }}" required>
                                <p class="field-error" data-error-for="customer_name"></p>
                            </div>

                            <div class="field">
                                <label for="customer_email_pj">E-mail</label>
                                <input id="customer_email_pj" name="customer_email" type="email" value="{{ $checkoutSession->customer_email }}" required>
                                <p class="field-error" data-error-for="customer_email"></p>
                            </div>

                            <div class="field">
                                <label for="customer_phone_pj">Telefone</label>
                                <input id="customer_phone_pj" name="customer_phone" value="{{ $checkoutSession->customer_phone }}" inputmode="numeric" maxlength="15" placeholder="(11) 99999-9999" required>
                                <p class="field-error" data-error-for="customer_phone"></p>
                            </div>

                            <div class="field">
                                <label for="customer_document_pj">CNPJ</label>
                                <input id="customer_document_pj" name="customer_document" value="{{ $checkoutSession->customer_document }}" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00" required>
                                <p class="field-error" data-error-for="customer_document"></p>
                            </div>

                            <div class="field">
                                <label for="customer_state_registration_pj">Inscrição estadual</label>
                                <input id="customer_state_registration_pj" name="customer_state_registration" value="{{ $checkoutSession->customer_state_registration }}">
                                <p class="field-error" data-error-for="customer_state_registration"></p>
                            </div>

                        </div>

                        <div class="actions">
                            <button class="btn btn-primary" type="submit">Salvar pessoa jurídica</button>
                        </div>
                    </form>
                </section>

                <section class="form-section" data-step-panel="delivery" hidden>
                    <div class="section-head">
                        <div>
                            <h2>Entrega</h2>
                            <p>Informe o endereço de envio para calcular e confirmar a entrega.</p>
                        </div>
                    </div>

                    <form id="checkout-delivery-form" data-checkout-form="delivery">
                        @csrf
                        <div class="field-grid">
                            <div class="field">
                                <label for="zipcode">CEP</label>
                                <input id="zipcode" name="zipcode" value="{{ $checkoutSession->zipcode }}" inputmode="numeric" maxlength="9" placeholder="00000-000" required>
                                <p class="field-error" data-error-for="zipcode"></p>
                            </div>

                            <div class="field">
                                <label for="state">UF</label>
                                <input id="state" name="state" maxlength="2" value="{{ $checkoutSession->state }}" required>
                                <p class="field-error" data-error-for="state"></p>
                            </div>

                            <div class="field field--full">
                                <label for="street">Endereço</label>
                                <input id="street" name="street" value="{{ $checkoutSession->street }}" required>
                                <p class="field-error" data-error-for="street"></p>
                            </div>

                            <div class="field">
                                <label for="number">Número</label>
                                <input id="number" name="number" value="{{ $checkoutSession->number }}" required>
                                <p class="field-error" data-error-for="number"></p>
                            </div>

                            <div class="field">
                                <label for="complement">Complemento</label>
                                <input id="complement" name="complement" value="{{ $checkoutSession->complement }}">
                                <p class="field-error" data-error-for="complement"></p>
                            </div>

                            <div class="field">
                                <label for="neighborhood">Bairro</label>
                                <input id="neighborhood" name="neighborhood" value="{{ $checkoutSession->neighborhood }}" required>
                                <p class="field-error" data-error-for="neighborhood"></p>
                            </div>

                            <div class="field">
                                <label for="city">Cidade</label>
                                <input id="city" name="city" value="{{ $checkoutSession->city }}" required>
                                <p class="field-error" data-error-for="city"></p>
                            </div>

                            <div class="field field--full">
                                <label for="recipient_name">Destinatário</label>
                                <input id="recipient_name" name="recipient_name" value="{{ $checkoutSession->recipient_name }}" required>
                                <p class="field-error" data-error-for="recipient_name"></p>
                            </div>
                        </div>

                        <div class="actions">
                            <button class="btn btn-secondary" type="button" data-back-to="identification">Voltar</button>
                            <button class="btn btn-primary" type="submit">Salvar entrega</button>
                        </div>
                    </form>
                </section>

                <section class="form-section" data-step-panel="payment" hidden>
                    <div class="section-head">
                        <div>
                            <h2>Pagamento</h2>
                            <p>Escolha o método disponível para este link e conclua o pedido.</p>
                        </div>
                    </div>

                    <form id="checkout-payment-form" data-checkout-form="payment">
                        @csrf
                        <div class="field-grid">
                            <div class="field">
                                <label for="payment_method">Método de pagamento</label>
                                <select id="payment_method" name="payment_method" required>
                                    @if($checkoutLink->allow_pix)
                                        <option value="pix" @selected(($checkoutSession->payment_method ?? '') === 'pix')>Pix</option>
                                    @endif
                                    @if($checkoutLink->allow_boleto)
                                        <option value="boleto" @selected(($checkoutSession->payment_method ?? '') === 'boleto')>Boleto</option>
                                    @endif
                                    @if($checkoutLink->allow_credit_card)
                                        <option value="credit_card" @selected(($checkoutSession->payment_method ?? '') === 'credit_card')>Cartão de crédito</option>
                                    @endif
                                </select>
                                <p class="field-error" data-error-for="payment_method"></p>
                            </div>

                            <div class="field">
                                <label for="installments">Parcelas</label>
                                <input id="installments" name="installments" type="number" min="1" max="18" value="1">
                                <p class="field-error" data-error-for="installments"></p>
                            </div>
                        </div>

                        <div class="actions">
                            <button class="btn btn-secondary" type="button" data-back-to="delivery">Voltar</button>
                            <button class="btn btn-primary" type="submit">Gerar pagamento</button>
                        </div>
                    </form>
                </section>

                <section class="pix-card" data-step-panel="waiting" hidden>
                    <div class="section-head" style="margin-bottom: 10px;">
                        <div>
                            <h2 style="margin-bottom: 6px;">Aguardando confirmação</h2>
                            <p data-payment-message>Assim que o Pix for pago, a confirmação será atualizada automaticamente.</p>
                        </div>
                        <span class="payment-badge" data-payment-method-badge>Pix</span>
                    </div>

                    <div class="summary-block" style="margin-bottom: 14px;">
                        <div class="summary-row">
                            <span>Status</span>
                            <strong data-payment-status-text>pendente</strong>
                        </div>
                        <div class="summary-row">
                            <span>Expira em</span>
                            <strong data-pix-expiration>--</strong>
                        </div>
                    </div>

                    <div class="summary-block" data-pix-block>
                        <h3 style="margin-bottom: 10px;" data-payment-code-title>Pix copia e cola</h3>
                        <div class="pix-qr-frame" data-pix-qr-frame>
                            <div class="pix-qr-placeholder" data-pix-qr-placeholder>O QR Code do Pix será exibido aqui.</div>
                            <div data-pix-qr></div>
                        </div>
                        <div class="pix-code" data-pix-code>O código aparecerá aqui assim que o pagamento for criado.</div>
                        <div class="pix-copy-row">
                            <button class="btn btn-primary" type="button" data-copy-pix>COPIAR CÓDIGO PIX</button>
                            <a class="btn btn-secondary" href="{{ route('checkout.public.thank-you', $checkoutSession->session_token) }}" data-thank-you-link>Ver página de confirmação</a>
                        </div>
                    </div>

                    <div class="summary-note" style="margin-top: 12px;">
                        A confirmação chega via webhook do gateway. Se você voltar para esta aba, o checkout revalida o status automaticamente.
                    </div>
                </section>

                <section class="summary-block boleto-card" data-boleto-block hidden>
                    <div class="boleto-card__header">
                        <div>
                            <h3>Seu boleto</h3>
                            <p>Abrir o documento e copiar a linha digitável.</p>
                        </div>
                        <span class="payment-badge">Boleto</span>
                    </div>

                    <div class="boleto-card__grid">
                        <div class="boleto-card__row">
                            <span>Link do boleto</span>
                            <a href="#" target="_blank" rel="noreferrer" class="boleto-card__value is-link" data-boleto-url>Abrir boleto</a>
                        </div>
                        <div class="boleto-card__row">
                            <span>Código de barras</span>
                            <strong class="boleto-card__value" data-boleto-barcode>--</strong>
                        </div>
                        <div class="boleto-card__row">
                            <span>Linha digitável</span>
                            <strong class="boleto-card__value" data-boleto-digitable-line>--</strong>
                        </div>
                    </div>

                    <div class="pix-copy-row boleto-card__actions">
                        <button class="btn btn-primary" type="button" data-copy-payment>COPIAR LINHA DIGITÁVEL</button>
                    </div>
                </section>

            </div>
        </section>

        <aside class="summary-card">
            <div class="summary-title">
                <div>
                    <h2>Resumo do pedido</h2>
                </div>
            </div>

            <div class="summary-stack">
                <div class="summary-row">
                    <span>Produto</span>
                    <strong data-summary-product>{{ $checkoutLink->product->name }}</strong>
                </div>
                <div class="summary-row">
                    <span>Quantidade</span>
                    <strong data-summary-quantity>{{ $checkoutLink->quantity }}</strong>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong data-summary-subtotal>R$ {{ number_format((float) $checkoutSession->subtotal, 2, ',', '.') }}</strong>
                </div>
                <div class="summary-row">
                    <span>Desconto</span>
                    <strong data-summary-discount>R$ {{ number_format((float) $checkoutSession->discount_total, 2, ',', '.') }}</strong>
                </div>
                <div class="summary-row">
                    <span>Frete</span>
                    <strong data-summary-shipping>R$ {{ number_format((float) $checkoutSession->shipping_total, 2, ',', '.') }}</strong>
                </div>
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <strong data-summary-total>R$ {{ number_format((float) $checkoutSession->total, 2, ',', '.') }}</strong>
                </div>
            </div>

            <div class="summary-status">
                <div class="summary-row" style="margin-top: 0;">
                    <span>Etapa atual</span>
                    <strong data-summary-step>{{ ucfirst(str_replace('_', ' ', $checkoutSession->current_step)) }}</strong>
                </div>
                <div class="summary-row">
                    <span>Pagamento</span>
                    <strong data-summary-payment-method>{{ $checkoutSession->payment_method ? strtoupper($checkoutSession->payment_method) : 'Ainda não iniciado' }}</strong>
                </div>
            </div>
        </aside>
    </div>
</div>
</body>
</html>


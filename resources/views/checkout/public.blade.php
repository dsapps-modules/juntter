<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $checkoutLink->name }} | Checkout Juntter</title>
    @vite(['resources/js/checkout-public.js'])
    @if($checkoutLink->allow_credit_card)
        <script src="https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js"></script>
    @endif
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

        .checkout-auth-grid {
            position: relative;
            z-index: 1;
            min-height: calc(100vh - 64px);
        }

        [hidden] {
            display: none !important;
        }

        .checkout-shell {
            max-width: 1260px;
            margin: 0 auto;
            padding: 24px 20px 60px;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        .checkout-page-header {
            margin-bottom: 18px;
            padding-top: 0;
        }

        .checkout-page-title {
            margin: 0;
            font-size: clamp(28px, 3.4vw, 46px);
            line-height: 0.96;
            letter-spacing: -0.05em;
        }

        .grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.9fr);
            gap: 26px;
            align-items: start;
        }

        .grid--payment {
            align-items: stretch;
        }

        .grid--payment > .panel,
        .grid--payment > .summary-card {
            height: 100%;
        }

        .grid--payment > .panel {
            display: flex;
            flex-direction: column;
        }

        .grid--payment .panel-stack {
            flex: 1;
        }

        .panel,
        .summary-card {
            border-radius: 28px;
            padding: 24px;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(255, 255, 255, 0.82);
            box-shadow: var(--checkout-shadow);
            backdrop-filter: blur(10px);
        }

        .panel-stack {
            display: grid;
            gap: 20px;
        }

        .form-section {
            padding: 0;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .section-head h2 {
            margin-bottom: 6px;
            font-size: 26px;
            letter-spacing: -0.04em;
        }

        .section-head p {
            margin-bottom: 0;
            color: var(--checkout-muted);
            line-height: 1.65;
            font-size: 15px;
        }

        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .section-head--with-switch {
            align-items: center;
        }

        .person-switch {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
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
            margin-top: 18px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        .payment-method-field {
            margin-top: 18px;
        }

        .field--full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            font-weight: 700;
            color: var(--checkout-ink);
            letter-spacing: 0.01em;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid rgba(31, 26, 23, 0.12);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.92);
            color: var(--checkout-ink);
            padding: 14px 15px;
            font: inherit;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
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
            color: #a72f22;
            font-size: 12px;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .btn {
            appearance: none;
            border: 0;
            border-radius: 16px;
            padding: 14px 20px;
            font-weight: 800;
            font-size: 15px;
            cursor: pointer;
            text-align: center;
        }

        .btn-primary {
            color: #fff;
            background: {{ data_get($checkoutLink->visual_config, 'primary_color', '#1f1a17') }};
            box-shadow: 0 16px 28px rgba(31, 26, 23, 0.16);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid rgba(31, 26, 23, 0.12);
            color: var(--checkout-ink);
        }

        .help-card,
        .pix-card,
        .summary-block,
        .boleto-card,
        .boleto-card__loading {
            border-radius: 22px;
            border: 1px solid rgba(255, 255, 255, 0.82);
            background: rgba(255, 255, 255, 0.9);
            padding: 18px;
            box-shadow: var(--checkout-shadow);
            backdrop-filter: blur(10px);
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
            font-size: 26px;
            letter-spacing: -0.04em;
        }

        .summary-title p,
        .summary-note {
            margin-bottom: 0;
            color: var(--checkout-muted);
            line-height: 1.65;
            font-size: 15px;
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

        .summary-quantity-input {
            width: 76px;
            padding: 9px 10px;
            text-align: center;
        }

        .summary-quantity-control {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .summary-quantity-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: 1px solid rgba(17, 17, 17, 0.12);
            border-radius: 10px;
            background: #fff;
            color: #111111;
            font-size: 18px;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            transition: transform 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
        }

        .summary-quantity-button:hover:not(:disabled) {
            transform: translateY(-1px);
            border-color: rgba(17, 17, 17, 0.2);
            background: rgba(17, 17, 17, 0.03);
        }

        .summary-quantity-button:disabled {
            cursor: not-allowed;
            opacity: 0.45;
        }

        .summary-total {
            font-size: 20px;
            font-weight: 800;
            color: var(--checkout-ink);
            padding-top: 12px;
            border-top: 1px solid rgba(31, 26, 23, 0.08);
        }

        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 9px 13px;
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
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.82);
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
            font-size: 20px;
            letter-spacing: -0.03em;
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

        .boleto-card__loading {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 14px;
            align-items: center;
            margin-bottom: 14px;
        }

        .boleto-card__spinner {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            border: 3px solid rgba(31, 26, 23, 0.16);
            border-top-color: {{ data_get($checkoutLink->visual_config, 'primary_color', '#1f1a17') }};
            animation: boleto-spin 0.9s linear infinite;
        }

        .boleto-card__loading strong {
            display: block;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .boleto-card__loading p {
            margin: 0;
            color: var(--checkout-muted);
            font-size: 13px;
            line-height: 1.5;
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
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(31, 26, 23, 0.08);
            padding: 13px 15px;
            color: var(--checkout-ink);
            word-break: break-word;
        }

        .boleto-card__value.is-link {
            display: inline-flex;
            width: fit-content;
        }

        .boleto-card__copy-row {
            display: grid;
            gap: 8px;
        }

        .boleto-card__copy-group {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }

        .boleto-card__copy-group .boleto-card__value {
            flex: 1 1 auto;
            min-width: 0;
        }

        .boleto-card__copy-group .btn {
            align-self: stretch;
            min-height: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .pix-code {
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.94);
            border: 1px dashed rgba(31, 26, 23, 0.14);
            padding: 16px;
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

        @keyframes boleto-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .feedback {
            border-radius: 18px;
            padding: 15px 16px;
            margin-bottom: 18px;
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

            .checkout-auth-grid {
                min-height: calc(100vh - 32px);
            }

            .checkout-shell {
                padding-inline: 14px;
            }

            .hero,
            .panel,
            .summary-card {
                border-radius: 22px;
                padding: 18px;
            }

            .field-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-auth-page">
        <div class="checkout-auth-logo">
            <img
                src="{{ $sellerLogoUrl }}"
                alt="{{ $checkoutLink->seller?->name ?? $checkoutPublicConfig['checkoutLink']['storeName'] ?? 'Juntter' }}"
                class="checkout-auth-logo-image"
                onerror="this.onerror=null;this.src='/img/logo/juntter_webp_640_174.webp';"
            >
        </div>
    <div class="checkout-auth-backdrop checkout-auth-backdrop-left" aria-hidden="true"></div>
    <div class="checkout-auth-backdrop checkout-auth-backdrop-right" aria-hidden="true"></div>

@php
    $checkoutPageMode = $checkoutPageMode ?? 'details';

    $checkoutPublicConfig = [
        'sessionToken' => $checkoutSession->session_token,
        'publicToken' => $checkoutLink->public_token,
        'currentStep' => $checkoutSession->current_step,
        'thankYouUrl' => route('checkout.public.thank-you', $checkoutSession->session_token),
        'urls' => [
            'identification' => route('checkout.public.identification', $checkoutSession->session_token),
            'quantity' => route('checkout.public.quantity', $checkoutSession->session_token),
            'delivery' => route('checkout.public.delivery', $checkoutSession->session_token),
            'payment' => route('checkout.public.payment', $checkoutSession->session_token),
            'cnpjLookupTemplate' => route('checkout.public.cnpj.lookup', ['cnpj' => '__CNPJ__']),
            'antifraudAuthTemplate' => route('checkout.public.payment', $checkoutSession->session_token).'/__TRANSACTION_ID__/antifraud-auth',
            'status' => route('checkout.public.status', $checkoutSession->session_token),
        ],
        'checkoutLink' => [
            'name' => $checkoutLink->name,
            'storeName' => data_get($checkoutLink->visual_config, 'store_name', $checkoutLink->seller->name ?? 'Checkout Juntter'),
            'primaryColor' => data_get($checkoutLink->visual_config, 'primary_color', '#1f1a17'),
            'offerMessage' => data_get($checkoutLink->visual_config, 'offer_message', 'Oferta disponível para pagamento direto no checkout.'),
            'requestAddress' => $checkoutLink->request_address ?? true,
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
            'quantity' => $checkoutSession->quantity ?? $checkoutLink->quantity,
            'customer_name' => $checkoutSession->customer_name,
            'customer_email' => $checkoutSession->customer_email,
            'customer_document_type' => $checkoutSession->customer_document_type,
            'customer_document' => $checkoutSession->customer_document,
            'customer_phone' => $checkoutSession->customer_phone,
            'customer_birth_date' => optional($checkoutSession->customer_birth_date)->format('Y-m-d'),
            'customer_company_name' => $checkoutSession->customer_company_name,
            'customer_responsible_document' => $checkoutSession->customer_responsible_document,
            'customer_responsible_birth_date' => optional($checkoutSession->customer_responsible_birth_date)->format('Y-m-d'),
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
            'gateway_transaction_id' => $paymentTransaction->gateway_transaction_id,
            'pix_qr_code' => $paymentTransaction->pix_qr_code,
            'pix_copy_paste' => $paymentTransaction->pix_copy_paste,
            'pix_expires_at' => optional($paymentTransaction->pix_expires_at)->toIso8601String(),
            'boleto_url' => $paymentTransaction->boleto_url,
            'boleto_barcode' => $paymentTransaction->boleto_barcode,
            'boleto_digitable_line' => $paymentTransaction->boleto_digitable_line,
            'card_last_four' => $paymentTransaction->card_last_four,
            'card_brand' => $paymentTransaction->card_brand,
            'requires_3ds' => (bool) data_get($paymentTransaction->response_payload, 'requires_3ds', false),
            'three_ds_session_id' => data_get($paymentTransaction->response_payload, 'session_id'),
            'response_payload' => $paymentTransaction->response_payload,
        ] : null,
    ];
@endphp

@php
    $paymentMethod = strtolower((string) data_get($paymentTransaction, 'payment_method', $checkoutSession->payment_method ?? ''));
    $paymentInternalStatus = strtolower((string) data_get($paymentTransaction, 'internal_status', $checkoutSession->status ?? ''));
    $paymentIsFinalized = in_array($paymentInternalStatus, ['paid', 'authorized'], true)
        || in_array(strtolower((string) ($order?->status ?? '')), ['paid', 'authorized'], true);
    $paymentPixCopyPaste = data_get($paymentTransaction, 'pix_copy_paste') ?: data_get($paymentTransaction, 'pix_qr_code');
    $paymentPixQrImage = data_get($paymentTransaction, 'response_payload.pix_qr_code_image')
        ?: data_get($paymentTransaction, 'response_payload.api_qrcode.qrcode');
    $showPaymentSelector = $checkoutPageMode === 'payment-selector';
    $showPaymentDetails = $checkoutPageMode === 'payment-details';
    $showPixStatus = $showPaymentDetails && $paymentMethod === 'pix' && ! $paymentIsFinalized;
    $showBoletoStatus = $showPaymentDetails && $paymentMethod === 'boleto' && ! $paymentIsFinalized;
    $showCardStatus = $showPaymentDetails
        && $paymentMethod === 'credit_card'
        && $paymentTransaction !== null
        && in_array($paymentInternalStatus, ['pending', 'waiting_auth'], true)
        && ! $paymentIsFinalized;
    $showBoletoLoading = $showBoletoStatus && blank(data_get($paymentTransaction, 'boleto_url'));
@endphp

<script type="application/json" id="checkout-public-data">@json($checkoutPublicConfig)</script>

<div class="checkout-auth-grid">
    <div class="checkout-shell" id="checkout-public-app" data-3ds-env="{{ app()->environment('local') ? 'SANDBOX' : 'PROD' }}">
        <header class="checkout-page-header">
            <h1 class="checkout-page-title">{{ $checkoutLink->name }}</h1>
        </header>

        <div class="grid @if(in_array($checkoutPageMode, ['payment-selector', 'payment-details'], true)) grid--payment @endif">
            <section class="panel">
                <div class="feedback @if(session('error')) is-visible is-error @endif @if(session('success')) is-visible is-success @endif" data-feedback>
                    {{ session('error') ?? session('success') }}
                </div>

                <div class="panel-stack">
                @if($checkoutPageMode === 'details')
                <section class="form-section">
                    <div class="section-head section-head--with-switch">
                        <div>
                            <h2 data-person-type-title>Dados pessoais</h2>
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
                                <input id="customer_birth_date_pf" name="customer_birth_date" type="date" value="{{ optional($checkoutSession->customer_birth_date)->format('Y-m-d') }}" required>
                                <p class="field-error" data-error-for="customer_birth_date"></p>
                            </div>
                        </div>

                        @unless($checkoutLink->request_address ?? true)
                        <div class="actions">
                            <button class="btn btn-primary" type="submit">Continuar para pagamento</button>
                        </div>
                        @endunless

                    </form>

                    <form id="checkout-identification-pj-form" class="person-form" data-checkout-form="identification" data-person-form="pj" hidden>
                        @csrf
                        <input type="hidden" name="customer_document_type" value="cnpj">
                        <div class="field-grid">
                            <div class="field">
                                <label for="customer_document_pj">CNPJ</label>
                                <input id="customer_document_pj" name="customer_document" value="{{ $checkoutSession->customer_document }}" inputmode="numeric" maxlength="18" placeholder="00.000.000/0000-00" required>
                                <p class="field-error" data-error-for="customer_document"></p>
                            </div>

                            <div class="field">
                                <label for="customer_email_pj">E-mail</label>
                                <input id="customer_email_pj" name="customer_email" type="email" value="{{ $checkoutSession->customer_email }}" required>
                                <p class="field-error" data-error-for="customer_email"></p>
                            </div>

                            <div class="field field--full">
                                <label for="customer_company_name_pj">Nome da empresa</label>
                                <input id="customer_company_name_pj" name="customer_company_name" value="{{ $checkoutSession->customer_company_name }}" required>
                                <p class="field-error" data-error-for="customer_company_name"></p>
                            </div>

                            <div class="field field--full">
                                <label for="customer_name_pj">Nome do responsável</label>
                                <input id="customer_name_pj" name="customer_name" value="{{ $checkoutSession->customer_name }}" required>
                                <p class="field-error" data-error-for="customer_name"></p>
                            </div>

                            <div class="field">
                                <label for="customer_responsible_document_pj">CPF do responsável</label>
                                <input id="customer_responsible_document_pj" name="customer_responsible_document" value="{{ $checkoutSession->customer_responsible_document }}" inputmode="numeric" maxlength="14" placeholder="000.000.000-00" required>
                                <p class="field-error" data-error-for="customer_responsible_document"></p>
                            </div>

                            <div class="field">
                                <label for="customer_responsible_birth_date_pj">Nascimento do responsável</label>
                                <input id="customer_responsible_birth_date_pj" name="customer_responsible_birth_date" type="date" value="{{ optional($checkoutSession->customer_responsible_birth_date)->format('Y-m-d') }}" required>
                                <p class="field-error" data-error-for="customer_responsible_birth_date"></p>
                            </div>

                            <div class="field">
                                <label for="customer_phone_pj">Celular</label>
                                <input id="customer_phone_pj" name="customer_phone" value="{{ $checkoutSession->customer_phone }}" inputmode="numeric" maxlength="15" placeholder="(11) 99999-9999" required>
                                <p class="field-error" data-error-for="customer_phone"></p>
                            </div>
                        </div>

                        @unless($checkoutLink->request_address ?? true)
                        <div class="actions">
                            <button class="btn btn-primary" type="submit">Continuar para pagamento</button>
                        </div>
                        @endunless

                    </form>
                </section>

                @if($checkoutLink->request_address ?? true)
                <section class="form-section">
                    <div class="section-head">
                        <div>
                            <h2>Endereço</h2>
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
                            <input type="hidden" name="recipient_name" value="{{ $checkoutSession->recipient_name ?: $checkoutSession->customer_name }}">
                        </div>

                        <div class="actions">
                            <button class="btn btn-primary" type="submit">Continuar para pagamento</button>
                        </div>
                    </form>
                </section>
                @endif
                @endif

@if($showPaymentSelector || $showPaymentDetails)
                <section class="form-section">
                    <div class="section-head">
                        <div>
                            <h2>{{ $showPaymentDetails ? 'Pagamento' : 'Selecione o método de pagamento' }}</h2>
                        </div>
                        @if($showPaymentDetails)
                        <a href="{{ route('checkout.public.payment.page', $checkoutSession->session_token) }}">Alterar método</a>
                        @endif
                    </div>

                    <form id="{{ $showPaymentDetails ? 'checkout-payment-form' : 'checkout-payment-method-form' }}" @if($showPaymentDetails) data-checkout-form="payment" @endif method="post" action="{{ $showPaymentDetails ? route('checkout.public.payment', $checkoutSession->session_token) : route('checkout.public.payment.choose', $checkoutSession->session_token) }}" novalidate>
                        @csrf

                        @if($showPaymentSelector)
                        <div class="field-grid">
                            <div class="field payment-method-field">
                                <select id="payment_method" name="payment_method" aria-label="Método de pagamento" required>
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
                        </div>
                        @endif

                        @if($showPaymentDetails)
                            <input type="hidden" name="payment_method" value="{{ $checkoutSession->payment_method }}">

                            <div class="field-grid">
                                <div class="field" data-installments-wrapper @unless(($checkoutSession->payment_method ?? '') === 'credit_card') hidden @endunless>
                                    <label for="installments">Parcelas</label>
                                    <input id="installments" name="installments" type="number" min="1" max="18" value="1" @unless(($checkoutSession->payment_method ?? '') === 'credit_card') disabled @endunless required>
                                    <p class="field-error" data-error-for="installments"></p>
                                </div>
                            </div>

                            @if($checkoutLink->allow_credit_card)
                            <div class="field-grid" data-card-fields-wrapper @unless(($checkoutSession->payment_method ?? '') === 'credit_card') hidden @endunless>
                                <div class="field field--full">
                                    <label for="card_holder_name">Nome no cartão</label>
                                    <input id="card_holder_name" name="card[holder_name]" value="" autocomplete="cc-name" @unless(($checkoutSession->payment_method ?? '') === 'credit_card') disabled @endunless required>
                                    <p class="field-error" data-error-for="card.holder_name"></p>
                                </div>

                                <div class="field field--full">
                                    <label for="card_holder_document">Documento do titular</label>
                                    <input id="card_holder_document" name="card[holder_document]" value="" inputmode="numeric" maxlength="18" placeholder="CPF/CNPJ" autocomplete="off" @unless(($checkoutSession->payment_method ?? '') === 'credit_card') disabled @endunless required>
                                    <p class="field-error" data-error-for="card.holder_document"></p>
                                </div>

                                <div class="field field--full">
                                    <label for="card_number">Número do cartão</label>
                                    <input id="card_number" name="card[card_number]" value="" inputmode="numeric" maxlength="19" placeholder="0000 0000 0000 0000" autocomplete="cc-number" @unless(($checkoutSession->payment_method ?? '') === 'credit_card') disabled @endunless required>
                                    <p class="field-error" data-error-for="card.card_number"></p>
                                </div>

                                <div class="field">
                                    <label for="card_expiration_month">Validade - mês</label>
                                    <input id="card_expiration_month" name="card[expiration_month]" type="number" min="1" max="12" value="" inputmode="numeric" placeholder="MM" autocomplete="cc-exp-month" @unless(($checkoutSession->payment_method ?? '') === 'credit_card') disabled @endunless required>
                                    <p class="field-error" data-error-for="card.expiration_month"></p>
                                </div>

                                <div class="field">
                                    <label for="card_expiration_year">Validade - ano</label>
                                    <input id="card_expiration_year" name="card[expiration_year]" type="number" min="{{ now()->year }}" max="2099" value="" inputmode="numeric" placeholder="AAAA" autocomplete="cc-exp-year" @unless(($checkoutSession->payment_method ?? '') === 'credit_card') disabled @endunless required>
                                    <p class="field-error" data-error-for="card.expiration_year"></p>
                                </div>

                                <div class="field">
                                    <label for="card_security_code">CVV</label>
                                    <input id="card_security_code" name="card[security_code]" value="" inputmode="numeric" maxlength="4" placeholder="123" autocomplete="cc-csc" @unless(($checkoutSession->payment_method ?? '') === 'credit_card') disabled @endunless required>
                                    <p class="field-error" data-error-for="card.security_code"></p>
                                </div>
                            </div>
                            @endif
                        @endif

                        @if(!($showPaymentDetails && in_array($paymentMethod, ['pix', 'boleto'], true)))
                        <div class="actions">
                            <button class="btn btn-primary" type="submit">
                                @if($showPaymentDetails && $paymentMethod === 'boleto')
                                    Gerar boleto
                                @else
                                    Pagar
                                @endif
                            </button>
                        </div>
                        @endif
                    </form>
                </section>
                @endif

                @if($showCardStatus)
                <section class="pix-card" data-step-panel="card-status">
                    <div class="section-head" style="margin-bottom: 10px;">
                        <div>
                            <h2 style="margin-bottom: 6px;">Pagamento</h2>
                            <p data-payment-message>Pagamento em processamento</p>
                        </div>
                        <span class="payment-badge" data-payment-method-badge>Cartão</span>
                    </div>
                </section>
                @endif

                @if($showPixStatus)
                <section class="pix-card" data-step-panel="waiting">
                    <div class="section-head" style="margin-bottom: 10px;">
                        <div>
                            <h2 style="margin-bottom: 6px;">Aguardando confirmação</h2>
                        </div>
                        <span class="payment-badge" data-payment-method-badge>Pix</span>
                    </div>

                    <div class="summary-block" style="margin-bottom: 14px;">
                        <div class="summary-row">
                            <span>Status</span>
                            <strong data-payment-status-text>Pendente</strong>
                        </div>
                        <div class="summary-row">
                            <span>Expira em</span>
                            <strong data-pix-expiration>--</strong>
                        </div>
                    </div>

                    <div class="summary-block" data-pix-block>
                        <h3 style="margin-bottom: 10px;" data-payment-code-title>Escaneie o QR Code ou copie o código Pix</h3>
                        <div class="pix-qr-frame" data-pix-qr-frame>
                            <div class="pix-qr-placeholder" data-pix-qr-placeholder @if(filled($paymentPixQrImage) || filled($paymentPixCopyPaste)) hidden @endif>O QR Code do Pix será exibido aqui.</div>
                            <div data-pix-qr>
                                @if(filled($paymentPixQrImage))
                                    <img src="{{ $paymentPixQrImage }}" alt="QR Code Pix" style="width: 240px; height: 240px; object-fit: contain;">
                                @endif
                            </div>
                        </div>
                        <div class="pix-code" data-pix-code>{{ $paymentPixCopyPaste ?: 'O código aparecerá aqui assim que o pagamento for criado.' }}</div>
                        <div class="pix-copy-row">
                            <button class="btn btn-primary" type="button" data-copy-pix>COPIAR CÓDIGO PIX</button>
                        </div>
                    </div>
                </section>
                @endif

                @if($showBoletoStatus)
                <section class="summary-block boleto-card" data-boleto-block>
                    <div class="boleto-card__header">
                        <div>
                            <h3>Seu boleto</h3>
                        </div>
                    </div>

                    <div class="boleto-card__loading" data-boleto-loading @unless($showBoletoLoading) hidden @endunless aria-live="polite">
                        <div class="boleto-card__spinner" aria-hidden="true"></div>
                        <div>
                            <strong>Gerando boleto...</strong>
                            <p>Os dados do boleto aparecerão automaticamente assim que estiverem disponíveis.</p>
                        </div>
                    </div>

                    <div class="boleto-card__grid" data-boleto-grid @if($showBoletoLoading) hidden @endif>
                        <div class="boleto-card__row boleto-card__copy-row">
                            <span>Linha digitável</span>
                            <div class="boleto-card__copy-group">
                                <strong class="boleto-card__value" data-boleto-digitable-line>--</strong>
                                <button class="btn btn-secondary" type="button" data-copy-boleto-digitable-line>Copiar</button>
                            </div>
                        </div>
                        <div class="boleto-card__row boleto-card__copy-row">
                            <span>Código de barras</span>
                            <div class="boleto-card__copy-group">
                                <strong class="boleto-card__value" data-boleto-barcode>--</strong>
                                <button class="btn btn-secondary" type="button" data-copy-boleto-barcode>Copiar</button>
                            </div>
                        </div>
                        <div class="boleto-card__row boleto-card__copy-row">
                            <span>Pix (copia e cola)</span>
                            <div class="boleto-card__copy-group">
                                <strong class="boleto-card__value" data-boleto-pix-copy-paste>--</strong>
                                <button class="btn btn-secondary" type="button" data-copy-boleto-pix-copy-paste>Copiar</button>
                            </div>
                        </div>
                    </div>

                    <div class="pix-copy-row">
                        <button class="btn btn-primary" type="button" data-open-payment @if($showBoletoLoading) disabled @endif>
                            @if($showBoletoLoading)
                                AGUARDANDO BOLETO...
                            @else
                                ABRIR BOLETO
                            @endif
                        </button>
                    </div>
                </section>
                @endif

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
                    <div class="summary-quantity-control" data-summary-quantity-control>
                        <button
                            type="button"
                            class="summary-quantity-button"
                            data-summary-quantity-decrement
                            aria-label="Diminuir quantidade"
                        >
                            -
                        </button>
                        <input
                            data-summary-quantity-input
                            class="summary-quantity-input"
                            type="number"
                            name="quantity"
                            min="1"
                            step="1"
                            inputmode="numeric"
                            value="{{ $checkoutSession->quantity ?? $checkoutLink->quantity }}"
                        >
                        <button
                            type="button"
                            class="summary-quantity-button"
                            data-summary-quantity-increment
                            aria-label="Aumentar quantidade"
                        >
                            +
                        </button>
                    </div>
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

        </aside>
    </div>
</div>
<script>
(function () {
    const personTypeSwitch = document.querySelector('[data-person-type-switch]');
    const personTypeSwitchTrack = document.querySelector('[data-person-switch-track]');
    const personTypeTitle = document.querySelector('[data-person-type-title]');
    const pfForm = document.querySelector('[data-person-form="pf"]');
    const pjForm = document.querySelector('[data-person-form="pj"]');

    if (!personTypeSwitch || !personTypeSwitchTrack || !personTypeTitle || !pfForm || !pjForm) {
        return;
    }

    const syncPersonTypeUi = () => {
        const isPj = personTypeSwitch.checked;

        personTypeSwitchTrack.classList.toggle('is-pj', isPj);
        personTypeTitle.textContent = isPj ? 'Dados da empresa' : 'Dados pessoais';
        pfForm.hidden = isPj;
        pjForm.hidden = !isPj;
    };

    let lastKnownPersonType = personTypeSwitch.checked ? 'pj' : 'pf';

    const reconcilePersonTypeUi = () => {
        const nextPersonType = personTypeSwitch.checked ? 'pj' : 'pf';

        if (nextPersonType === lastKnownPersonType) {
            return;
        }

        lastKnownPersonType = nextPersonType;
        syncPersonTypeUi();
    };

    personTypeSwitch.addEventListener('change', syncPersonTypeUi);
    personTypeSwitch.addEventListener('click', () => window.setTimeout(reconcilePersonTypeUi, 0));
    personTypeSwitchTrack.addEventListener('click', () => window.setTimeout(reconcilePersonTypeUi, 0));
    syncPersonTypeUi();
})();
</script>
</div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $checkoutLink->name }} | Checkout Juntter</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 32px 20px 48px; }
        .grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 24px; align-items: start; }
        .card { background: #fff; border-radius: 18px; box-shadow: 0 10px 30px rgba(15,23,42,.08); padding: 24px; }
        .badge { display: inline-flex; border-radius: 999px; background: {{ data_get($checkoutLink->visual_config, 'primary_color', '#111827') }}; color: #fff; padding: 8px 12px; font-size: 12px; font-weight: 700; }
        label { display: block; margin-top: 14px; font-size: 14px; font-weight: 700; }
        input, select, textarea { width: 100%; margin-top: 6px; padding: 12px 14px; border: 1px solid #d1d5db; border-radius: 12px; box-sizing: border-box; }
        button { margin-top: 16px; width: 100%; border: 0; border-radius: 12px; padding: 14px 16px; font-weight: 700; color: #fff; background: {{ data_get($checkoutLink->visual_config, 'primary_color', '#111827') }}; cursor: pointer; }
        .muted { color: #6b7280; }
        .summary-row { display: flex; justify-content: space-between; gap: 12px; margin-top: 10px; }
        .summary-row strong { white-space: nowrap; }
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card" style="margin-bottom: 24px;">
        <span class="badge">{{ data_get($checkoutLink->visual_config, 'store_name', $checkoutLink->seller->name ?? 'Checkout Juntter') }}</span>
        <h1 style="margin: 16px 0 8px;">{{ $checkoutLink->name }}</h1>
        <p class="muted" style="margin: 0;">{{ data_get($checkoutLink->visual_config, 'offer_message', 'Oferta disponível para pagamento direto no checkout.') }}</p>
    </div>

    <div class="grid">
        <div class="card">
            <h2 style="margin-top: 0;">1. Identificação</h2>
            <form method="POST" action="{{ route('checkout.public.identification', $checkoutSession->session_token) }}">
                @csrf
                <label>Nome completo</label>
                <input name="customer_name" value="{{ $checkoutSession->customer_name }}" required>
                <label>E-mail</label>
                <input name="customer_email" type="email" value="{{ $checkoutSession->customer_email }}" required>
                <label>Tipo de documento</label>
                <select name="customer_document_type" required>
                    <option value="cpf">CPF</option>
                    <option value="cnpj">CNPJ</option>
                </select>
                <label>Documento</label>
                <input name="customer_document" value="{{ $checkoutSession->customer_document }}" required>
                <label>Telefone</label>
                <input name="customer_phone" value="{{ $checkoutSession->customer_phone }}" required>
                <label>Data de nascimento</label>
                <input name="customer_birth_date" type="date" value="{{ optional($checkoutSession->customer_birth_date)->format('Y-m-d') }}">
                <label>Razão social</label>
                <input name="customer_company_name" value="{{ $checkoutSession->customer_company_name }}">
                <label>Inscrição estadual</label>
                <input name="customer_state_registration" value="{{ $checkoutSession->customer_state_registration }}">
                <label style="display:flex; gap:10px; align-items:center;">
                    <input type="checkbox" name="customer_is_state_registration_exempt" value="1" style="width:auto;"> Isento
                </label>
                <button type="submit">Salvar identificação</button>
            </form>

            <h2>2. Entrega</h2>
            <form method="POST" action="{{ route('checkout.public.delivery', $checkoutSession->session_token) }}">
                @csrf
                <label>CEP</label>
                <input name="zipcode" value="{{ $checkoutSession->zipcode }}" required>
                <label>Endereço</label>
                <input name="street" value="{{ $checkoutSession->street }}" required>
                <label>Número</label>
                <input name="number" value="{{ $checkoutSession->number }}" required>
                <label>Complemento</label>
                <input name="complement" value="{{ $checkoutSession->complement }}">
                <label>Bairro</label>
                <input name="neighborhood" value="{{ $checkoutSession->neighborhood }}" required>
                <label>Cidade</label>
                <input name="city" value="{{ $checkoutSession->city }}" required>
                <label>UF</label>
                <input name="state" maxlength="2" value="{{ $checkoutSession->state }}" required>
                <label>Destinatário</label>
                <input name="recipient_name" value="{{ $checkoutSession->recipient_name }}" required>
                <button type="submit">Salvar entrega</button>
            </form>

            <h2>3. Pagamento</h2>
            <form method="POST" action="{{ route('checkout.public.payment', $checkoutSession->session_token) }}">
                @csrf
                <label>Método de pagamento</label>
                <select name="payment_method" required>
                    @if($checkoutLink->allow_pix)
                        <option value="pix">Pix</option>
                    @endif
                    @if($checkoutLink->allow_boleto)
                        <option value="boleto">Boleto</option>
                    @endif
                    @if($checkoutLink->allow_credit_card)
                        <option value="credit_card">Cartão de crédito</option>
                    @endif
                </select>
                <label>Parcelas</label>
                <input name="installments" type="number" min="1" max="18" value="1">
                <button type="submit">Finalizar compra</button>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-top: 0;">Resumo</h2>
            <div class="summary-row"><span>Produto</span><strong>{{ $checkoutLink->product->name }}</strong></div>
            <div class="summary-row"><span>Quantidade</span><strong>{{ $checkoutLink->quantity }}</strong></div>
            <div class="summary-row"><span>Subtotal</span><strong>R$ {{ number_format((float) $checkoutSession->subtotal, 2, ',', '.') }}</strong></div>
            <div class="summary-row"><span>Desconto</span><strong>R$ {{ number_format((float) $checkoutSession->discount_total, 2, ',', '.') }}</strong></div>
            <div class="summary-row"><span>Frete</span><strong>R$ {{ number_format((float) $checkoutSession->shipping_total, 2, ',', '.') }}</strong></div>
            <div class="summary-row" style="font-size: 18px;"><span>Total</span><strong>R$ {{ number_format((float) $checkoutSession->total, 2, ',', '.') }}</strong></div>
            <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 20px 0;">
            <p class="muted" style="margin: 0;">Token da sessão: {{ $checkoutSession->session_token }}</p>
        </div>
    </div>
</div>
</body>
</html>

@props(['link', 'paymentSummary' => null])

@php
    $paymentSummary = is_array($paymentSummary) ? $paymentSummary : [];
    $totalAmountFormatted = $paymentSummary['total_amount_formatted'] ?? $link->valor_formatado;
    $baseAmountFormatted = $paymentSummary['base_amount_formatted'] ?? $link->valor_formatado;
    $feeAmountFormatted = $paymentSummary['fee_amount_formatted'] ?? 'R$ 0,00';
    $feeAmountCents = (int) ($paymentSummary['fee_amount_cents'] ?? 0);
@endphp

<div class="col-lg-4">
    <div class="order-summary">
        <div class="order-summary-header">
            <i class="fas fa-receipt me-2"></i>
            Resumo do Pedido
        </div>
        <div class="order-summary-body">
            <div class="order-item">
                <span class="order-item-label">Produto/Servico</span>
                <span class="order-item-value">{{ $link->descricao ?: 'Pagamento' }}</span>
            </div>
            <div class="order-item">
                <span class="order-item-label">Valor</span>
                <span class="order-item-value">{{ $baseAmountFormatted }}</span>
            </div>
            @if ($link->tipo_pagamento === 'PIX' && $link->juros === 'CLIENT' && $feeAmountCents > 0)
                <div class="order-item">
                    <span class="order-item-label">Taxa do Pix</span>
                    <span class="order-item-value">{{ $feeAmountFormatted }}</span>
                </div>
            @endif
            @if ($link->tipo_pagamento === 'CARTAO' && $link->parcelas_maximas > 1)
                <div class="order-item">
                    <span class="order-item-label">Parcelamento</span>
                    <span class="order-item-value">Ate {{ $link->parcelas_maximas }}x</span>
                </div>
            @endif
            <div class="order-item">
                <span class="order-item-label">Forma de Pagamento</span>
                <span class="order-item-value">
                    @if ($link->tipo_pagamento === 'PIX')
                        <i class="fas fa-qrcode me-1"></i>PIX
                    @elseif($link->tipo_pagamento === 'BOLETO')
                        <i class="fas fa-file-invoice me-1"></i>Boleto
                    @else
                        <i class="fas fa-credit-card me-1"></i>Cartao
                    @endif
                </span>
            </div>

            @if ($link->tipo_pagamento !== 'CARTAO')
                <div class="order-total">
                    <div class="order-item">
                        <span class="order-item-label">Total</span>
                        <span class="order-item-value">{{ $totalAmountFormatted }}</span>
                    </div>
                </div>
            @endif

            <div class="mt-3 text-center">
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    Pagamento 100% seguro e criptografado
                </small>
            </div>
        </div>
    </div>
</div>

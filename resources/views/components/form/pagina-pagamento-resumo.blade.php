@props(['link'])

<div class="col-lg-4">
    <div class="order-summary">
        <div class="order-summary-header">
            <i class="fas fa-receipt me-2"></i>
            Resumo do Pedido
        </div>
        <div class="order-summary-body">
            <div class="order-item">
                <span class="order-item-label">Produto/Serviço</span>
                <span class="order-item-value">{{ $link->descricao ?: 'Pagamento' }}</span>
            </div>
            <div class="order-item">
                <span class="order-item-label">Valor</span>
                <span class="order-item-value">{{ $link->valor_formatado }}</span>
            </div>
            @if ($link->tipo_pagamento === 'CREDIT' && $link->parcelas > 1)
                <div class="order-item">
                    <span class="order-item-label">Parcelamento</span>
                    <span class="order-item-value">Até {{ $link->parcelas }}x</span>
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
                        <i class="fas fa-credit-card me-1"></i>Cartão
                    @endif
                </span>
            </div>

            <div class="order-total">
                <div class="order-item">
                    <span class="order-item-label">Total</span>
                    <span class="order-item-value">{{ $link->valor_formatado }}</span>
                </div>
            </div>

            <!-- Security Info -->
            <div class="mt-3 text-center">
                <small class="text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    Pagamento 100% seguro e criptografado
                </small>
            </div>
        </div>
    </div>
</div>

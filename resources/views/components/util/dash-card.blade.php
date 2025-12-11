@props(['amount', 'label', 'icon' => '', 'iconClass' => ''])

<div class="saldo-card fade-in-up" data-delay="0.1s">
    <div class="saldo-content">
        <div class="saldo-valor">{{ $amount ?? 'R$ 0,00' }}</div>
        <div class="saldo-label">
            <i class="fas fa-info-circle me-1"></i>
            {{ $label }}
        </div>
    </div>
    <div class="saldo-icon {{ $iconClass }}">
        <i class="{{ $icon }}"></i>
    </div>
</div>

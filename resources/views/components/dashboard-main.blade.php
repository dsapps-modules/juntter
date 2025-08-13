@props([
  'title' => 'Dashboard',
  'saldos' => [],
  'metricas' => [],
  'metricasGeral' => null,
  'metricasCartao' => null,
  'metricasBoleto' => null,
  'breadcrumbItems' => [],
  'showSaldos' => true,
  'rightSub' => null
])

<!-- Breadcrumb -->
<x-breadcrumb :items="$breadcrumbItems" :rightSub="$rightSub" />


@if($showSaldos)
    <!-- Saldo Cards -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="saldo-card saldo-disponivel fade-in-up" data-delay="0.1s">
                <div class="saldo-content">
                    <div class="saldo-valor">{{ $saldos['disponivel'] ?? 'R$ 0,00' }}</div>
                    <div class="saldo-label">
                        <i class="fas fa-info-circle me-1"></i>
                        Lançamentos Futuros
                    </div>
                </div>
                <div class="saldo-icon">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="saldo-card saldo-transito fade-in-up" data-delay="0.2s">
                <div class="saldo-content">
                    <div class="saldo-valor">{{ $saldos['transito'] ?? 'R$ 0,00' }}</div>
                    <div class="saldo-label">
                        <i class="fas fa-info-circle me-1"></i>
                        Saldo em trânsito
                    </div>
                </div>
                <div class="saldo-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="saldo-card saldo-processamento fade-in-up" data-delay="0.3s">
                <div class="saldo-content">
                    <div class="saldo-valor">{{ $saldos['processamento'] ?? 'R$ 0,00' }}</div>
                    <div class="saldo-label">
                        <i class="fas fa-info-circle me-1"></i>
                        Em Processamento
                    </div>
                </div>
                <div class="saldo-icon">
                    <i class="fas fa-spinner"></i>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Segunda linha: 2 cards -->
    <div class="row justify-content-center">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="saldo-card saldo-bloqueado fade-in-up" data-delay="0.4s">
                <div class="saldo-content">
                    <div class="saldo-valor">{{ $saldos['bloqueado_cartao'] ?? 'R$ 0,00' }}</div>
                    <div class="saldo-label">
                        <i class="fas fa-info-circle me-1"></i>
                        Bloqueado: cartão
                    </div>
                </div>
                <div class="saldo-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-3">
            <div class="saldo-card saldo-bloqueado-boleto fade-in-up" data-delay="0.5s">
                <div class="saldo-content">
                    <div class="saldo-valor">{{ $saldos['bloqueado_boleto'] ?? 'R$ 0,00' }}</div>
                    <div class="saldo-label">
                        <i class="fas fa-info-circle me-1"></i>
                        Bloqueado: boleto
                    </div>
                </div>
                <div class="saldo-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Dashboard Analytics Card -->
<div class="row">
    <div class="col-12">
        <div class="analytics-card">
            <!-- Tabs Navigation -->
            <div class="analytics-tabs">
                <button class="tab-btn active" onclick="switchTab('geral')">Geral</button>
                <button class="tab-btn" onclick="switchTab('cartao')">Cartão</button>
                <button class="tab-btn" onclick="switchTab('boleto')">Boleto</button>
            </div>
            
            <!-- Metrics Grid por abas -->

            <div id="tab-content-geral" class="metrics-grid">
                @foreach(($metricasGeral ?? $metricas ?? []) as $metrica)
                    <div class="metric-card">
                        <div class="metric-value">{{ $metrica['valor'] }}</div>
                        <div class="metric-label">
                            <i class="fas fa-info-circle me-1"></i>
                            {{ $metrica['label'] }}
                        </div>
                        <div class="metric-icon {{ $metrica['cor'] }}">
                            <i class="{{ $metrica['icone'] }}"></i>
                        </div>
                    </div>
                @endforeach
            </div>

            <div id="tab-content-cartao" class="metrics-grid" style="display:none;">
                @forelse(($metricasCartao ?? []) as $metrica)
                    <div class="metric-card">
                        <div class="metric-value">{{ $metrica['valor'] }}</div>
                        <div class="metric-label">
                            <i class="fas fa-info-circle me-1"></i>
                            {{ $metrica['label'] }}
                        </div>
                        <div class="metric-icon {{ $metrica['cor'] }}">
                            <i class="{{ $metrica['icone'] }}"></i>
                        </div>
                    </div>
                @empty
                    <div class="text-muted p-3">Sem métricas de cartão.</div>
                @endforelse
            </div>

            <div id="tab-content-boleto" class="metrics-grid" style="display:none;">
                @forelse(($metricasBoleto ?? []) as $metrica)
                    <div class="metric-card">
                        <div class="metric-value">{{ $metrica['valor'] }}</div>
                        <div class="metric-label">
                            <i class="fas fa-info-circle me-1"></i>
                            {{ $metrica['label'] }}
                        </div>
                        <div class="metric-icon {{ $metrica['cor'] }}">
                            <i class="{{ $metrica['icone'] }}"></i>
                        </div>
                    </div>
                @empty
                    <div class="text-muted p-3">Sem métricas de boleto.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
  window.switchTab = function(tab){
    const map = {
      'geral':  {btn: '.analytics-tabs .tab-btn:nth-child(1)', div: '#tab-content-geral'},
      'cartao': {btn: '.analytics-tabs .tab-btn:nth-child(2)', div: '#tab-content-cartao'},
      'boleto': {btn: '.analytics-tabs .tab-btn:nth-child(3)', div: '#tab-content-boleto'}
    };
    // esconder todos
    ['#tab-content-geral','#tab-content-cartao','#tab-content-boleto'].forEach(id=>$(id).hide());
    $('.analytics-tabs .tab-btn').removeClass('active');
    // mostrar o selecionado
    const cfg = map[tab] || map.geral;
    $(cfg.div).show();
    $(cfg.btn).addClass('active');
  };
  // garantir estado inicial
  $(function(){ switchTab('geral'); });
</script>
@endpush


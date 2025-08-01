<!-- Breadcrumb -->
<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent p-0 mb-0">
                <li class="breadcrumb-item">
                    <a href="#" class="text-primary text-decoration-none">Juntter</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="#" class="text-primary text-decoration-none">Cobrança</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Dashboard Title -->
<div class="row mb-4">
    <div class="col-12">
        <h1 class="dashboard-title fw-bold mb-0">{{ $title ?? 'Dashboard' }}</h1>
    </div>
</div>

<!-- Saldo Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="saldo-card saldo-disponivel fade-in-up" data-delay="0.1s">
            <div class="saldo-content">
                <div class="saldo-valor">{{ $saldos['disponivel'] ?? 'R$ 0,00' }}</div>
                <div class="saldo-label">
                    <i class="fas fa-info-circle me-1"></i>
                    Saldo Disponível
                </div>
            </div>
            <div class="saldo-icon">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
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
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="saldo-card saldo-bloqueado fade-in-up" data-delay="0.3s">
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
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="saldo-card saldo-bloqueado-boleto fade-in-up" data-delay="0.4s">
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
            
            <!-- Metrics Grid -->
            <div class="metrics-grid">
                @foreach($metricas as $metrica)
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
        </div>
    </div>
</div>


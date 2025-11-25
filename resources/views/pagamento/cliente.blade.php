<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Seguro - Juntter</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- Checkout Styles -->
    <link href="{{ asset('css/checkout-styles.css') }}" rel="stylesheet">
    <!-- jQuery Mask Plugin -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
</head>

<body>
    <!-- Checkout Header -->
    <header class="checkout-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-4">
                    <img src="{{ asset('img/logo/juntter_webp_640_174.webp') }}" alt="Juntter" class="checkout-logo">
                </div>
            </div>
            <div class="checkout-steps">
                <div class="step active">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Checkout</span>
                </div>
                <div class="step pending">
                    <i class="fas fa-credit-card"></i>
                    <span>Pagamento</span>
                </div>
                <div class="step pending">
                    <i class="fas fa-check-circle"></i>
                    <span>Confirmação</span>
                </div>
                <div class="security-badges">
                    <div class="security-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>SSL Seguro</span>
                    </div>
                    <div class="security-badge">
                        <i class="fas fa-lock"></i>
                        <span>Dados Protegidos</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Checkout Container -->
    <div class="checkout-container">
        <div class="row">
            <!-- Payment Form -->
            <div class="col-lg-8">
                <div class="payment-card">
                    <div class="card-header">
                        <div class="payment-header">
                            <div class="payment-icon">
                                @if ($link->tipo_pagamento === 'PIX')
                                    <i class="fas fa-qrcode"></i>
                                @elseif($link->tipo_pagamento === 'BOLETO')
                                    <i class="fas fa-file-invoice"></i>
                                @else
                                    <i class="fas fa-credit-card"></i>
                                @endif
                            </div>
                            <h1 class="payment-title">
                                @if ($link->tipo_pagamento === 'PIX')
                                    Pagamento PIX
                                @elseif($link->tipo_pagamento === 'BOLETO')
                                    Pagamento Boleto
                                @else
                                    Pagamento com Cartão
                                @endif
                            </h1>
                            @if ($link->descricao)
                                <p class="payment-subtitle">{{ $link->descricao }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">

                        <!-- Formulário baseado no tipo de pagamento -->
                        @if ($link->tipo_pagamento === 'PIX')
                            <!-- Formulário PIX -->
                            <form id="pixForm">
                                @csrf
                                <!-- QR Code PIX -->
                                <div class="pix-qr-container" id="pixContainer">
                                    <div class="row">
                                        <div class="col-md-6 text-center">
                                            <h6 class="fw-bold mb-3">QR Code</h6>
                                            <div id="qrcode-container" class="mb-3">
                                                <div class="pix-qr-code">
                                                    <i class="fas fa-qrcode"></i>
                                                </div>
                                            </div>
                                            <button class="btn btn-outline-primary btn-sm" onclick="downloadQrCode()"
                                                id="downloadBtn" style="display: none;">
                                                <i class="fas fa-download me-2"></i>
                                                Baixar QR Code
                                            </button>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="fw-bold mb-3">Copia e Cola</h6>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Código PIX</label>
                                                <div class="input-group">
                                                    <input type="text" id="pix-code" class="form-control" readonly>
                                                    <button class="btn btn-outline-secondary" type="button"
                                                        onclick="copyPixCode()">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="pix-instructions">
                                                <i class="fas fa-info-circle"></i>
                                                <strong>Como pagar:</strong><br>
                                                1. Abra o app do seu banco<br>
                                                2. Escolha "PIX" ou "Pagar"<br>
                                                3. Escaneie o QR Code ou cole o código
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dados do Cliente (opcionais para PIX - só aparece se tiver dados preenchidos) -->
                                @if (isset($link->dados_cliente['preenchidos']) &&
                                        ($link->dados_cliente['preenchidos']['nome'] ||
                                            $link->dados_cliente['preenchidos']['sobrenome'] ||
                                            $link->dados_cliente['preenchidos']['email'] ||
                                            $link->dados_cliente['preenchidos']['telefone'] ||
                                            $link->dados_cliente['preenchidos']['documento']))
                                    <div class="form-section">
                                        <h6 class="section-title">
                                            <i class="fas fa-user me-2"></i>
                                            Dados do Cliente
                                        </h6>

                                        <!-- Resumo dos dados preenchidos -->
                                        <div class="data-summary mb-3">
                                            <small><strong>Dados pré-preenchidos:</strong></small><br>
                                            @if ($link->dados_cliente['preenchidos']['nome'] || $link->dados_cliente['preenchidos']['sobrenome'])
                                                <small><strong>Nome:</strong>
                                                    {{ $link->dados_cliente['preenchidos']['nome'] }}
                                                    {{ $link->dados_cliente['preenchidos']['sobrenome'] }}</small><br>
                                            @endif
                                            @if ($link->dados_cliente['preenchidos']['email'])
                                                <small><strong>Email:</strong>
                                                    {{ $link->dados_cliente['preenchidos']['email'] }}</small><br>
                                            @endif
                                            @if ($link->dados_cliente['preenchidos']['telefone'])
                                                <small><strong>Telefone:</strong>
                                                    {{ $link->dados_cliente['preenchidos']['telefone'] }}</small><br>
                                            @endif
                                            @if ($link->dados_cliente['preenchidos']['documento'])
                                                <small><strong>Documento:</strong>
                                                    {{ $link->dados_cliente['preenchidos']['documento'] }}</small><br>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="text-center mt-4">
                                    <button type="button" class="btn btn-payment" onclick="gerarQRCode()"
                                        data-url="{{ route('pagamento.pix', $link->codigo_unico) }}">
                                        <i class="fas fa-qrcode me-2"></i>
                                        Gerar QR Code PIX
                                    </button>
                                </div>
                            </form>
                        @elseif($link->tipo_pagamento === 'BOLETO')
                            <!-- Formulário Boleto -->
                            <form id="boletoForm">
                                @csrf

                                <!-- Informações do Boleto -->
                                <div class="boleto-info">
                                    <h6 class="section-title">
                                        <i class="fas fa-file-invoice me-2"></i>
                                        Informações do Boleto
                                    </h6>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Data de Vencimento</label>
                                            <p class="mb-0">
                                                {{ $link->data_vencimento ? $link->data_vencimento->format('d/m/Y') : 'Não informado' }}
                                            </p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Data Limite de Pagamento</label>
                                            <p class="mb-0">
                                                {{ $link->data_limite_pagamento ? $link->data_limite_pagamento->format('d/m/Y') : 'Não informado' }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Código de Barras -->
                                    <div class="boleto-barcode">
                                        <div class="mb-2">
                                            <small class="text-muted">Código de Barras:</small>
                                        </div>
                                        <div id="boletoBarcode">
                                            <i class="fas fa-barcode fa-2x text-muted"></i>
                                            <p class="mt-2 mb-0">Código será gerado após confirmação</p>
                                        </div>
                                    </div>

                                    <div class="boleto-instructions">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Como pagar:</strong><br>
                                        1. Imprima o boleto ou copie o código<br>
                                        2. Pague em qualquer banco, lotérica ou internet banking<br>
                                        3. O pagamento será confirmado em até 3 dias úteis
                                    </div>
                                </div>

                                <!-- Dados do Cliente (obrigatórios para Boleto) -->
                                <div class="form-section">
                                    <h6 class="section-title">
                                        <i class="fas fa-user me-2"></i>
                                        Dados do Cliente
                                    </h6>

                                    <!-- Resumo dos dados preenchidos -->
                                    @if (isset($link->dados_cliente['preenchidos']) && $link->dados_cliente['preenchidos'])
                                        <div class="prefilled-data-card">
                                            <div class="prefilled-header">
                                                <div class="prefilled-icon">
                                                    <i class="fas fa-user-check"></i>
                                                </div>
                                                <div class="prefilled-title">
                                                    <h6>Dados Pré-preenchidos</h6>
                                                    <small>Informações já cadastradas</small>
                                                </div>
                                            </div>
                                            <div class="prefilled-content">
                                                <div class="data-item">
                                                    <i class="fas fa-user"></i>
                                                    <span>{{ $link->dados_cliente['preenchidos']['nome'] ?? '' }}
                                                        {{ $link->dados_cliente['preenchidos']['sobrenome'] ?? '' }}</span>
                                                </div>
                                                <div class="data-item">
                                                    <i class="fas fa-envelope"></i>
                                                    <span>{{ $link->dados_cliente['preenchidos']['email'] ?? '' }}</span>
                                                </div>
                                                <div class="data-item">
                                                    <i class="fas fa-phone"></i>
                                                    <span>{{ $link->dados_cliente['preenchidos']['telefone'] ?? '' }}</span>
                                                </div>
                                                <div class="data-item">
                                                    <i class="fas fa-id-card"></i>
                                                    <span>{{ $link->dados_cliente['preenchidos']['documento'] ?? '' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Endereço (obrigatório para Boleto) -->
                                    @if (isset($link->dados_cliente['preenchidos']['endereco']) && $link->dados_cliente['preenchidos']['endereco'])
                                        <div class="prefilled-data-card">
                                            <div class="prefilled-header">
                                                <div class="prefilled-icon">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </div>
                                                <div class="prefilled-title">
                                                    <h6>Endereço Pré-preenchido</h6>
                                                    <small>Informações de entrega já cadastradas</small>
                                                </div>
                                            </div>
                                            <div class="prefilled-content">
                                                <div class="data-item">
                                                    <i class="fas fa-road"></i>
                                                    <span>{{ $link->dados_cliente['preenchidos']['endereco']['rua'] ?? '' }},
                                                        {{ $link->dados_cliente['preenchidos']['endereco']['numero'] ?? '' }}</span>
                                                </div>
                                                <div class="data-item">
                                                    <i class="fas fa-map-pin"></i>
                                                    <span>{{ $link->dados_cliente['preenchidos']['endereco']['bairro'] ?? '' }}
                                                        -
                                                        {{ $link->dados_cliente['preenchidos']['endereco']['cidade'] ?? '' }}/{{ $link->dados_cliente['preenchidos']['endereco']['estado'] ?? '' }}</span>
                                                </div>
                                                <div class="data-item">
                                                    <i class="fas fa-mail-bulk"></i>
                                                    <span>CEP:
                                                        {{ $link->dados_cliente['preenchidos']['endereco']['cep'] ?? '' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="text-center mt-4">
                                    <button type="button" class="btn btn-payment" onclick="processarBoleto()"
                                        data-url="{{ route('pagamento.boleto', $link->codigo_unico) }}">
                                        <i class="fas fa-file-invoice me-2"></i>
                                        Gerar Boleto
                                    </button>
                                </div>
                            </form>
                        @else
                            <!-- Formulário Cartão (padrão) -->
                            <x-form.pagina-pagamento-dados-cartao :link="$link" />
                        @endif
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <x-form.pagina-pagamento-resumo :link="$link" />
        </div>

        <!-- Modal Sucesso -->
        <x-form.pagina-pagamento-modal-sucesso />

        <!-- Loading -->
        <x-util.data-loading-tip />

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <!-- PagSeguro 3DS SDK -->
        <script src="https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js"></script>
        <!-- Checkout Scripts -->
        <script src="{{ asset('js/checkout-scripts.js') }}"></script>
</body>

</html>

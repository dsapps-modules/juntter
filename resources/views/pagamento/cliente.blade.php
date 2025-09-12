<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Seguro - Juntter</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                <div class="col-6">
                    <img src="{{ asset('logo/JUNTTER-MODELO-1-SF.webp') }}" alt="Juntter" class="checkout-logo">
                </div>
                <div class="col-6 text-end">
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
                                @if($link->tipo_pagamento === 'PIX')
                                <i class="fas fa-qrcode"></i>
                                @elseif($link->tipo_pagamento === 'BOLETO')
                                <i class="fas fa-file-invoice"></i>
                                @else
                                <i class="fas fa-credit-card"></i>
                                @endif
                            </div>
                            <h1 class="payment-title">
                                @if($link->tipo_pagamento === 'PIX')
                                Pagamento PIX
                                @elseif($link->tipo_pagamento === 'BOLETO')
                                Pagamento Boleto
                                @else
                                Pagamento com Cartão
                                @endif
                            </h1>
                            @if($link->descricao)
                            <p class="payment-subtitle">{{ $link->descricao }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">

                        <!-- Formulário baseado no tipo de pagamento -->
                        @if($link->tipo_pagamento === 'PIX')
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
                                        <button class="btn btn-outline-primary btn-sm" onclick="downloadQrCode()" id="downloadBtn" style="display: none;">
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
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyPixCode()">
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
                            @if(isset($link->dados_cliente['preenchidos']) &&
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
                                    @if($link->dados_cliente['preenchidos']['nome'] || $link->dados_cliente['preenchidos']['sobrenome'])
                                    <small><strong>Nome:</strong> {{ $link->dados_cliente['preenchidos']['nome'] }} {{ $link->dados_cliente['preenchidos']['sobrenome'] }}</small><br>
                                    @endif
                                    @if($link->dados_cliente['preenchidos']['email'])
                                    <small><strong>Email:</strong> {{ $link->dados_cliente['preenchidos']['email'] }}</small><br>
                                    @endif
                                    @if($link->dados_cliente['preenchidos']['telefone'])
                                    <small><strong>Telefone:</strong> {{ $link->dados_cliente['preenchidos']['telefone'] }}</small><br>
                                    @endif
                                    @if($link->dados_cliente['preenchidos']['documento'])
                                    <small><strong>Documento:</strong> {{ $link->dados_cliente['preenchidos']['documento'] }}</small><br>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <div class="text-center mt-4">
                                <button type="button" class="btn btn-payment" onclick="gerarQRCode()" data-url="{{ route('pagamento.pix', $link->codigo_unico) }}">
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
                                        <p class="mb-0">{{ $link->data_vencimento ? $link->data_vencimento->format('d/m/Y') : 'Não informado' }}</p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Data Limite de Pagamento</label>
                                        <p class="mb-0">{{ $link->data_limite_pagamento ? $link->data_limite_pagamento->format('d/m/Y') : 'Não informado' }}</p>
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
                                @if(isset($link->dados_cliente['preenchidos']) && $link->dados_cliente['preenchidos'])
                                <div class="data-summary mb-3">
                                    <small><strong>Dados pré-preenchidos:</strong></small><br>
                                    <small><strong>Nome:</strong> {{ $link->dados_cliente['preenchidos']['nome'] ?? '' }} {{ $link->dados_cliente['preenchidos']['sobrenome'] ?? '' }}</small><br>
                                    <small><strong>Email:</strong> {{ $link->dados_cliente['preenchidos']['email'] ?? '' }}</small><br>
                                    <small><strong>Telefone:</strong> {{ $link->dados_cliente['preenchidos']['telefone'] ?? '' }}</small><br>
                                    <small><strong>Documento:</strong> {{ $link->dados_cliente['preenchidos']['documento'] ?? '' }}</small>
                                </div>
                                @endif

                                <!-- Endereço (obrigatório para Boleto) -->
                                @if(isset($link->dados_cliente['preenchidos']['endereco']) && $link->dados_cliente['preenchidos']['endereco'])
                                <div class="data-summary mb-3">
                                    <small><strong>Endereço pré-preenchido:</strong></small><br>
                                    <small>{{ $link->dados_cliente['preenchidos']['endereco']['rua'] ?? '' }}, {{ $link->dados_cliente['preenchidos']['endereco']['numero'] ?? '' }}</small><br>
                                    <small>{{ $link->dados_cliente['preenchidos']['endereco']['bairro'] ?? '' }} - {{ $link->dados_cliente['preenchidos']['endereco']['cidade'] ?? '' }}/{{ $link->dados_cliente['preenchidos']['endereco']['estado'] ?? '' }}</small><br>
                                    <small>CEP: {{ $link->dados_cliente['preenchidos']['endereco']['cep'] ?? '' }}</small>
                                </div>
                                @endif
                            </div>

                            <div class="text-center mt-4">
                                <button type="button" class="btn btn-payment" onclick="processarBoleto()" data-url="{{ route('pagamento.boleto', $link->codigo_unico) }}">
                                    <i class="fas fa-file-invoice me-2"></i>
                                    Gerar Boleto
                                </button>
                            </div>
                        </form>

                        @else
                        <!-- Formulário Cartão (padrão) -->
                        <form id="creditForm" data-url="{{ route('pagamento.cartao', $link->codigo_unico) }}">
                            @csrf

                            <!-- Dados do Cartão -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-section">
                                        <h6 class="section-title">
                                            <i class="fas fa-credit-card me-2"></i>
                                            Dados do Cartão
                                        </h6>

                                        <!-- Parcelamento integrado -->
                                        @if($link->parcelas > 1)
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">
                                                    Parcelas <span class="text-danger">*</span>
                                                </label>
                                                <select name="installments" class="form-select" required>
                                                    <option value="">Selecione...</option>
                                                    @for($i = 1; $i <= $link->parcelas; $i++)
                                                        <option value="{{ $i }}">{{ $i }}x de R$ {{ number_format($link->valor / $i, 2, ',', '.') }}</option>
                                                        @endfor
                                                </select>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label">Nome do titular <span class="text-danger">*</span></label>
                                                <input type="text" name="card[holder_name]" class="form-control" placeholder="Nome completo" required>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label">Número do cartão <span class="text-danger">*</span></label>
                                                <div class="form-group">
                                                    <input type="text" name="card[card_number]" class="form-control" placeholder="0000 0000 0000 0000" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 mb-4">
                                                <label class="form-label">Mês <span class="text-danger">*</span></label>
                                                <select name="card[expiration_month]" class="form-select" required>
                                                    <option value="">MM</option>
                                                    @for($m = 1; $m <= 12; $m++)
                                                        <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                                                        @endfor
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-4">
                                                <label class="form-label">Ano <span class="text-danger">*</span></label>
                                                <select name="card[expiration_year]" class="form-select" required>
                                                    <option value="">AAAA</option>
                                                    @for($y = date('Y'); $y <= date('Y') + 10; $y++)
                                                        <option value="{{ $y }}">{{ $y }}</option>
                                                        @endfor
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-4">
                                                <label class="form-label">CVV <span class="text-danger">*</span></label>
                                                <div class="form-group">
                                                    <input type="text" name="card[security_code]" class="form-control" placeholder="123" maxlength="4" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dados do Cliente e Endereço -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="form-section">
                                            <h6 class="section-title">
                                                <i class="fas fa-user me-2"></i>
                                                Dados do Cliente
                                            </h6>

                                            <!-- Resumo dos dados preenchidos -->
                                            @if(isset($link->dados_cliente['preenchidos']) &&
                                            $link->dados_cliente['preenchidos']['nome'] &&
                                            $link->dados_cliente['preenchidos']['sobrenome'] &&
                                            $link->dados_cliente['preenchidos']['email'] &&
                                            $link->dados_cliente['preenchidos']['telefone'] &&
                                            $link->dados_cliente['preenchidos']['documento'])
                                            <div class="data-summary mb-3">
                                                <small><strong>Dados pré-preenchidos:</strong></small><br>
                                                <small><strong>Nome:</strong> {{ $link->dados_cliente['preenchidos']['nome'] }} {{ $link->dados_cliente['preenchidos']['sobrenome'] }}</small><br>
                                                <small><strong>Email:</strong> {{ $link->dados_cliente['preenchidos']['email'] }}</small><br>
                                                <small><strong>Telefone:</strong> {{ $link->dados_cliente['preenchidos']['telefone'] }}</small><br>
                                                <small><strong>Documento:</strong> {{ $link->dados_cliente['preenchidos']['documento'] }}</small>
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="toggleClientFields()">
                                                    <i class="fas fa-edit me-1"></i>Editar
                                                </button>
                                            </div>
                                            @endif

                                            <!-- Campos editáveis (mostrar se não preenchidos ou se clicou em editar) -->
                                            <div id="clientFields" style="display: {{ (isset($link->dados_cliente['preenchidos']) && $link->dados_cliente['preenchidos']['nome'] && $link->dados_cliente['preenchidos']['sobrenome'] && $link->dados_cliente['preenchidos']['email'] && $link->dados_cliente['preenchidos']['telefone'] && $link->dados_cliente['preenchidos']['documento']) ? 'none' : 'block' }};">
                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                                                        <input type="text" name="client[first_name]" class="form-control" placeholder="Nome completo" value="{{ $link->dados_cliente['preenchidos']['nome'] ?? '' }}" required>
                                                    </div>
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">Sobrenome <span class="text-danger">*</span></label>
                                                        <input type="text" name="client[last_name]" class="form-control" placeholder="Sobrenome" value="{{ $link->dados_cliente['preenchidos']['sobrenome'] ?? '' }}" required>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                                        <div class="form-group">
                                                            <input type="email" name="client[email]" class="form-control" placeholder="email@exemplo.com" value="{{ $link->dados_cliente['preenchidos']['email'] ?? '' }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">Telefone <span class="text-danger">*</span></label>
                                                        <div class="form-group">
                                                            <input type="text" name="client[phone]" class="form-control" placeholder="(00) 00000-0000" value="{{ $link->dados_cliente['preenchidos']['telefone'] ?? '' }}" required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    <label class="form-label">CPF/CNPJ <span class="text-danger">*</span></label>
                                                    <div class="form-group">
                                                        <input type="text" name="client[document]" class="form-control" placeholder="000.000.000-00" value="{{ $link->dados_cliente['preenchidos']['documento'] ?? '' }}" required>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Resumo do endereço -->
                                            @if(isset($link->dados_cliente['preenchidos']['endereco']) &&
                                            $link->dados_cliente['preenchidos']['endereco']['rua'] &&
                                            $link->dados_cliente['preenchidos']['endereco']['numero'] &&
                                            $link->dados_cliente['preenchidos']['endereco']['bairro'] &&
                                            $link->dados_cliente['preenchidos']['endereco']['cidade'] &&
                                            $link->dados_cliente['preenchidos']['endereco']['estado'] &&
                                            $link->dados_cliente['preenchidos']['endereco']['cep'])
                                            <div class="data-summary mb-3">
                                                <small><strong>Endereço pré-preenchido:</strong></small><br>
                                                <small>{{ $link->dados_cliente['preenchidos']['endereco']['rua'] }}, {{ $link->dados_cliente['preenchidos']['endereco']['numero'] }}</small><br>
                                                <small>{{ $link->dados_cliente['preenchidos']['endereco']['bairro'] }} - {{ $link->dados_cliente['preenchidos']['endereco']['cidade'] }}/{{ $link->dados_cliente['preenchidos']['endereco']['estado'] }}</small><br>
                                                <small>CEP: {{ $link->dados_cliente['preenchidos']['endereco']['cep'] }}</small>
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="toggleAddressFields()">
                                                    <i class="fas fa-edit me-1"></i>Editar
                                                </button>
                                            </div>
                                            @endif

                                            <!-- Campos de endereço editáveis (mostrar se não preenchidos ou se clicou em editar) -->
                                            <div id="addressFields" style="display: {{ (isset($link->dados_cliente['preenchidos']['endereco']) && $link->dados_cliente['preenchidos']['endereco']['rua'] && $link->dados_cliente['preenchidos']['endereco']['numero'] && $link->dados_cliente['preenchidos']['endereco']['bairro'] && $link->dados_cliente['preenchidos']['endereco']['cidade'] && $link->dados_cliente['preenchidos']['endereco']['estado'] && $link->dados_cliente['preenchidos']['endereco']['cep']) ? 'none' : 'block' }};">
                                                <div class="row">
                                                    <div class="col-md-8 mb-4">
                                                        <label class="form-label">Rua <span class="text-danger">*</span></label>
                                                        <input type="text" name="client[address][street]" class="form-control" placeholder="Nome da rua" value="{{ $link->dados_cliente['preenchidos']['endereco']['rua'] ?? '' }}" required>
                                                    </div>
                                                    <div class="col-md-4 mb-4">
                                                        <label class="form-label">Número <span class="text-danger">*</span></label>
                                                        <input type="text" name="client[address][number]" class="form-control" placeholder="123" value="{{ $link->dados_cliente['preenchidos']['endereco']['numero'] ?? '' }}" required>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-4 mb-4">
                                                        <label class="form-label">Bairro <span class="text-danger">*</span></label>
                                                        <input type="text" name="client[address][neighborhood]" class="form-control" placeholder="Bairro" value="{{ $link->dados_cliente['preenchidos']['endereco']['bairro'] ?? '' }}" required>
                                                    </div>
                                                    <div class="col-md-4 mb-4">
                                                        <label class="form-label">Cidade <span class="text-danger">*</span></label>
                                                        <input type="text" name="client[address][city]" class="form-control" placeholder="Cidade" value="{{ $link->dados_cliente['preenchidos']['endereco']['cidade'] ?? '' }}" required>
                                                    </div>
                                                    <div class="col-md-4 mb-4">
                                                        <label class="form-label">CEP <span class="text-danger">*</span></label>
                                                        <input type="text" name="client[address][zip_code]" class="form-control" placeholder="00000-000" value="{{ $link->dados_cliente['preenchidos']['endereco']['cep'] ?? '' }}" required>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">Estado <span class="text-danger">*</span></label>
                                                        <select name="client[address][state]" class="form-select" required>
                                                            <option value="">Selecione...</option>
                                                            <option value="AC" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'AC' ? 'selected' : '' }}>Acre</option>
                                                            <option value="AL" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'AL' ? 'selected' : '' }}>Alagoas</option>
                                                            <option value="AP" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'AP' ? 'selected' : '' }}>Amapá</option>
                                                            <option value="AM" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'AM' ? 'selected' : '' }}>Amazonas</option>
                                                            <option value="BA" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'BA' ? 'selected' : '' }}>Bahia</option>
                                                            <option value="CE" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'CE' ? 'selected' : '' }}>Ceará</option>
                                                            <option value="DF" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'DF' ? 'selected' : '' }}>Distrito Federal</option>
                                                            <option value="ES" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'ES' ? 'selected' : '' }}>Espírito Santo</option>
                                                            <option value="GO" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'GO' ? 'selected' : '' }}>Goiás</option>
                                                            <option value="MA" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'MA' ? 'selected' : '' }}>Maranhão</option>
                                                            <option value="MT" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'MT' ? 'selected' : '' }}>Mato Grosso</option>
                                                            <option value="MS" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'MS' ? 'selected' : '' }}>Mato Grosso do Sul</option>
                                                            <option value="MG" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'MG' ? 'selected' : '' }}>Minas Gerais</option>
                                                            <option value="PA" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'PA' ? 'selected' : '' }}>Pará</option>
                                                            <option value="PB" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'PB' ? 'selected' : '' }}>Paraíba</option>
                                                            <option value="PR" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'PR' ? 'selected' : '' }}>Paraná</option>
                                                            <option value="PE" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'PE' ? 'selected' : '' }}>Pernambuco</option>
                                                            <option value="PI" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'PI' ? 'selected' : '' }}>Piauí</option>
                                                            <option value="RJ" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'RJ' ? 'selected' : '' }}>Rio de Janeiro</option>
                                                            <option value="RN" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'RN' ? 'selected' : '' }}>Rio Grande do Norte</option>
                                                            <option value="RS" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'RS' ? 'selected' : '' }}>Rio Grande do Sul</option>
                                                            <option value="RO" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'RO' ? 'selected' : '' }}>Rondônia</option>
                                                            <option value="RR" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'RR' ? 'selected' : '' }}>Roraima</option>
                                                            <option value="SC" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'SC' ? 'selected' : '' }}>Santa Catarina</option>
                                                            <option value="SP" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'SP' ? 'selected' : '' }}>São Paulo</option>
                                                            <option value="SE" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'SE' ? 'selected' : '' }}>Sergipe</option>
                                                            <option value="TO" {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'TO' ? 'selected' : '' }}>Tocantins</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-4">
                                                        <label class="form-label">Complemento</label>
                                                        <input type="text" name="client[address][complement]" class="form-control" placeholder="Apto, casa, etc." value="{{ $link->dados_cliente['preenchidos']['endereco']['complemento'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-payment">
                                        <i class="fas fa-credit-card me-2"></i>
                                        Finalizar Pagamento
                                    </button>
                                </div>
                            </div>
                        </form>
                        @endif
                    </div>
                </div>
            </div>



            <!-- Order Summary -->
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
                        @if($link->tipo_pagamento === 'CREDIT' && $link->parcelas > 1)
                        <div class="order-item">
                            <span class="order-item-label">Parcelamento</span>
                            <span class="order-item-value">Até {{ $link->parcelas }}x</span>
                        </div>
                        @endif
                        <div class="order-item">
                            <span class="order-item-label">Forma de Pagamento</span>
                            <span class="order-item-value">
                                @if($link->tipo_pagamento === 'PIX')
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
        </div>

        <!-- Modal Sucesso -->
        <div class="modal fade" id="successModal" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Pagamento Processado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-check-circle fa-3x text-success"></i>
                        </div>
                        <p class="mb-0">Pagamento realizado com sucesso!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div id="loading" class="loading-overlay" style="display: none;">
            <div class="d-flex justify-content-center align-items-center" style="height: 100vh;">
                <div class="text-center text-white">
                    <div class="spinner-border spinner-custom mb-3" style="width: 3rem; height: 3rem;"></div>
                    <h5>Processando pagamento...</h5>
                    <p class="mb-0">Aguarde um momento</p>
                </div>
            </div>
        </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Checkout Scripts -->
    <script src="{{ asset('js/checkout-scripts.js') }}"></script>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</body>

</html>
              
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- jQuery Mask Plugin -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <style>
        :root {
            --primary-color: #FFCF00;
            --secondary-color: #ffb800;
            --dark-color: #000000;
            --light-gray: #f8f9fa;
            --white: #ffffff;
        }

        body {
            background: linear-gradient(135deg, var(--dark-color) 0%, var(--secondary-color) 70%, var(--primary-color) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Particles Background */
        .particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 8s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-30px) rotate(120deg); }
            66% { transform: translateY(-60px) rotate(240deg); }
        }

        /* Navbar Styles */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 4px 30px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--dark-color) !important;
            font-size: 1.5rem;
        }

        .navbar-brand i {
            color: var(--primary-color);
        }

        /* Payment Container */
        .payment-container {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 100px 20px 20px;
        }

        .payment-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.3);
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .payment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        /* Header do Pagamento */
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .payment-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: var(--dark-color);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.3);
        }

        .payment-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .payment-subtitle {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .amount-display {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 8px;
            padding: 0.5rem;
            margin: 0.5rem auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            color: var(--dark-color);
            max-width: 300px;
            text-align: center;
        }

        .amount-display h2 {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
        }

        /* Form Sections */
        .form-section {
            background: var(--light-gray);
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .section-title {
            color: var(--dark-color);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.6rem;
            padding-bottom: 0.3rem;
            border-bottom: 2px solid var(--primary-color);
        }

        /* Form Controls */
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            height: auto;
        }

        .form-control:focus, .form-select:focus {
            background: var(--white);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
            outline: none;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.3rem;
            font-size: 0.8rem;
        }

        /* Button Styles */
        .btn-payment {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--dark-color);
            font-weight: 700;
            font-size: 1rem;
            padding: 12px 0;
            border-radius: 12px;
            border: none;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-payment::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-payment:hover::before {
            left: 100%;
        }

        .btn-payment:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
            color: var(--dark-color);
        }

        /* PIX Specific Styles */
        .pix-qr-container {
            text-align: center;
            padding: 2rem;
            background: var(--light-gray);
            border-radius: 16px;
            margin-bottom: 1rem;
        }

        .pix-qr-code {
            width: 200px;
            height: 200px;
            background: var(--white);
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: var(--primary-color);
        }

        /* Boleto Specific Styles */
        .boleto-info {
            background: var(--light-gray);
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .boleto-barcode {
            background: var(--white);
            border: 2px solid var(--dark-color);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            font-family: monospace;
            font-size: 0.8rem;
            letter-spacing: 2px;
            margin-bottom: 1rem;
        }

        /* Data Summary Styles */
        .data-summary {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 12px;
            padding: 15px;
            color: var(--dark-color);
            position: relative;
        }

        .data-summary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px 0 0 2px;
        }

        .data-summary small {
            color: var(--dark-color);
            font-weight: 500;
        }

        .data-summary strong {
            color: var(--dark-color);
            font-weight: 700;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 20px 20px 0 0;
        }

        /* Loading Overlay */
        .loading-overlay {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }

        .spinner-custom {
            color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .payment-card {
                padding: 25px 20px;
                margin: 15px;
                border-radius: 16px;
            }
            
            .payment-title {
                font-size: 1.5rem;
            }
            
            .payment-subtitle {
                font-size: 0.85rem;
            }
            
            .amount-display h2 {
                font-size: 1.1rem;
            }
            
            .btn-payment {
                padding: 10px 0;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <!-- Particles Background -->
    <div class="particles-container" id="particles"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="{{ asset('logo/JUNTTER-MODELO-1-SF.webp') }}" alt="Logo" class="img-fluid" style="width: 100px;">
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    Pagamento Seguro
                </span>
            </div>
        </div>
    </nav>

    <!-- Payment Container -->
    <div class="payment-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-11">
                    <div class="payment-card">
                        <!-- Header do Pagamento -->
                        <div class="payment-header">
                            <div class="payment-logo">
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
                                    Pagamento
                                @endif
                            </h1>
                            @if($link->descricao)
                                <p class="payment-subtitle">{{ $link->descricao }}</p>
                            @endif
                            <div class="amount-display">
                                <h2 class="mb-0">{{ $link->valor_formatado }}</h2>
                            </div>
                        </div>

                        <!-- Formulário baseado no tipo de pagamento -->
                        @if($link->tipo_pagamento === 'PIX')
                            <!-- Formulário PIX -->
                            <form id="pixForm">
                                @csrf
                                
                                <!-- QR Code PIX -->
                                <div class="pix-qr-container">
                                    <div class="pix-qr-code">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                    <h5 class="mb-2">Escaneie o QR Code</h5>
                                    <p class="text-muted mb-3">Use o app do seu banco para escanear o código e finalizar o pagamento</p>
                                    <button type="button" class="btn btn-outline-primary" onclick="gerarQRCode()">
                                        <i class="fas fa-sync-alt me-2"></i>Gerar QR Code
                                    </button>
                                </div>

                                <!-- Dados do Cliente (opcionais para PIX) -->
                                @if(isset($link->dados_cliente['preenchidos']) && $link->dados_cliente['preenchidos'])
                                    <div class="form-section">
                                        <h6 class="section-title">
                                            <i class="fas fa-user me-2"></i>
                                            Dados do Cliente
                                        </h6>
                                        
                                        <!-- Resumo dos dados preenchidos -->
                                        <div class="data-summary mb-3">
                                            <small><strong>Dados pré-preenchidos:</strong></small><br>
                                            <small><strong>Nome:</strong> {{ $link->dados_cliente['preenchidos']['nome'] ?? '' }} {{ $link->dados_cliente['preenchidos']['sobrenome'] ?? '' }}</small><br>
                                            <small><strong>Email:</strong> {{ $link->dados_cliente['preenchidos']['email'] ?? '' }}</small><br>
                                            <small><strong>Telefone:</strong> {{ $link->dados_cliente['preenchidos']['telefone'] ?? '' }}</small><br>
                                            <small><strong>Documento:</strong> {{ $link->dados_cliente['preenchidos']['documento'] ?? '' }}</small>
                                        </div>
                                    </div>
                                @endif

                                <div class="text-center mt-4">
                                    <button type="button" class="btn btn-payment" onclick="processarPIX()">
                                        <i class="fas fa-qrcode me-2"></i>
                                        Processar PIX
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
                                    <button type="button" class="btn btn-payment" onclick="processarBoleto()">
                                        <i class="fas fa-file-invoice me-2"></i>
                                        Gerar Boleto
                                    </button>
                                </div>
                            </form>

                        @else
                            <!-- Formulário Cartão (padrão) -->
                            <form id="creditForm">
                                @csrf
                                
                                <!-- Dados do Cartão e Cliente lado a lado -->
                                <div class="row">
                                    <!-- Dados do Cartão -->
                                    <div class="col-md-6">
                                        <div class="form-section">
                                            <h6 class="section-title">
                                                <i class="fas fa-credit-card me-2"></i>
                                                Dados do Cartão
                                            </h6>
                                            
                                            <!-- Parcelamento integrado -->
                                            @if($link->parcelas > 1)
                                            <div class="row mb-3">
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
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Nome do titular <span class="text-danger">*</span></label>
                                                    <input type="text" name="card[holder_name]" class="form-control" placeholder="Nome completo" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Número do cartão <span class="text-danger">*</span></label>
                                                    <input type="text" name="card[card_number]" class="form-control" placeholder="0000 0000 0000 0000" required>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Mês <span class="text-danger">*</span></label>
                                                    <select name="card[expiration_month]" class="form-select" required>
                                                        <option value="">MM</option>
                                                        @for($m = 1; $m <= 12; $m++)
                                                            <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Ano <span class="text-danger">*</span></label>
                                                    <select name="card[expiration_year]" class="form-select" required>
                                                        <option value="">AAAA</option>
                                                        @for($y = date('Y'); $y <= date('Y') + 10; $y++)
                                                            <option value="{{ $y }}">{{ $y }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">CVV <span class="text-danger">*</span></label>
                                                    <input type="text" name="card[security_code]" class="form-control" placeholder="123" maxlength="4" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dados do Cliente e Endereço -->
                                    <div class="col-md-6">
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

                                            <!-- Endereço (opcional para cartão) -->
                                            @if(isset($link->dados_cliente['preenchidos']['endereco']) && $link->dados_cliente['preenchidos']['endereco'])
                                                <div class="data-summary mb-3">
                                                    <small><strong>Endereço pré-preenchido:</strong></small><br>
                                                    <small>{{ $link->dados_cliente['preenchidos']['endereco']['rua'] ?? '' }}, {{ $link->dados_cliente['preenchidos']['endereco']['numero'] ?? '' }}</small><br>
                                                    <small>{{ $link->dados_cliente['preenchidos']['endereco']['bairro'] ?? '' }} - {{ $link->dados_cliente['preenchidos']['endereco']['cidade'] ?? '' }}/{{ $link->dados_cliente['preenchidos']['endereco']['estado'] ?? '' }}</small><br>
                                                    <small>CEP: {{ $link->dados_cliente['preenchidos']['endereco']['cep'] ?? '' }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-payment">
                                        <i class="fas fa-credit-card me-2"></i>
                                        Finalizar Pagamento
                                    </button>
                                </div>
                            </form>
                        @endif
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
    
    <script>
        // Create Particles
        function createParticles() {
            const container = document.getElementById('particles');
            if (container) {
                for (let i = 0; i < 30; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.top = Math.random() * 100 + '%';
                    particle.style.width = Math.random() * 8 + 4 + 'px';
                    particle.style.height = particle.style.width;
                    particle.style.animationDelay = Math.random() * 8 + 's';
                    container.appendChild(particle);
                }
            }
        }

        $(document).ready(function() {
            // Criar partículas
            createParticles();
            
            // Máscaras para cartão
            $('input[name="card[card_number]"]').mask('0000 0000 0000 0000');
            $('input[name="client[phone]"]').mask('(00) 00000-0000');
            $('input[name="client[document]"]').mask('000.000.000-00');
            $('input[name="client[address][zip_code]"]').mask('00000-000');
            
            // Form submit para cartão
            $('#creditForm').submit(function(e) {
                e.preventDefault();
                processarCartao($(this));
            });
        });

        // Processar pagamento com cartão
        function processarCartao(form) {
            $('#loading').show();
            
            const url = '{{ route("pagamento.cartao", $link->codigo_unico) }}';
            const data = form.serialize();
            
            $.post(url, data)
                .done(function(response) {
                    $('#loading').hide();
                    
                    if (response.success) {
                        $('#successModal').modal('show');
                    } else {
                        alert(response.error || 'Erro ao processar pagamento');
                    }
                })
                .fail(function(xhr) {
                    $('#loading').hide();
                    let error = 'Erro ao processar pagamento. Tente novamente.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        error = xhr.responseJSON.error;
                    }
                    alert(error);
                });
        }

        // Processar PIX
        function processarPIX() {
            $('#loading').show();
            
            // Simular processamento PIX
            setTimeout(function() {
                $('#loading').hide();
                $('#successModal').modal('show');
            }, 2000);
        }

        // Processar Boleto
        function processarBoleto() {
            $('#loading').show();
            
            // Simular geração de boleto
            setTimeout(function() {
                $('#loading').hide();
                
                // Mostrar código de barras simulado
                const barcodeContainer = document.getElementById('boletoBarcode');
                barcodeContainer.innerHTML = `
                    <div class="mb-2">
                        <small class="text-muted">Código de Barras:</small>
                    </div>
                    <div style="font-family: monospace; font-size: 0.7rem; letter-spacing: 1px;">
                        12345.67890.12345.678901.23456.789012.3.45678901234567
                    </div>
                    <p class="mt-2 mb-0 text-success">
                        <i class="fas fa-check-circle me-1"></i>
                        Boleto gerado com sucesso!
                    </p>
                `;
                
                $('#successModal').modal('show');
            }, 2000);
        }

        // Gerar QR Code PIX
        function gerarQRCode() {
            const qrContainer = document.querySelector('.pix-qr-code');
            qrContainer.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Simular geração de QR Code
            setTimeout(function() {
                qrContainer.innerHTML = '<i class="fas fa-qrcode"></i>';
            }, 1000);
        }
    </script>
</body>
</html>
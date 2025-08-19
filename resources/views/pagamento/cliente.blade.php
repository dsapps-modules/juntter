<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $link->titulo }} - Pagamento</title>
    
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
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <h1 class="payment-title">{{ $link->titulo }}</h1>
                            @if($link->descricao)
                                <p class="payment-subtitle">{{ $link->descricao }}</p>
                            @endif
                            <div class="amount-display">
                                <h2 class="mb-0">{{ $link->valor_formatado }}</h2>
                            </div>
                        </div>

                        <!-- Formulário -->
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
                                        @if($link->dados_cliente['preenchidos'] && 
                                             $link->dados_cliente['preenchidos']['nome'] && 
                                             $link->dados_cliente['preenchidos']['sobrenome'] &&
                                             $link->dados_cliente['preenchidos']['email'] && 
                                             $link->dados_cliente['preenchidos']['telefone'] && 
                                             $link->dados_cliente['preenchidos']['documento'])
                                        <div class="data-summary mb-3">
                                            <small><strong>Dados pré-preenchidos:</strong></small><br>
                                            <small><strong>Nome:</strong> {{ $link->dados_cliente['preenchidos']['nome'] }}</small><br>
                                            <small><strong>Sobrenome:</strong> {{ $link->dados_cliente['preenchidos']['sobrenome'] }}</small><br>
                                            <small><strong>Email:</strong> {{ $link->dados_cliente['preenchidos']['email'] }}</small><br>
                                            <small><strong>Telefone:</strong> {{ $link->dados_cliente['preenchidos']['telefone'] }}</small><br>
                                            <small><strong>Documento:</strong> {{ $link->dados_cliente['preenchidos']['documento'] }}</small>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="toggleClientFields()">
                                                <i class="fas fa-edit me-1"></i>Editar
                                            </button>
                                        </div>
                                        @endif
                                        
                                        <!-- Campos editáveis (mostrar se não preenchidos ou se clicou em editar) -->
                                        <div id="clientFields" style="display: {{ ($link->dados_cliente['preenchidos'] && $link->dados_cliente['preenchidos']['nome'] && $link->dados_cliente['preenchidos']['email'] && $link->dados_cliente['preenchidos']['telefone'] && $link->dados_cliente['preenchidos']['documento']) ? 'none' : 'block' }};">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Nome <span class="text-danger">*</span></label>
                                                    <input type="text" name="client[first_name]" class="form-control" placeholder="Nome completo" value="{{ $link->dados_cliente['preenchidos']['nome'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Sobrenome <span class="text-danger">*</span></label>
                                                    <input type="text" name="client[last_name]" class="form-control" placeholder="Sobrenome" value="{{ $link->dados_cliente['preenchidos']['sobrenome'] ?? '' }}" required>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                                    <input type="email" name="client[email]" class="form-control" placeholder="email@exemplo.com" value="{{ $link->dados_cliente['preenchidos']['email'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Telefone <span class="text-danger">*</span></label>
                                                    <input type="text" name="client[phone]" class="form-control" placeholder="(00) 00000-0000" value="{{ $link->dados_cliente['preenchidos']['telefone'] ?? '' }}" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">CPF/CNPJ <span class="text-danger">*</span></label>
                                                <input type="text" name="client[document]" class="form-control" placeholder="000.000.000-00" value="{{ $link->dados_cliente['preenchidos']['documento'] ?? '' }}" required>
                                            </div>
                                        </div>
                                        
                                        <!-- Resumo do endereço -->
                                        @if($link->dados_cliente['preenchidos']['endereco'] && 
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
                                        <div id="addressFields" style="display: {{ ($link->dados_cliente['preenchidos']['endereco'] && $link->dados_cliente['preenchidos']['endereco']['rua'] && $link->dados_cliente['preenchidos']['endereco']['numero'] && $link->dados_cliente['preenchidos']['endereco']['bairro'] && $link->dados_cliente['preenchidos']['endereco']['cidade'] && $link->dados_cliente['preenchidos']['endereco']['estado'] && $link->dados_cliente['preenchidos']['endereco']['cep']) ? 'none' : 'block' }};">
                                            <div class="row">
                                                <div class="col-md-8 mb-3">
                                                    <label class="form-label">Rua <span class="text-danger">*</span></label>
                                                    <input type="text" name="client[address][street]" class="form-control" placeholder="Nome da rua" value="{{ $link->dados_cliente['preenchidos']['endereco']['rua'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Número <span class="text-danger">*</span></label>
                                                    <input type="text" name="client[address][number]" class="form-control" placeholder="123" value="{{ $link->dados_cliente['preenchidos']['endereco']['numero'] ?? '' }}" required>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Bairro <span class="text-danger">*</span></label>
                                                    <input type="text" name="client[address][neighborhood]" class="form-control" placeholder="Bairro" value="{{ $link->dados_cliente['preenchidos']['endereco']['bairro'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Cidade <span class="text-danger">*</span></label>
                                                    <input type="text" name="client[address][city]" class="form-control" placeholder="Cidade" value="{{ $link->dados_cliente['preenchidos']['endereco']['cidade'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">CEP <span class="text-danger">*</span></label>
                                                    <input type="text" name="client[address][zip_code]" class="form-control" placeholder="00000-000" value="{{ $link->dados_cliente['preenchidos']['endereco']['cep'] ?? '' }}" required>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
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
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Complemento</label>
                                                    <input type="text" name="client[address][complement]" class="form-control" placeholder="Apto, casa, etc." value="{{ $link->dados_cliente['preenchidos']['endereco']['complemento'] ?? '' }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-payment" style="width: auto; padding: 10px 40px; font-size: 0.9rem;">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Finalizar Pagamento
                                </button>
                            </div>
                        </form>
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
        // Create Particles (igual ao da Juntter)
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
            
            // Máscaras
            $('input[name="card[card_number]"]').mask('0000 0000 0000 0000');
            $('input[name="client[phone]"]').mask('(00) 00000-0000');
            $('input[name="client[document]"]').mask('000.000.000-00');
            $('input[name="client[address][zip_code]"]').mask('00000-000');
            
            // Form submit
            $('#creditForm').submit(function(e) {
                e.preventDefault();
                processPayment($(this));
            });
        });

        function processPayment(form) {
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

        // Funções para mostrar/ocultar campos editáveis
        function toggleClientFields() {
            const clientFields = document.getElementById('clientFields');
            if (clientFields) {
                const isHidden = clientFields.style.display === 'none';
                clientFields.style.display = isHidden ? 'block' : 'none';
                
                // Atualizar texto do botão
                const button = event.target;
                button.innerHTML = isHidden ? 
                    '<i class="fas fa-times me-1"></i>Voltar' : 
                    '<i class="fas fa-edit me-1"></i>Editar';
            }
        }

        function toggleAddressFields() {
            const addressFields = document.getElementById('addressFields');
            if (addressFields) {
                const isHidden = addressFields.style.display === 'none';
                addressFields.style.display = isHidden ? 'block' : 'none';
                
                // Atualizar texto do botão
                const button = event.target;
                button.innerHTML = isHidden ? 
                    '<i class="fas fa-times me-1"></i>Voltar' : 
                    '<i class="fas fa-edit me-1"></i>Editar';
            }
        }
    </script>
</body>
</html>

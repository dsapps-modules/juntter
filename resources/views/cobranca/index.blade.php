@extends('templates.dashboard-template')

@section('title', 'Cobrança Única')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Cobranças', 'icon' => 'fas fa-credit-card', 'url' => '#']
    ]"
/>
@if(session('pix_data'))
    <div id="pix-data" data-pix-data="{{ json_encode(session('pix_data')) }}" style="display: none;"></div>
@endif
<!-- Alert -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning bg-warning text-white border-0 rounded-3 shadow-sm">
            <i class="fas fa-info-circle me-2"></i>
            Gere links de pagamento para seus clientes.
        </div>
    </div>
</div>

<!-- Header com botão -->
<div class="row align-items-center mb-4">
    <div class="col-12 text-center">
        <h1 class="h3 mb-2 fw-bold">Cobrança Única</h1>
        <p class="text-muted mb-3">Gerencie suas cobranças avulsas</p>
        <button class="btn btn-novo-pagamento shadow-sm" 
                data-bs-toggle="modal" 
                data-bs-target="#modalCobranca">
            <i class="fas fa-plus me-2"></i>
            Novo Pagamento Único
        </button>
    </div>
</div>

<!-- Tabela de cobranças -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-center flex-grow-1">
                        <h5 class="card-title fw-bold mb-2">
                            <i class="fas fa-credit-card me-2 text-primary"></i>
                            Histórico de Transações
                        </h5>
                        <p class="text-muted mb-0 small">
                            <i class="fas fa-info-circle me-1"></i>
                            Clique em "Ver Detalhes" para ver informações completas do cliente e da transação
                        </p>
                    </div>
                    
                    <!-- Filtro de Data -->
                    <div class="d-flex align-items-center gap-2">
                        <form method="GET" action="{{ request()->url() }}" class="d-flex align-items-center gap-1">
                            <select name="mes" class="form-select form-select-sm" style="width: 100px; font-size: 0.8rem;">
                                <option value="" {{ empty($mesAtual) ? 'selected' : '' }}>Todos</option>
                                <option value="1" {{ $mesAtual == 1 ? 'selected' : '' }}>Janeiro</option>
                                <option value="2" {{ $mesAtual == 2 ? 'selected' : '' }}>Fevereiro</option>
                                <option value="3" {{ $mesAtual == 3 ? 'selected' : '' }}>Março</option>
                                <option value="4" {{ $mesAtual == 4 ? 'selected' : '' }}>Abril</option>
                                <option value="5" {{ $mesAtual == 5 ? 'selected' : '' }}>Maio</option>
                                <option value="6" {{ $mesAtual == 6 ? 'selected' : '' }}>Junho</option>
                                <option value="7" {{ $mesAtual == 7 ? 'selected' : '' }}>Julho</option>
                                <option value="8" {{ $mesAtual == 8 ? 'selected' : '' }}>Agosto</option>
                                <option value="9" {{ $mesAtual == 9 ? 'selected' : '' }}>Setembro</option>
                                <option value="10" {{ $mesAtual == 10 ? 'selected' : '' }}>Outubro</option>
                                <option value="11" {{ $mesAtual == 11 ? 'selected' : '' }}>Novembro</option>
                                <option value="12" {{ $mesAtual == 12 ? 'selected' : '' }}>Dezembro</option>
                            </select>
                            <select name="ano" class="form-select form-select-sm" style="width: 100px; font-size: 0.8rem;">
                                <option value="" {{ empty($anoAtual) ? 'selected' : '' }}>Todos</option>
                                @for ($i = date('Y'); $i >= date('Y')-2; $i--)
                                    <option value="{{ $i }}" {{ $anoAtual == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                            <button type="submit" class="btn btn-warning btn-sm ml-2" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                                <i class="fas fa-filter"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <!-- Tabela Juntter Style -->
                <div class="table-responsive">
                    <table id="cobrancasTable" class="table table-hover table-striped">
                        <thead>
                            <tr class="table-header-juntter">
                                <th></th>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                             
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($transacoes) && isset($transacoes['data']) && count($transacoes['data']) > 0)
                                @foreach($transacoes['data'] as $transacao)
                                    <tr>
                                        <td></td>
                                        <td>
                                            <small class="text-muted font-monospace">
                                                {{ substr($transacao['_id'] ?? 'N/A', 0, 8) }}...
                                            </small>
                                        </td>
                                        <td>
                                            @if(isset($transacao['type']))
                                                @if($transacao['type'] === 'PIX')
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-qrcode me-1"></i>PIX
                                                    </span>
                                                @elseif($transacao['type'] === 'CREDIT')
                                                    <span class="badge badge-primary">
                                                        <i class="fas fa-credit-card me-1"></i>Crédito
                                                        @if(isset($transacao['installments']) && $transacao['installments'] > 1)
                                                            <small>({{ $transacao['installments'] }}x)</small>
                                                        @endif
                                                    </span>
                                                @elseif($transacao['type'] === 'DEBIT')
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-credit-card me-1"></i>Débito
                                                    </span>
                                                @elseif($transacao['type'] === 'BILLET' || $transacao['type'] === 'BOLETO')
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-file-invoice me-1"></i>Boleto
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">{{ $transacao['type'] }}</span>
                                                @endif
                                            @else
                                                <span class="badge badge-secondary">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <strong class="text-success">
                                                    R$ {{ number_format(($transacao['amount'] ?? 0) / 100, 2, ',', '.') }}
                                                </strong>
                                                @if(isset($transacao['fees']) && $transacao['fees'] > 0)
                                                    <br><small class="text-muted">Taxa: R$ {{ number_format($transacao['fees'] / 100, 2, ',', '.') }}</small>
                                                @endif
                                    </div>
                                </td>
                                        
                                        <td>
                                            <span class="text-muted">
                                                {{ \Carbon\Carbon::parse($transacao['created_at'] ?? now())->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(isset($transacao['status']))
                                                @if($transacao['status'] === 'PAID')
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check me-1"></i>Pago
                                                    </span>
                                                @elseif($transacao['status'] === 'PENDING')
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-clock me-1"></i>Pendente
                                                    </span>
                                                @elseif($transacao['status'] === 'FAILED')
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times me-1"></i>Falhou
                                                    </span>
                                                @elseif($transacao['status'] === 'CANCELED')
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-ban me-1"></i>Cancelado
                                                    </span>
                                                @elseif($transacao['status'] === 'REFUNDED')
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-undo me-1"></i>Estornado
                                                    </span>
                                                @elseif($transacao['status'] === 'APPROVED')
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check-circle me-1"></i>Aprovado
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary">{{ $transacao['status'] }}</span>
                                                @endif
                                            @else
                                                <span class="badge badge-secondary">Desconhecido</span>
                                            @endif
                                        </td>
                                <td>
                                    <div class="btn-group" role="group">
                                                <a href="{{ ($transacao['type'] ?? '') === 'BILLET' || ($transacao['type'] ?? '') === 'BOLETO' 
                                                    ? route('cobranca.boleto.detalhes', $transacao['_id']) 
                                                    : route('cobranca.transacao.detalhes', $transacao['_id']) }}" 
                                                   class="btn btn-sm btn-outline-info" title="Ver detalhes">
                                                    <i class="fas fa-eye"></i>
                                                    Ver detalhes
                                                </a>
                                                @if(($transacao['status'] ?? '') === 'PAID' || ($transacao['status'] ?? '') === 'APPROVED')
                                                    <form action="{{ route('cobranca.transacao.estornar', $transacao['_id']) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Tem certeza que deseja estornar esta transação de R$ {{ number_format(($transacao['amount'] ?? 0) / 100, 2, ',', '.') }}?')"
                                                                title="Estornar transação">
                                                            <i class="fas fa-undo"></i>
                                                            Estornar
                                        </button>
                                                    </form>
                                                                                                @elseif(($transacao['status'] ?? '') === 'PENDING')
                                                    <form action="{{ route('cobranca.transacao.estornar', $transacao['_id']) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-warning" 
                                                                onclick="return confirm('Tem certeza que deseja cancelar esta transação de R$ {{ number_format(($transacao['amount'] ?? 0) / 100, 2, ',', '.') }}?')"
                                                                title="Cancelar transação">
                                                            <i class="fas fa-ban"></i>
                                                            Cancelar
                                                        </button>
                                                    </form>
                                                @elseif(($transacao['status'] ?? '') === 'REFUNDED')
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-undo me-1"></i>Estornada
                                                    </span>
                                                @endif
                                    </div>
                                </td>
                            </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <br>
                                        Nenhuma transação encontrada
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cobrança -->
<div class="modal fade" id="modalCobranca" tabindex="-1" aria-labelledby="modalCobrancaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalCobrancaLabel">Cobrança única</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                    <!-- Abas de pagamento -->
                    <div class="payment-tabs mb-4">
                        <ul class="nav nav-tabs border-0" id="paymentTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active payment-tab" id="link-tab" data-bs-toggle="tab" data-bs-target="#link-content" type="button" role="tab">
                                PIX
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link payment-tab" id="cartao-tab" data-bs-toggle="tab" data-bs-target="#cartao-content" type="button" role="tab">
                                    Cartão de Crédito
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link payment-tab" id="boleto-tab" data-bs-toggle="tab" data-bs-target="#boleto-content" type="button" role="tab">
                                Boleto Bancário
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mt-4" id="paymentTabsContent">
                        <!-- Aba Link de Pagamento (PIX) -->
                            <div class="tab-pane fade show active" id="link-content" role="tabpanel">
                            <form action="{{ route('cobranca.transacao.pix') }}" method="POST">
                                @csrf
                                <input type="hidden" name="payment_type" value="PIX">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            Valor da transação <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="amount" class="form-control" placeholder="0,00" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            Quem paga as taxas <span class="text-danger">*</span>
                                        </label>
                                        <select name="interest" class="form-select" required>
                                            <option value="">Selecione...</option>
                                            <option value="CLIENT">Cliente</option>
                                            <option value="ESTABLISHMENT">Estabelecimento</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Dados do Cliente (OPCIONAL) -->
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase small text-muted mb-3">
                                            DADOS DO CLIENTE <span class="text-muted">(OPCIONAL)</span>
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Nome do cliente</label>
                                                <input type="text" name="client[first_name]" class="form-control" placeholder="Nome completo">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Sobrenome</label>
                                                <input type="text" name="client[last_name]" class="form-control" placeholder="Sobrenome">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">CPF/CNPJ</label>
                                                <input type="text" name="client[document]" class="form-control" placeholder="000.000.000-00">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Telefone</label>
                                                <input type="text" name="client[phone]" class="form-control" placeholder="(00) 00000-0000">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Email</label>
                                            <input type="email" name="client[email]" class="form-control" placeholder="email@exemplo.com">
                                        </div>
                                    </div>
                                </div>

                                <!-- Informações Adicionais (OPCIONAL) -->
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase small text-muted mb-3">
                                            INFORMAÇÕES ADICIONAIS <span class="text-muted">(OPCIONAL)</span>
                                        </h6>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="form-label fw-bold">Informações Adicionais</label>
                                                <input type="text" name="info_additional" class="form-control" placeholder="Ex: ERP12345, Sistema de origem, etc.">
                                                <small class="text-muted">Informações extras sobre a transação</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Criar Transação PIX
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Aba Cartão de Crédito -->
                        <div class="tab-pane fade" id="cartao-content" role="tabpanel">
                            <form action="{{ route('cobranca.transacao.credito') }}" method="POST">
                                @csrf
                                <input type="hidden" name="payment_type" value="CREDIT">
                                <input type="hidden" name="session_id" id="sessionIdAntifraude" value="session_{{ uniqid() }}">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            Valor da transação <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="amount" class="form-control" placeholder="0,00" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            Número de parcelas <span class="text-danger">*</span>
                                        </label>
                                        <select name="installments" class="form-select" required>
                                            <option value="">Selecione...</option>
                                            <option value="1">1x</option>
                                            <option value="2">2x</option>
                                            <option value="3">3x</option>
                                            <option value="4">4x</option>
                                            <option value="5">5x</option>
                                            <option value="6">6x</option>
                                            <option value="7">7x</option>
                                            <option value="8">8x</option>
                                            <option value="9">9x</option>
                                            <option value="10">10x</option>
                                            <option value="11">11x</option>
                                            <option value="12">12x</option>
                                            <option value="13">13x</option>
                                            <option value="14">14x</option>
                                            <option value="15">15x</option>
                                            <option value="16">16x</option>
                                            <option value="17">17x</option>
                                            <option value="18">18x</option>
                                          
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            Quem paga as taxas <span class="text-danger">*</span>
                                        </label>
                                        <select name="interest" class="form-select" required>
                                            <option value="">Selecione...</option>
                                            <option value="CLIENT">Cliente</option>
                                            <option value="ESTABLISHMENT">Estabelecimento</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Dados do Cliente (OBRIGATÓRIO) -->
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase small text-muted mb-3">
                                            DADOS DO CLIENTE <span class="text-danger">(OBRIGATÓRIO)</span>
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    Nome <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[first_name]" class="form-control" placeholder="Nome completo" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Sobrenome</label>
                                                <input type="text" name="client[last_name]" class="form-control" placeholder="Sobrenome">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    CPF/CNPJ <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[document]" class="form-control" placeholder="000.000.000-00" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    Telefone <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[phone]" class="form-control" placeholder="(00) 00000-0000" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                Email <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" name="client[email]" class="form-control" placeholder="email@exemplo.com" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Endereço do Cliente (OBRIGATÓRIO) -->
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase small text-muted mb-3">
                                            ENDEREÇO DO CLIENTE <span class="text-danger">(OBRIGATÓRIO)</span>
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label class="form-label fw-bold">
                                                    Rua <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[address][street]" class="form-control" placeholder="Nome da rua" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">
                                                    Número <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[address][number]" class="form-control" placeholder="123" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">Complemento</label>
                                                <input type="text" name="client[address][complement]" class="form-control" placeholder="Apto 101">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">
                                                    Bairro <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[address][neighborhood]" class="form-control" placeholder="Centro" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">
                                                    CEP <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[address][zip_code]" class="form-control" placeholder="00000-000" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label class="form-label fw-bold">
                                                    Cidade <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[address][city]" class="form-control" placeholder="Nome da cidade" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">
                                                    Estado <span class="text-danger">*</span>
                                                </label>
                                                <select name="client[address][state]" class="form-select" required>
                                                    <option value="">Selecione...</option>
                                                    <option value="AC">Acre</option>
                                                    <option value="AL">Alagoas</option>
                                                    <option value="AP">Amapá</option>
                                                    <option value="AM">Amazonas</option>
                                                    <option value="BA">Bahia</option>
                                                    <option value="CE">Ceará</option>
                                                    <option value="DF">Distrito Federal</option>
                                                    <option value="ES">Espírito Santo</option>
                                                    <option value="GO">Goiás</option>
                                                    <option value="MA">Maranhão</option>
                                                    <option value="MT">Mato Grosso</option>
                                                    <option value="MS">Mato Grosso do Sul</option>
                                                    <option value="MG">Minas Gerais</option>
                                                    <option value="PA">Pará</option>
                                                    <option value="PB">Paraíba</option>
                                                    <option value="PR">Paraná</option>
                                                    <option value="PE">Pernambuco</option>
                                                    <option value="PI">Piauí</option>
                                                    <option value="RJ">Rio de Janeiro</option>
                                                    <option value="RN">Rio Grande do Norte</option>
                                                    <option value="RS">Rio Grande do Sul</option>
                                                    <option value="RO">Rondônia</option>
                                                    <option value="RR">Roraima</option>
                                                    <option value="SC">Santa Catarina</option>
                                                    <option value="SP">São Paulo</option>
                                                    <option value="SE">Sergipe</option>
                                                    <option value="TO">Tocantins</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dados do Cartão (OBRIGATÓRIO) -->
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase small text-muted mb-3">
                                            DADOS DO CARTÃO <span class="text-danger">(OBRIGATÓRIO)</span>
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    Nome do titular <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="card[holder_name]" class="form-control" placeholder="Nome completo" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">CPF/CNPJ do titular</label>
                                                <input type="text" name="card[holder_document]" class="form-control" placeholder="000.000.000-00">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    Número do cartão <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="card[card_number]" class="form-control" placeholder="0000 0000 0000 0000" required>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label fw-bold">
                                                    Mês <span class="text-danger">*</span>
                                                </label>
                                                <select name="card[expiration_month]" class="form-select" required>
                                                    <option value="">MM</option>
                                                    <option value="1">01</option>
                                                    <option value="2">02</option>
                                                    <option value="3">03</option>
                                                    <option value="4">04</option>
                                                    <option value="5">05</option>
                                                    <option value="6">06</option>
                                                    <option value="7">07</option>
                                                    <option value="8">08</option>
                                                    <option value="9">09</option>
                                                    <option value="10">10</option>
                                                    <option value="11">11</option>
                                                    <option value="12">12</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label fw-bold">
                                                    Ano <span class="text-danger">*</span>
                                                </label>
                                                <select name="card[expiration_year]" class="form-select" required>
                                                    <option value="">AAAA</option>
                                                    <option value="2024">2024</option>
                                                    <option value="2025">2025</option>
                                                    <option value="2026">2026</option>
                                                    <option value="2027">2027</option>
                                                    <option value="2028">2028</option>
                                                    <option value="2029">2029</option>
                                                    <option value="2030">2030</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label fw-bold">
                                                    CVV <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="card[security_code]" class="form-control" placeholder="000" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Criar Transação de Crédito
                                    </button>
                                </div>
                            </form>
                            </div>

                        <!-- Aba Boleto/Pix -->
                        <div class="tab-pane fade" id="boleto-content" role="tabpanel">
                            <form action="{{ route('cobranca.boleto.criar') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            Valor do boleto <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="amount" class="form-control" placeholder="0,00" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">
                                            Data de vencimento <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" name="expiration" class="form-control" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Data limite para pagamento</label>
                                        <input type="date" name="payment_limit_date" class="form-control">
                                        <small class="text-muted">Opcional - Data limite após o vencimento</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">É para recarga?</label>
                                        <select name="recharge" class="form-select">
                                            <option value="0">Não</option>
                                            <option value="1">Sim</option>
                                        </select>
                                        <small class="text-muted">Opcional - Para carteiras digitais</small>
                                    </div>
                                </div>

                                <!-- Dados do Cliente (OBRIGATÓRIO) -->
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase small text-muted mb-3">
                                            DADOS DO CLIENTE <span class="text-danger">(OBRIGATÓRIO)</span>
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    Nome <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[first_name]" class="form-control" placeholder="Nome completo" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    Sobrenome <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[last_name]" class="form-control" placeholder="Sobrenome" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    CPF/CNPJ <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[document]" class="form-control" placeholder="000.000.000-00" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    Email <span class="text-danger">*</span>
                                                </label>
                                                <input type="email" name="client[email]" class="form-control" placeholder="email@exemplo.com" required>
                                            </div>
                                    </div>
                                </div>
                            </div>

                                <!-- Endereço do Cliente (OBRIGATÓRIO) -->
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase small text-muted mb-3">
                                            ENDEREÇO DO CLIENTE <span class="text-danger">(OBRIGATÓRIO)</span>
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label class="form-label fw-bold">
                                                    Rua <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[address][street]" class="form-control" placeholder="Nome da rua" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">
                                                    Número <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[address][number]" class="form-control" placeholder="123" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">Complemento</label>
                                                <input type="text" name="client[address][complement]" class="form-control" placeholder="Apto 101">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">
                                                    Bairro <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[address][neighborhood]" class="form-control" placeholder="Centro" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">
                                                    CEP <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[address][zip_code]" class="form-control" placeholder="00000-000" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label class="form-label fw-bold">
                                                    Cidade <span class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="client[address][city]" class="form-control" placeholder="Nome da cidade" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">
                                                    Estado <span class="text-danger">*</span>
                                                </label>
                                                <select name="client[address][state]" class="form-select" required>
                                                    <option value="">Selecione...</option>
                                                    <option value="AC">Acre</option>
                                                    <option value="AL">Alagoas</option>
                                                    <option value="AP">Amapá</option>
                                                    <option value="AM">Amazonas</option>
                                                    <option value="BA">Bahia</option>
                                                    <option value="CE">Ceará</option>
                                                    <option value="DF">Distrito Federal</option>
                                                    <option value="ES">Espírito Santo</option>
                                                    <option value="GO">Goiás</option>
                                                    <option value="MA">Maranhão</option>
                                                    <option value="MT">Mato Grosso</option>
                                                    <option value="MS">Mato Grosso do Sul</option>
                                                    <option value="MG">Minas Gerais</option>
                                                    <option value="PA">Pará</option>
                                                    <option value="PB">Paraíba</option>
                                                    <option value="PR">Paraná</option>
                                                    <option value="PE">Pernambuco</option>
                                                    <option value="PI">Piauí</option>
                                                    <option value="RJ">Rio de Janeiro</option>
                                                    <option value="RN">Rio Grande do Norte</option>
                                                    <option value="RS">Rio Grande do Sul</option>
                                                    <option value="RO">Rondônia</option>
                                                    <option value="RR">Roraima</option>
                                                    <option value="SC">Santa Catarina</option>
                                                    <option value="SP">São Paulo</option>
                                                    <option value="SE">Sergipe</option>
                                                    <option value="TO">Tocantins</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Instruções do Boleto (OBRIGATÓRIO) -->
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase small text-muted mb-3">
                                            INSTRUÇÕES DO BOLETO <span class="text-danger">(OBRIGATÓRIO)</span>
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">
                                                    É carnê? <span class="text-danger">*</span>
                                                </label>
                                                <select name="instruction[booklet]" class="form-select" required>
                                                    <option value="0">Não</option>
                                                    <option value="1">Sim</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Descrição</label>
                                                <input type="text" name="instruction[description]" class="form-control" placeholder="Descrição do boleto">
                                                <small class="text-muted">Opcional - Descrição exibida no boleto</small>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">
                                                    Multa por atraso <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" name="instruction[late_fee][amount]" class="form-control" placeholder="2,00" required style="width: 80%;">
                                                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                </div>
                                                <small class="text-muted">Ex: 2,00 para 2%</small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">
                                                    Juros ao mês <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" name="instruction[interest][amount]" class="form-control" placeholder="1,00" required style="width: 80%;">
                                                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                </div>
                                                <small class="text-muted">Ex: 1,00 para 1%</small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">
                                                    Desconto <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <input type="text" name="instruction[discount][amount]" class="form-control" placeholder="5,00" required style="width: 80%;">
                                                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                                </div>
                                                <small class="text-muted">Ex: 5,00 para 5%</small>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                Data limite para desconto <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" name="instruction[discount][limit_date]" class="form-control" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Criar Boleto
                                    </button>
                            </div>
                            </form>
                        </div>
                    </div>
            </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal QR Code PIX -->
<div class="modal fade" id="modalQrCodePix" tabindex="-1" aria-labelledby="modalQrCodePixLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalQrCodePixLabel">Pagamento PIX</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 text-center">
                        <h6 class="fw-bold mb-3">QR Code</h6>
                        <div id="qrcode-container" class="mb-3">
                            <!-- QR Code será inserido aqui via JavaScript -->
                        </div>
                        <button class="btn btn-outline-primary btn-sm" onclick="downloadQrCode()">
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
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Como pagar:</strong><br>
                            1. Abra o app do seu banco<br>
                            2. Escolha "PIX" ou "Pagar"<br>
                            3. Escaneie o QR Code ou cole o código
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePixModal()">Fechar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Verificar se há dados PIX para mostrar
document.addEventListener('DOMContentLoaded', function() {
    const pixDataElement = document.getElementById('pix-data');
    if (pixDataElement && pixDataElement.dataset.pixData) {
        const pixData = JSON.parse(pixDataElement.dataset.pixData);
        showPixModal(pixData);
    }
});

function showPixModal(pixData) {
    console.log('Dados PIX recebidos:', pixData);
    
    // Buscar QR Code em base64
    let qrCodeBase64 = '';
    if (pixData.qr_code && pixData.qr_code.qrcode) {
        qrCodeBase64 = pixData.qr_code.qrcode;
    } else if (pixData.qr_code && typeof pixData.qr_code === 'string' && pixData.qr_code.startsWith('data:image')) {
        qrCodeBase64 = pixData.qr_code;
    }
    
    // Buscar código PIX
    let pixCode = '';
    if (pixData.qr_code && pixData.qr_code.emv) {
        pixCode = pixData.qr_code.emv;
    } else if (pixData.transacao && pixData.transacao.emv) {
        pixCode = pixData.transacao.emv;
    }
    
    console.log('QR Code base64 encontrado:', qrCodeBase64 ? 'Sim' : 'Não');
    console.log('Código PIX encontrado:', pixCode);
    
    if (qrCodeBase64) {
        // Mostrar imagem base64 diretamente
        const qrContainer = document.getElementById('qrcode-container');
        qrContainer.innerHTML = `<img src="${qrCodeBase64}" alt="QR Code PIX" class="img-fluid" style="max-width: 200px;">`;
        
        // Preencher código PIX se disponível
        if (pixCode) {
            document.getElementById('pix-code').value = pixCode;
        }
        
        // Mostrar modal
        $('#modalQrCodePix').modal('show');
    } else {
        console.error('QR Code base64 não encontrado nos dados:', pixData);
        alert('Erro: QR Code não encontrado');
    }
}

function copyPixCode() {
    const pixCode = document.getElementById('pix-code');
    pixCode.select();
    pixCode.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Mostrar feedback
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i>';
    button.classList.remove('btn-outline-secondary');
    button.classList.add('btn-success');
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}

function downloadQrCode() {
    const img = document.querySelector('#qrcode-container img');
    if (img) {
        const link = document.createElement('a');
        link.download = 'qrcode-pix.png';
        link.href = img.src;
        link.click();
    }
}

function closePixModal() {
    $('#modalQrCodePix').modal('hide');
}


</script>
@endpush





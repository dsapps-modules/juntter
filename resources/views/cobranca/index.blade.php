@extends('templates.dashboard-template')
@section('title', 'Cobrança Única')

@section('content')
    <!-- Breadcrumb -->
    <x-breadcrumb :items="[['label' => 'Cobranças', 'icon' => 'fas fa-credit-card', 'url' => '#']]" :rightSub="isset($estabelecimento)
        ? ($estabelecimento['first_name'] ?? ($estabelecimento['name'] ?? 'Estabelecimento')) .
            ' • ID ' .
            ($estabelecimento['id'] ?? 'N/A')
        : null" />

    @if (session('pix_data'))
        <div id="pix-data" data-pix-data="{{ json_encode(session('pix_data')) }}" style="display: none;"></div>
    @endif

    <!-- Header com botão -->
    <div class="row align-items-center mb-4">
        <div class="col-12 text-center">
            <h1 class="h3 mb-2 fw-bold">Cobrança Única</h1>
            <p class="text-muted mb-3">Gerencie suas cobranças avulsas</p>
            <div class="d-flex gap-2 justify-content-center">
                <button class="btn btn-novo-pagamento shadow-sm mr-3" data-bs-toggle="modal" data-bs-target="#modalCobranca">
                    <i class="fas fa-plus me-2"></i>
                    Novo Pagamento Único
                </button>
                <button class="btn btn-novo-pagamento shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCreditoVista">
                    <i class="fas fa-credit-card me-2"></i>
                    Crédito à Vista
                </button>
            </div>
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
                                <select name="mes" class="form-select form-select-sm"
                                    style="width: 100px; font-size: 0.8rem;">
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
                                <select name="ano" class="form-select form-select-sm"
                                    style="width: 100px; font-size: 0.8rem;">
                                    <option value="" {{ empty($anoAtual) ? 'selected' : '' }}>Todos</option>
                                    @for ($i = date('Y'); $i >= date('Y') - 2; $i--)
                                        <option value="{{ $i }}" {{ $anoAtual == $i ? 'selected' : '' }}>
                                            {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                                <button type="submit" class="btn btn-warning btn-sm ml-2"
                                    style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
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
                                @if (isset($transacoes) && isset($transacoes['data']) && count($transacoes['data']) > 0)
                                    @foreach ($transacoes['data'] as $transacao)
                                        <tr>
                                            <td></td>
                                            <td>
                                                <small class="text-muted font-monospace">
                                                    {{ $transacao['_id'] ?? 'N/A' }}
                                                </small>
                                            </td>
                                            <td>
                                                @if (isset($transacao['type']))
                                                    @if ($transacao['type'] === 'PIX')
                                                        <span class="badge badge-info">
                                                            <i class="fas fa-qrcode me-1"></i>PIX
                                                        </span>
                                                    @elseif($transacao['type'] === 'CREDIT')
                                                        <span class="badge badge-primary">
                                                            <i class="fas fa-credit-card me-1"></i>Crédito
                                                            @if (isset($transacao['installments']) && $transacao['installments'] > 1)
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
                                                        R$
                                                        {{ number_format(($transacao['amount'] ?? 0) / 100, 2, ',', '.') }}
                                                    </strong>
                                                    @if (isset($transacao['fees']) && $transacao['fees'] > 0)
                                                        <br><small class="text-muted">Taxa: R$
                                                            {{ number_format($transacao['fees'] / 100, 2, ',', '.') }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td
                                                data-order="{{ \Carbon\Carbon::parse($transacao['created_at'] ?? now())->format('Y-m-d H:i:s') }}">
                                                <span class="text-muted">
                                                    {{ \Carbon\Carbon::parse($transacao['created_at'] ?? now())->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                                                </span>
                                            </td>
                                            <td>
                                                @if (isset($transacao['status']))
                                                    @if ($transacao['status'] === 'PAID')
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
                                                        <span
                                                            class="badge badge-secondary">{{ $transacao['status'] }}</span>
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
                                                        class="btn btn-sm btn-outline-info mr-1" title="Ver detalhes">
                                                        <i class="fas fa-eye"></i>
                                                        Ver detalhes
                                                    </a>
                                                    @if (($transacao['status'] ?? '') === 'PAID' || ($transacao['status'] ?? '') === 'APPROVED')
                                                        <form
                                                            action="{{ route('cobranca.transacao.estornar', $transacao['_id']) }}"
                                                            method="POST" style="display: inline;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Tem certeza que deseja estornar esta transação de R$ {{ number_format(($transacao['amount'] ?? 0) / 100, 2, ',', '.') }}?')"
                                                                title="Estornar transação">
                                                                <i class="fas fa-undo"></i>
                                                                Estornar
                                                            </button>
                                                        </form>
                                                    @elseif(($transacao['status'] ?? '') === 'PENDING')
                                                        <form
                                                            action="{{ route('cobranca.transacao.estornar', $transacao['_id']) }}"
                                                            method="POST" style="display: inline;">
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

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $erro)
                                <li>{{ $erro }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="modal-body">
                    <!-- Abas de pagamento -->
                    <div class="payment-tabs mb-4">
                        <ul class="nav nav-tabs border-0" id="paymentTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active payment-tab" id="link-tab" data-bs-toggle="tab"
                                    data-bs-target="#link-content" type="button" role="tab">
                                    PIX
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link payment-tab" id="cartao-tab" data-bs-toggle="tab"
                                    data-bs-target="#cartao-content" type="button" role="tab">
                                    Cartão de Crédito
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link payment-tab" id="boleto-tab" data-bs-toggle="tab"
                                    data-bs-target="#boleto-content" type="button" role="tab">
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
                                            <input type="text" name="amount" value="{{ old('amount') }}"
                                                class="form-control" placeholder="0,00" required>
                                        </div>
                                        <x-form.quem-paga-taxa />
                                    </div>

                                    <!-- Dados do Cliente (OPCIONAL) -->
                                    <x-form.dados-cliente />

                                    <!-- Informações Adicionais (OPCIONAL) -->
                                    <div class="card bg-light border-0 mb-4">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <label class="form-label fw-bold">Observações</label>
                                                    <input type="text" name="info_additional"
                                                        value="{{ old('info_additional') }}" class="form-control">
                                                    <small class="text-muted">Informações extras sobre a transação</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <x-form.submit-row label="Criar Transação Pix" name="link-tab" />
                                </form>
                            </div>

                            <!-- Aba Cartão de Crédito -->
                            <div class="tab-pane fade" id="cartao-content" role="tabpanel">
                                <form data-url="{{ route('cobranca.transacao.credito') }}" method="POST"
                                    id="creditForm">
                                    @csrf
                                    <input type="hidden" name="payment_type" value="CREDIT">
                                    <input type="hidden" name="session_id" id="sessionIdAntifraude"
                                        value="session_{{ uniqid() }}">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">
                                                Valor da transação <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="amount" value="{{ old('amount') }}"
                                                id="amount-credito" class="form-control" placeholder="0,00" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-bold">
                                                Número de parcelas <span class="text-danger">*</span>
                                            </label>
                                            <select name="installments" class="form-select" id="installments" required>
                                                <option value="">Selecione...</option>
                                                <option value="1">À vista (1x)</option>
                                            </select>

                                            <!-- Informação sobre parcelas possíveis -->
                                            <div id="parcelas-info" class="mt-2" style="display: none;">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Máximo de <span id="parcelas-possiveis" class="fw-bold"></span>
                                                    parcelas
                                                    (mínimo R$ 5,00 cada)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <x-form.quem-paga-taxa />
                                    </div>

                                    <!-- Dados do Cliente (OBRIGATÓRIO) -->
                                    <x-form.dados-cliente type="obrigatorio" />

                                    <!-- Endereço do Cliente (OBRIGATÓRIO) -->
                                    <x-form.endereco />

                                    <!-- Dados do Cartão (OBRIGATÓRIO) -->
                                    <x-form.dados-cartao />

                                    <x-form.submit-row label="Criar Transação de Crédito" name="cartao-tab" />
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
                                            <input type="text" name="amount" class="form-control" placeholder="0,00"
                                                required>
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
                                    <x-form.dados-cliente type="obrigatorio" :usePhone="false" />

                                    <!-- Endereço do Cliente (OBRIGATÓRIO) -->
                                    <x-form.endereco />

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
                                                    <input type="text" name="instruction[description]"
                                                        class="form-control" placeholder="Descrição do boleto">
                                                    <small class="text-muted">Opcional - Descrição exibida no
                                                        boleto</small>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label fw-bold">
                                                        Multa por atraso <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="text" name="instruction[late_fee][amount]"
                                                            class="form-control" placeholder="2,00" required
                                                            style="width: 80%;">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-percentage"></i></span>
                                                    </div>
                                                    <small class="text-muted">Ex: 2,00 para 2%</small>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label fw-bold">
                                                        Juros ao mês <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="text" name="instruction[interest][amount]"
                                                            class="form-control" placeholder="1,00" required
                                                            style="width: 80%;">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-percentage"></i></span>
                                                    </div>
                                                    <small class="text-muted">Ex: 1,00 para 1%</small>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label fw-bold">
                                                        Desconto <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="text" name="instruction[discount][amount]"
                                                            class="form-control" placeholder="5,00" required
                                                            style="width: 80%;">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-percentage"></i></span>
                                                    </div>
                                                    <small class="text-muted">Ex: 5,00 para 5%</small>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">
                                                    Data limite para desconto <span class="text-danger">*</span>
                                                </label>
                                                <input type="date" name="instruction[discount][limit_date]"
                                                    id="discount_limit_date" class="form-control" required>
                                            </div>
                                        </div>
                                    </div>

                                    <x-form.submit-row label="Criar Boleto" name="boleto-tab" />
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal QR Code PIX -->
    <div class="modal fade" id="modalQrCodePix" tabindex="-1" aria-labelledby="modalQrCodePixLabel"
        aria-hidden="true">
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

    <!-- Modal Crédito à Vista - Link de Pagamento -->
    <div class="modal fade" id="modalCreditoVista" tabindex="-1" aria-labelledby="modalCreditoVistaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" data-3ds-env="{{ app()->environment('local') ? 'SANDBOX' : 'PROD' }}"
            id="credit-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalCreditoVistaLabel">Crédito à Vista - Link de Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('cobranca.credito-vista.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    Valor <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="valor" id="valor-credito-vista" class="form-control"
                                    placeholder="0,00" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    Quem paga as taxas <span class="text-danger">*</span>
                                </label>
                                <select name="juros" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <option value="CLIENT">Cliente</option>
                                    <option value="ESTABLISHMENT">Estabelecimento</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    Descrição
                                </label>
                                <input type="text" name="descricao" class="form-control"
                                    placeholder="Descrição detalhada do pagamento (opcional)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">
                                    Data de expiração <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="data_expiracao" class="form-control"
                                    id="data_expiracao_credito" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                            </div>
                        </div>


                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-link me-2"></i>
                                Gerar Link de Pagamento
                            </button>
                        </div>
                    </form>
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

            // Inicializar validação dinâmica de parcelas para cartão de crédito
            inicializarValidacaoParcelas();

            // Inicializar máscara monetária para crédito à vista
            inicializarMascaraCreditoVista();

            // Definir data padrão de expiração (7 dias)
            definirDataPadraoExpiracao();

            @if ($errors->any())
                setTimeout(() => {
                    atualizarOpcoesParcelasCredito('{{ old('amount') }}');
                    $('#modalCobranca').modal('show')
                    $('#{{ old('submit') }}').trigger('click')
                }, 1000);
            @endif
        });

        function showPixModal(pixData) {

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
            } else if (pixData.pix_code) {
                pixCode = pixData.pix_code;
            }



            if (qrCodeBase64) {
                // Mostrar imagem base64 diretamente
                const qrContainer = document.getElementById('qrcode-container');
                qrContainer.innerHTML =
                    `<img src="${qrCodeBase64}" alt="QR Code PIX" class="img-fluid" style="max-width: 200px;">`;

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

        // ===== VALIDAÇÃO DINÂMICA DE PARCELAS PARA CARTÃO DE CRÉDITO =====

        function inicializarValidacaoParcelas() {
            const amountInput = document.getElementById('amount-credito');
            const installmentsSelect = document.getElementById('installments');

            if (amountInput && installmentsSelect) {
                // Aplicar máscara monetária
                amountInput.addEventListener('input', function() {
                    let value = this.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        value = (value / 100).toFixed(2) + '';
                        value = value.replace(".", ",");
                        value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
                        value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
                        this.value = 'R$ ' + value;
                    }

                    // Atualizar opções de parcelas
                    atualizarOpcoesParcelasCredito(this.value);
                });

                // Marcar à vista por padrão
                if (!installmentsSelect.value) {
                    installmentsSelect.value = '1';
                }
            }
        }

        function calcularParcelasPossiveisCredito(valor) {
            if (!valor || valor.trim() === '') return 1;

            // Converter valor formatado para número
            const valorNumerico = parseFloat(valor.replace(/[R$\s.]/g, '').replace(',', '.'));

            if (isNaN(valorNumerico) || valorNumerico < 5) return 1;

            // Calcular quantas parcelas são possíveis (mínimo R$ 5,00 cada)
            const parcelasPossiveis = Math.floor(valorNumerico / 5);

            // Limitar a 18 parcelas
            return Math.min(parcelasPossiveis, 18);
        }

        function atualizarOpcoesParcelasCredito(valor) {
            const parcelasPossiveis = calcularParcelasPossiveisCredito(valor);
            const selectParcelas = document.getElementById('installments');
            const parcelasInfo = document.getElementById('parcelas-info');
            const parcelasPossiveisSpan = document.getElementById('parcelas-possiveis');

            // Limpar opções existentes (exceto à vista)
            const options = selectParcelas.querySelectorAll('option:not([value="1"])');
            options.forEach(option => option.remove());

            // Adicionar opções baseadas no valor
            for (let i = 2; i <= parcelasPossiveis; i++) {
                const valorNumerico = parseFloat(valor.replace(/[R$\s.]/g, '').replace(',', '.'));
                const valorParcela = (valorNumerico / i).toFixed(2).replace('.', ',');
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `Até ${i}x sem juros (R$ ${valorParcela} cada)`;
                selectParcelas.appendChild(option);
            }

            // Mostrar informação sobre parcelas possíveis
            if (parcelasPossiveis > 1) {
                parcelasInfo.style.display = 'block';
                parcelasPossiveisSpan.textContent = parcelasPossiveis;
            } else {
                parcelasInfo.style.display = 'none';
            }
        }

        // ===== MÁSCARA MONETÁRIA PARA CRÉDITO À VISTA =====

        function inicializarMascaraCreditoVista() {
            const amountInput = document.getElementById('valor-credito-vista');

            if (amountInput) {
                // Aplicar máscara monetária
                amountInput.addEventListener('input', function() {
                    let value = this.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        value = (value / 100).toFixed(2) + '';
                        value = value.replace(".", ",");
                        value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
                        value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
                        this.value = 'R$ ' + value;
                    }
                });
            }
        }

        // ===== DEFINIR DATA PADRÃO DE EXPIRAÇÃO =====

        function definirDataPadraoExpiracao() {
            const dataExpiracaoInput = document.getElementById('data_expiracao_credito');

            if (dataExpiracaoInput && !dataExpiracaoInput.value) {
                // Definir data padrão de 7 dias a partir de hoje
                const dataExpiracao = new Date();
                dataExpiracao.setDate(dataExpiracao.getDate() + 7);
                dataExpiracaoInput.value = dataExpiracao.toISOString().split('T')[0];
            }
        }

        // Função específica para processar cartão na cobrança única
        function processarCartaoCobranca(button) {
            const form = button.closest('form');
            const originalText = button.innerHTML;

            // Mostrar loading
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processando...';
            button.disabled = true;

            // Coletar dados do formulário
            const formData = new FormData(form);
            const data = {};

            // Converter FormData para objeto
            for (let [key, value] of formData.entries()) {
                const keys = key.split(/[\[\]]/).filter(k => k !== '');
                let current = data;

                for (let i = 0; i < keys.length - 1; i++) {
                    if (!current[keys[i]]) {
                        current[keys[i]] = {};
                    }
                    current = current[keys[i]];
                }
                current[keys[keys.length - 1]] = value;
            }

            // Fazer requisição AJAX com jQuery
            const $form = $(form);
            const url = $form.attr('action') || window.location.href;
            const serializedData = $form.serialize();

            $.post(url, serializedData)
                .done(function(response) {
                    if (response.success) {
                        // Verificar se precisa de autenticação 3DS
                        if (response.requires_3ds && response.session_id) {
                            processar3DSCobranca(response.session_id, response.transaction_id, form, button,
                                originalText);
                        } else {
                            // Sucesso sem 3DS
                            alert('Transação criada com sucesso!');
                            location.reload();
                        }
                    } else {
                        alert('Erro: ' + (response.error || 'Erro desconhecido'));
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .fail(function(xhr) {
                    let error = 'Erro ao processar transação. Tente novamente.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        error = xhr.responseJSON.error;
                    }
                    console.error('Erro:', xhr);
                    alert(error);
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
        }

        // Processar autenticação 3DS para cobrança única
        function processar3DSCobranca(sessionId, transactionId, form, button, originalText) {
            try {
                // Configurar SDK PagSeguro
                PagSeguro.setUp({
                    session: sessionId,
                    env: $('#credit-modal').data('3ds-env')
                });

                // Coletar dados do formulário
                const formData = new FormData(form);
                const data = {};

                for (let [key, value] of formData.entries()) {
                    const keys = key.split(/[\[\]]/).filter(k => k !== '');
                    let current = data;

                    for (let i = 0; i < keys.length - 1; i++) {
                        if (!current[keys[i]]) {
                            current[keys[i]] = {};
                        }
                        current = current[keys[i]];
                    }
                    current[keys[keys.length - 1]] = value;
                }

                // Montar payload
                const request = {
                    data: {
                        customer: {
                            name: data.client.first_name + ' ' + (data.client.last_name || ''),
                            email: data.client.email,
                            phones: [{
                                country: '55',
                                area: data.client.phone.substring(0, 2),
                                number: data.client.phone.substring(2),
                                type: 'MOBILE'
                            }]
                        },
                        paymentMethod: {
                            type: 'CREDIT_CARD',
                            installments: parseInt(data.installments) || 1,
                            card: {
                                number: data.card.card_number.replace(/\s/g, ''),
                                expMonth: data.card.expiration_month.toString().padStart(2, '0'),
                                expYear: data.card.expiration_year.toString(),
                                holder: {
                                    name: data.card.holder_name
                                }
                            }
                        },
                        amount: {
                            value: getAmountFromCobrancaForm(form),
                            currency: 'BRL'
                        },
                        billingAddress: {
                            street: data.client.address.street,
                            number: data.client.address.number,
                            complement: data.client.address.complement || '',
                            regionCode: data.client.address.state,
                            country: 'BRA',
                            city: data.client.address.city,
                            postalCode: data.client.address.zip_code.replace(/\D/g, '')
                        },
                        shippingAddress: {
                            street: data.client.address.street,
                            number: data.client.address.number,
                            complement: data.client.address.complement || '',
                            regionCode: data.client.address.state,
                            country: 'BRA',
                            city: data.client.address.city,
                            postalCode: data.client.address.zip_code.replace(/\D/g, '')
                        },
                        dataOnly: false
                    }
                };

                // Executar autenticação 3DS
                PagSeguro.authenticate3DS(request)
                    .then(function(result) {
                        // Enviar resultado para o endpoint
                        enviarResultado3DSCobranca(transactionId, result, button, originalText);
                    })
                    .catch(function(err) {
                        console.error('Erro no SDK 3DS:', err);

                        if (err instanceof PagSeguro.PagSeguroError) {
                            alert('Erro na autenticação 3DS: ' + err.message);
                        } else {
                            alert('Erro na autenticação 3DS. Tente novamente.');
                        }

                        button.innerHTML = originalText;
                        button.disabled = false;
                    });

            } catch (error) {
                console.error('Erro ao configurar 3DS:', error);
                alert('Erro ao configurar autenticação 3DS');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        // Enviar resultado do 3DS para o backend
        function enviarResultado3DSCobranca(transactionId, result, button, originalText) {
            const authData = {
                id: result.id,
                status: result.status,
                authentication_status: result.authentication_status || 'NOT_AUTHENTICATED',
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            const url = `/cobranca/transacao/${transactionId}/antifraud-auth`;

            $.post(url, authData)
                .done(function(response) {
                    if (response.success) {
                        alert('Pagamento processado com sucesso!');
                        location.reload();
                    } else {
                        alert(response.message || 'Erro ao processar autenticação');
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .fail(function(xhr) {
                    let error = 'Erro ao processar autenticação. Tente novamente.';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        error = xhr.responseJSON.error;
                    }
                    console.error('Erro:', xhr);
                    alert(error);
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
        }

        // Função auxiliar para obter valor do formulário
        function getAmountFromCobrancaForm(form) {
            const amountField = form.querySelector('input[name="amount"]');
            if (amountField) {
                const valueText = amountField.value;
                const cleanValue = valueText.replace(/[R$\s]/g, '').replace(',', '.');
                const value = parseFloat(cleanValue);
                return Math.round(value * 100); // Converter para centavos
            }

            return 100;
        }
    </script>

    <script>
        $(document).ready(function() {

            const $expiration = $('input[name="expiration"]').on('change', adjustDates);
            const $paymentLimit = $('input[name="payment_limit_date"]').on('change', adjustDates);
            const $discountLimit = $('input#discount_limit_date').on('change', adjustDates);

            function toDate(value) {
                return value ? new Date(value + 'T00:00:00') : null;
            }

            function toISODate(date) {
                return date.toISOString().split('T')[0];
            }

            function adjustDates() {
                const expiration = toDate($expiration.val());
                let paymentLimit = toDate($paymentLimit.val());
                let discountLimit = toDate($discountLimit.val());

                if (!expiration) return;

                // 1️⃣ Se payment_limit_date estiver vazio ou <= expiration → expiration + 1 dia
                if (!paymentLimit || paymentLimit <= expiration) {
                    const newDate = new Date(expiration);
                    newDate.setDate(expiration.getDate() + 1);
                    $paymentLimit.val(toISODate(newDate));
                }

                // 2️⃣ Se discount_limit_date >= expiration → expiration - 1 dia
                if (!discountLimit && discountLimit >= expiration) {
                    const newDate = new Date(expiration);
                    newDate.setDate(expiration.getDate() - 1);
                    $discountLimit.val(toISODate(newDate));
                }
            }

            // 🔁 Verificação inicial
            adjustDates();


            $('#zipcode').on('input change keyup', function() {
                const cleaned = $(this).val().replace(/[^0-9]/g, '');
                $(this).val(cleaned);
            });

        });
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js"></script>
    <script src="{{ asset('js/checkout-scripts.js') }}"></script>
@endpush

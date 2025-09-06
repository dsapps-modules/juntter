@extends('templates.dashboard-template')

@section('title', 'Detalhes da Transação')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb :items="$breadcrumbItems" />

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">Transação #{{ substr($transacao['_id'] ?? 'N/A', 0, 8) }}</h3>
                        <p class="text-muted mb-0">Detalhes completos da transação</p>
                    </div>
                    <div>
                        @if(request('from') == 'saldoextrato')
                            <a href="{{ route('cobranca.saldoextrato') }}" class="btn btn-secondary">
                                <i class="fas fa-home mr-2"></i> ao Saldo e Extrato
                            </a>
                        @else
                            <a href="{{ route('cobranca.index') }}" class="btn btn-secondary">
                                <i class="fas fa-home mr-2"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Informações Principais -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="info-card bg-light rounded-3 p-4 mb-3">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-info-circle me-2"></i>Informações Principais
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">ID da Transação</small>
                                        <strong class="font-monospace">{{ $transacao['_id'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Tipo</small>
                                        <strong>
                                            @if(isset($transacao['type']))
                                                @if($transacao['type'] === 'PIX')
                                                    <span class="badge badge-info">PIX</span>
                                                @elseif($transacao['type'] === 'CREDIT')
                                                    <span class="badge badge-primary">Crédito</span>
                                                @elseif($transacao['type'] === 'DEBIT')
                                                    <span class="badge badge-success">Débito</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ $transacao['type'] }}</span>
                                                @endif
                                            @else
                                                <span class="badge badge-secondary">N/A</span>
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Status</small>
                                        <strong>
                                            @if(isset($transacao['status']))
                                                @if($transacao['status'] === 'PAID')
                                                    <span class="badge badge-success">Pago</span>
                                                @elseif($transacao['status'] === 'PENDING')
                                                    <span class="badge badge-warning">Pendente</span>
                                                @elseif($transacao['status'] === 'FAILED')
                                                    <span class="badge badge-danger">Falhou</span>
                                                @elseif($transacao['status'] === 'CANCELED')
                                                    <span class="badge badge-secondary">Cancelado</span>
                                                @elseif($transacao['status'] === 'REFUNDED')
                                                    <span class="badge badge-info">Estornado</span>
                                                @elseif($transacao['status'] === 'APPROVED')
                                                    <span class="badge badge-success">Aprovado</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ $transacao['status'] }}</span>
                                                @endif
                                            @else
                                                <span class="badge badge-secondary">Desconhecido</span>
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Valor Líquido</small>
                                        <strong class="text-success fs-5">
                                            R$ {{ number_format(($transacao['amount'] ?? 0) / 100, 2, ',', '.') }}
                                        </strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Valor Bruto</small>
                                        <strong class="text-primary">
                                            R$ {{ number_format(($transacao['original_amount'] ?? 0) / 100, 2, ',', '.') }}
                                        </strong>
                                    </div>
                                    @if(isset($transacao['fees']) && $transacao['fees'] > 0)
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Taxas</small>
                                        <strong class="text-warning">
                                            R$ {{ number_format($transacao['fees'] / 100, 2, ',', '.') }}
                                        </strong>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="status-card rounded-3 pl-3 mb-3">
                            {{-- <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-chart-line me-2"></i>Informações Adicionais
                            </h6> --}}
                            <div class="status-item mb-3">
                                <small class="text-muted d-block">Gateway</small>
                                <strong>{{ $transacao['gateway_authorization'] ?? 'N/A' }}</strong>
                            </div>
                            <div class="status-item mb-3">
                                <small class="text-muted d-block">Data de Criação</small>
                                <strong>{{ \Carbon\Carbon::parse($transacao['created_at'] ?? now())->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s') }}</strong>
                            </div>

                            @if(isset($transacao['installments']) && $transacao['installments'] > 1)
                            <div class="status-item mb-3">
                                <small class="text-muted d-block">Parcelas</small>
                                <strong>{{ $transacao['installments'] }}x</strong>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Dados do Cliente -->
                @if(isset($transacao['customer']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-user me-2"></i>Dados do Cliente
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Nome</small>
                                        <strong>{{ $transacao['customer']['first_name'] ?? '' }} {{ $transacao['customer']['last_name'] ?? '' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Documento</small>
                                        <strong>{{ $transacao['customer']['document'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Email</small>
                                        <strong>{{ $transacao['customer']['email'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Telefone</small>
                                        <strong>{{ $transacao['customer']['phone'] ?? 'N/A' }}</strong>
                                    </div>
                                    @if(isset($transacao['customer']['address']))
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Endereço</small>
                                        <strong>
                                            {{ $transacao['customer']['address']['street'] ?? '' }}, 
                                            {{ $transacao['customer']['address']['number'] ?? '' }}
                                            {{ $transacao['customer']['address']['complement'] ?? '' }}
                                        </strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Cidade/Estado</small>
                                        <strong>
                                            {{ $transacao['customer']['address']['city'] ?? '' }} - 
                                            {{ $transacao['customer']['address']['state'] ?? '' }}
                                        </strong>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Dados do Cartão (se for crédito/débito) -->
                @if(isset($transacao['card']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-credit-card me-2"></i>Dados do Cartão
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Bandeira</small>
                                        <strong>{{ $transacao['card']['brand_name'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Número</small>
                                        <strong>{{ $transacao['card']['first4_digits'] ?? '' }}****{{ $transacao['card']['last4_digits'] ?? '' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Validade</small>
                                        <strong>{{ $transacao['card']['expiration_month'] ?? '' }}/{{ $transacao['card']['expiration_year'] ?? '' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Portador</small>
                                        <strong>{{ $transacao['card']['holder_name'] ?? 'N/A' }}</strong>
                                    </div>
                                    @if(isset($transacao['card']['holder_document']))
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">CPF/CNPJ do Portador</small>
                                        <strong>{{ $transacao['card']['holder_document'] }}</strong>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Dados da Adquirente -->
                @if(isset($transacao['acquirer']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-building me-2"></i>Dados da Adquirente
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Nome</small>
                                        <strong>{{ $transacao['acquirer']['name'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">NSU</small>
                                        <strong>{{ $transacao['acquirer']['acquirer_nsu'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Gateway Key</small>
                                        <strong class="font-monospace">{{ $transacao['acquirer']['gateway_key'] ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">MID</small>
                                        <strong>{{ $transacao['acquirer']['mid'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Código PIX (se for PIX) -->
                @if(isset($transacao['emv']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-qrcode me-2"></i>Código PIX
                            </h6>
                            <div class="info-item mb-2">
                                <small class="text-muted d-block">Código EMV (Copia e Cola)</small>
                                <div class="input-group no-wrap">
                                    <input type="text" class="form-control font-monospace" value="{{ $transacao['emv'] }}" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" data-value="{{ $transacao['emv'] }}" onclick="copyToClipboard(this)">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Parcelas (se for crédito parcelado) -->
                @if(isset($transacao['expected_on']) && count($transacao['expected_on']) > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-calendar-alt me-2"></i>Parcelas
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Parcela</th>
                                            <th>Data Prevista</th>
                                            <th>Valor</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transacao['expected_on'] as $parcela)
                                        <tr>
                                            <td><strong>{{ $parcela['installment'] }}ª</strong></td>
                                            <td>{{ \Carbon\Carbon::parse($parcela['date'])->format('d/m/Y') }}</td>
                                            <td class="text-success">
                                                <strong>R$ {{ number_format($parcela['amount'] / 100, 2, ',', '.') }}</strong>
                                            </td>
                                            <td>
                                                @if($parcela['status'] === 'PAID')
                                                    <span class="badge badge-success">Paga</span>
                                                @elseif($parcela['status'] === 'PENDING')
                                                    <span class="badge badge-warning">Pendente</span>
                                                @elseif($parcela['status'] === 'CANCELED')
                                                    <span class="badge badge-secondary">Cancelada</span>
                                                @elseif($parcela['status'] === 'REFUNDED')
                                                    <span class="badge badge-info">Estornada</span>
                                                @elseif($parcela['status'] === 'FAILED')
                                                    <span class="badge badge-danger">Falhou</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ $parcela['status'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Antifraude -->
                @if(isset($transacao['antifraud']) && count($transacao['antifraud']) > 0)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-shield-alt me-2"></i>Antifraude
                            </h6>
                            @foreach($transacao['antifraud'] as $antifraud)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Tipo de Análise</small>
                                        <strong>{{ $antifraud['analyse_required'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Status da Análise</small>
                                        <strong>
                                            @if($antifraud['analyse_status'] === 'APPROVED')
                                                <span class="badge badge-success">Aprovado</span>
                                            @elseif($antifraud['analyse_status'] === 'PROCESSING')
                                                <span class="badge badge-warning">Processando</span>
                                            @elseif($antifraud['analyse_status'] === 'WAITING_AUTH')
                                                <span class="badge badge-info">Aguardando Autorização</span>
                                            @elseif($antifraud['analyse_status'] === 'FAILED')
                                                <span class="badge badge-danger">Falhou</span>
                                            @elseif($antifraud['analyse_status'] === 'NO_ANALYSED')
                                                <span class="badge badge-secondary">Não Analisado</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $antifraud['analyse_status'] }}</span>
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Point of Sale -->
                @if(isset($transacao['point_of_sale']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-store me-2"></i>Ponto de Venda
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Tipo</small>
                                        <strong>
                                            @if($transacao['point_of_sale']['type'] === 'ONLINE')
                                                <span class="badge badge-info">Online</span>
                                            @elseif($transacao['point_of_sale']['type'] === 'CHIP')
                                                <span class="badge badge-primary">Chip</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $transacao['point_of_sale']['type'] }}</span>
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Tipo de Identificação</small>
                                        <strong>{{ $transacao['point_of_sale']['identification_type'] ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                            </div>
                            @if(isset($transacao['point_of_sale']['identification_number']))
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Número de Identificação</small>
                                        <strong>{{ $transacao['point_of_sale']['identification_number'] }}</strong>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Payment Response -->
                @if(isset($transacao['payment_response']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-reply me-2"></i>Resposta do Pagamento
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    @if(isset($transacao['payment_response']['code']))
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Código</small>
                                        <strong>{{ $transacao['payment_response']['code'] }}</strong>
                                    </div>
                                    @endif
                                    @if(isset($transacao['payment_response']['message']))
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Mensagem</small>
                                        <strong>{{ $transacao['payment_response']['message'] }}</strong>
                                    </div>
                                    @endif
                                    @if(isset($transacao['payment_response']['reference']))
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Referência</small>
                                        <strong>{{ $transacao['payment_response']['reference'] }}</strong>
                                    </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    @if(isset($transacao['payment_response']['authorization_code']))
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Código de Autorização</small>
                                        <strong>{{ $transacao['payment_response']['authorization_code'] }}</strong>
                                    </div>
                                    @endif
                                    @if(isset($transacao['payment_response']['nsu']))
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">NSU</small>
                                        <strong>{{ $transacao['payment_response']['nsu'] }}</strong>
                                    </div>
                                    @endif
                                    @if(isset($transacao['payment_response']['reason_code']))
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Código do Motivo</small>
                                        <strong>{{ $transacao['payment_response']['reason_code'] }}</strong>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Split (se aplicável) -->
                @if(isset($transacao['split']))
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-share-alt me-2"></i>Split
                            </h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Ativo</small>
                                        <strong>
                                            @if($transacao['split']['active'])
                                                <span class="badge badge-success">Sim</span>
                                            @else
                                                <span class="badge badge-secondary">Não</span>
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Transação Original</small>
                                        <strong>
                                            @if($transacao['split']['is_origin'])
                                                <span class="badge badge-primary">Sim</span>
                                            @else
                                                <span class="badge badge-secondary">Não</span>
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item mb-2">
                                        <small class="text-muted d-block">Em Processamento</small>
                                        <strong>
                                            @if($transacao['split']['processing'])
                                                <span class="badge badge-warning">Sim</span>
                                            @else
                                                <span class="badge badge-success">Não</span>
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function copyToClipboard(button) {
  try {
    const value = button.getAttribute('data-value') || '';
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(value);
    } else {
      // Fallback
      const temp = document.createElement('textarea');
      temp.value = value;
      document.body.appendChild(temp);
      temp.select();
      document.execCommand('copy');
      document.body.removeChild(temp);
    }
    const original = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i>';
    button.classList.remove('btn-outline-secondary');
    button.classList.add('btn-success');
    setTimeout(() => {
      button.innerHTML = original;
      button.classList.remove('btn-success');
      button.classList.add('btn-outline-secondary');
    }, 1500);
  } catch (e) { console.error(e); }
}
</script>
@endpush

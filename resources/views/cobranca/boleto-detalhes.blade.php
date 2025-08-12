@extends('templates.dashboard-template')

@section('title', 'Detalhes do Boleto')

@section('content')
<x-breadcrumb 
    :items="[
        ['label' => 'Cobranças', 'icon' => 'fas fa-credit-card', 'url' => route('cobranca.index')],
        ['label' => 'Boleto', 'icon' => 'fas fa-file-invoice', 'url' => '#']
    ]"
/>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">
                            <i class="fas fa-file-invoice me-2 text-warning"></i>
                            Boleto {{ $boleto['_id'] ?? '' }}
                        </h3>
                        <p class="text-muted mb-0">Detalhes completos do boleto</p>
                    </div>
                    <div>
                        <a href="{{ route('cobranca.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="info-card bg-light rounded-3 p-4 mb-3">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-info-circle me-2"></i>Informações do Boleto
                            </h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Tipo</small>
                                    <strong>{{ $boleto['type'] ?? 'BILLET' }}</strong>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Status</small>
                                    <strong>{{ $boleto['status'] ?? 'N/A' }}</strong>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Recarga</small>
                                    @if(isset($boleto['recharge']) && $boleto['recharge'])
                                        <span class="badge badge-success">Sim</span>
                                    @else
                                        <span class="badge badge-secondary">Não</span>
                                    @endif
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Valor</small>
                                    <strong>R$ {{ number_format(($boleto['amount'] ?? 0) / 100, 2, ',', '.') }}</strong>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Valor Original</small>
                                    <strong>R$ {{ number_format(($boleto['original_amount'] ?? 0) / 100, 2, ',', '.') }}</strong>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Vencimento</small>
                                    <strong>{{ isset($boleto['expiration_at']) ? \Carbon\Carbon::parse($boleto['expiration_at'])->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') : 'N/A' }}</strong>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Data Limite</small>
                                    <strong>{{ isset($boleto['payment_limit_date']) ? \Carbon\Carbon::parse($boleto['payment_limit_date'])->setTimezone('America/Sao_Paulo')->format('d/m/Y') : 'N/A' }}</strong>
                                </div>
                                <div class="col-12 mb-3">
                                    <small class="text-muted d-block">Linha Digitável</small>
                                    <span class="font-monospace">{{ $boleto['digitable_line'] ?? 'N/A' }}</span>
                                </div>
                                <div class="col-12 mb-3">
                                    <small class="text-muted d-block">Código de Barras</small>
                                    <span class="font-monospace">{{ $boleto['barcode'] ?? 'N/A' }}</span>
                                </div>
                                @if(isset($boleto['pix_emv']))
                                <div class="col-12 mb-3">
                                    <small class="text-muted d-block">PIX (Copia e Cola)</small>
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="{{ $boleto['pix_emv'] }}" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('{{ $boleto['pix_emv'] }}')">Copiar</button>
                                    </div>
                                </div>
                                @endif
                                @if(isset($boleto['url']))
                                <div class="col-12">
                                    <a href="{{ $boleto['url'] }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-download me-2"></i>Baixar PDF
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>

                        @if(isset($boleto['billing_instructions']))
                        <div class="info-card bg-light rounded-3 p-4 mb-3">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-list me-2"></i>Instruções
                            </h6>
                            <ul class="mb-0">
                                @foreach($boleto['billing_instructions'] as $inst)
                                    <li class="mb-1">
                                        <strong>{{ $inst['name'] ?? '' }}</strong> - {{ $inst['mode'] ?? '' }}: {{ $inst['amount'] ?? '' }}
                                        @if(isset($inst['limit_date']))
                                            (até {{ \Carbon\Carbon::parse($inst['limit_date'])->format('d/m/Y') }})
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                    <div class="col-lg-4">
                        <div class="info-card bg-light rounded-3 p-4 mb-3">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-user me-2"></i>Cliente
                            </h6>
                            <p class="mb-1"><strong>{{ $boleto['client']['first_name'] ?? 'N/A' }} {{ $boleto['client']['last_name'] ?? '' }}</strong></p>
                            <p class="mb-1"><small class="text-muted">CPF/CNPJ:</small> {{ $boleto['client']['document'] ?? 'N/A' }}</p>
                            <p class="mb-1"><small class="text-muted">Email:</small> {{ $boleto['client']['email'] ?? 'N/A' }}</p>
                        </div>
                        <div class="info-card bg-light rounded-3 p-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-building me-2"></i>Estabelecimento
                            </h6>
                            <p class="mb-1"><small class="text-muted">ID:</small> {{ $boleto['establishment']['id'] ?? ($boleto['establishment_id'] ?? 'N/A') }}</p>
                            <p class="mb-1"><small class="text-muted">Gateway:</small> {{ $boleto['gateway_authorization'] ?? $boleto['gateway_key'] ?? 'N/A' }}</p>
                            <p class="mb-1"><small class="text-muted">Criado em:</small> {{ isset($boleto['created_at']) ? \Carbon\Carbon::parse($boleto['created_at'])->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') : 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



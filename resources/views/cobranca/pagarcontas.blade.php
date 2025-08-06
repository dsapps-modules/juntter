@extends('templates.dashboard-template')

@section('title', 'Pagar Conta')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Cobranças', 'icon' => 'fas fa-credit-card', 'url' => route('cobranca.index')],
        ['label' => 'Pagar Contas', 'icon' => 'fas fa-file-invoice-dollar', 'url' => '#']
    ]"
/>

<!-- Primeira Seção: Informe a linha digitável -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Informe a linha digitável</h5>
                
                <!-- Alert informativo -->
                <div class="alert alert-dark bg-dark text-white border-0 rounded-3 mb-4">
                    <div class="small">
                        <p class="mb-1">Por aqui você pode pagar boletos bancários e contas de consumo (Saneamento, Energia Elétrica, Gás e Telecomunicações).</p>
                        <p class="mb-0">Não é possível realizar pagamentos de tributos (municipais, estaduais e federais).</p>
                    </div>
                </div>
                
                <!-- Campo linha digitável -->
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <label for="linhaDigitavel" class="form-label fw-bold">Informe a linha digitável</label>
                        <input type="text" class="form-control" id="linhaDigitavel" placeholder="Digite a linha digitável do boleto">
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="#" class="text-primary text-decoration-none">
                            <i class="fas fa-pencil-alt me-1"></i>
                            Alterar assinatura eletrônica
                        </a>
                    </div>
                </div>
                
                <!-- Botão Enviar -->
                <div class="row mt-4">
                    <div class="col-12">
                        <button class="btn btn-warning text-white" id="btnEnviarLinha">
                            <i class="fas fa-check me-2"></i>
                            Enviar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Segunda Seção: Informações do boleto -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Informações do boleto</h5>
                
                <form>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="dataVencimento" class="form-label fw-bold">Data de vencimento</label>
                            <input type="text" class="form-control" id="dataVencimento" placeholder="dd/mm/aaaa">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="valorMinimo" class="form-label fw-bold">Valor mínimo</label>
                            <input type="text" class="form-control" id="valorMinimo" placeholder="R$">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="valorPagar" class="form-label fw-bold">Valor a ser pago</label>
                            <input type="text" class="form-control" id="valorPagar" placeholder="R$">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="valorMaximo" class="form-label fw-bold">Valor máximo</label>
                            <input type="text" class="form-control" id="valorMaximo" placeholder="R$">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="assinaturaEletronica" class="form-label fw-bold">
                                Assinatura eletrônica
                                <i class="fas fa-eye ms-2" style="cursor: pointer;"></i>
                            </label>
                            <input type="password" class="form-control" id="assinaturaEletronica">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="taxaConta" class="form-label fw-bold">Taxa atual para cada conta paga</label>
                            <input type="text" class="form-control" id="taxaConta" placeholder="R$">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <!-- Campo vazio para manter layout -->
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="codigoWhatsApp" class="form-label fw-bold">
                                Código enviado por WhatsApp
                                <i class="fab fa-whatsapp ms-2"></i>
                            </label>
                            <input type="text" class="form-control" id="codigoWhatsApp">
                        </div>
                    </div>
                    
                    <!-- Botão Solicitar pagamento -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <button class="btn btn-warning text-white" id="btnSolicitarPagamento">
                                <i class="fas fa-file-alt me-2"></i>
                                Solicitar pagamento
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Scripts consolidados no dashboard.js -->
@endsection 
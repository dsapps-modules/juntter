@extends('templates.dashboard-template')

@section('title', 'Simular Transação')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Cobranças', 'icon' => 'fas fa-credit-card', 'url' => route('cobranca.index')],
        ['label' => 'Simular Transação', 'icon' => 'fas fa-calculator', 'url' => '#']
    ]"
/>

<!-- Header simples -->
<div class="row align-items-center mb-4">
    <div class="col-12 text-center">
        <h1 class="h3 mb-2 fw-bold">Simular Transação</h1>
        <p class="text-muted mb-0">Calcule taxas e valores para diferentes métodos de pagamento</p>
    </div>
</div>

<!-- Card principal -->
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-header bg-transparent border-0 pb-0">
                <h5 class="card-title fw-bold mb-2">Simulação de Transação</h5>
            </div>
            <div class="card-body p-4">
                <!-- Alertas de sessão -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Formulário de simulação -->
                <form action="{{ route('cobranca.transacao.simular') }}" method="POST" id="formSimularTransacao">
                    @csrf
                    
                    <!-- Primeira linha: Valor e Bandeira -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control @error('amount') is-invalid @enderror" 
                                       id="amount" name="amount" placeholder="0,00" required>
                                <label for="amount" class="fw-bold">
                                    <i class="fas fa-dollar-sign me-2 text-success"></i>
                                    Valor da Transação <span class="text-danger">*</span>
                                </label>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select @error('flag_id') is-invalid @enderror" 
                                        id="flag_id" name="flag_id" required>
                                    <option value="">Selecione a bandeira</option>
                                    <option value="1">Mastercard</option>
                                    <option value="2">Visa</option>
                                    <option value="3">Elo</option>
                                    <option value="4">American Express</option>
                                    <option value="5">Hiper/Hipercard</option>
                                    <option value="6">Outras</option>
                                    <option value="8">Bacen</option>
                                </select>
                                <label for="flag_id" class="fw-bold">
                                    <i class="fas fa-credit-card me-2 text-info"></i>
                                    Bandeira do Cartão <span class="text-danger">*</span>
                                </label>
                                @error('flag_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Segunda linha: Quem paga as taxas -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select @error('interest') is-invalid @enderror" 
                                        id="interest" name="interest" required>
                                    <option value="">Selecione</option>
                                    <option value="CLIENT">Cliente</option>
                                    <option value="ESTABLISHMENT">Estabelecimento</option>
                                </select>
                                <label for="interest" class="fw-bold">
                                    <i class="fas fa-percentage me-2 text-warning"></i>
                                    Quem paga as taxas? <span class="text-danger">*</span>
                                </label>
                                @error('interest')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Botões de ação -->
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-calculator me-2"></i>
                                Simular Transação
                            </button>
                            <a href="{{ route('cobranca.index') }}" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-arrow-left me-2"></i>
                                Voltar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <!-- Resultados da Simulação -->
     


    @if(isset($simulacao))
        <div class="row justify-content-center mt-4">
            <div class="col-lg-10 col-xl-8">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h5 class="card-title fw-bold mb-2">
                            @if(isset($simulacao['status']) && $simulacao['status'] == 422)
                                 Erro na Simulação
                            @else
                                 Resultados da Simulação
                            @endif
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @if(isset($simulacao['status']) && $simulacao['status'] == 422)
                            <!-- Exibir erro da API -->
                            <div class="alert alert-danger border-0 shadow-sm">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="alert-heading fw-bold mb-2">
                                            Erro na API: {{ $simulacao['code'] ?? 'Erro desconhecido' }}
                                        </h6>
                                        @if(isset($simulacao['message']) && is_array($simulacao['message']))
                                            <ul class="mb-0 mt-2 list-unstyled">
                                                @foreach($simulacao['message'] as $erro)
                                                    <li class="mb-1">
                                                        <i class="fas fa-times-circle text-danger me-2"></i>
                                                        {{ $erro }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @elseif(isset($simulacao['message']))
                                            <p class="mb-0">{{ $simulacao['message'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 p-4 bg-light rounded-3">
                                <h6 class="text-primary fw-bold mb-3">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    Possíveis soluções:
                                </h6>
                                <ul class="text-muted list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Verifique se todos os campos obrigatórios foram preenchidos
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Certifique-se de que a bandeira do cartão foi selecionada
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Confirme se o valor está no formato correto
                                    </li>
                                </ul>
                            </div>
                        @else
                            <!-- Exibir resultados da simulação -->
                            <div class="row g-4">
                                <!-- Valores principais -->
                                <div class="col-lg-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2">
                                                <i class="fas fa-dollar-sign fa-lg text-success"></i>
                                            </div>
                                            <h6 class="text-muted mb-2">Valor da Transação</h6>
                                            <h5 class="text-success fw-bold mb-0">
                                                R$ {{ number_format($simulacao['amount'] / 100, 2, ',', '.') }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2">
                                                <i class="fas fa-credit-card fa-lg text-info"></i>
                                            </div>
                                            <h6 class="text-muted mb-2">Cartão de Débito</h6>
                                            <h5 class="text-info fw-bold mb-0">
                                                R$ {{ number_format($simulacao['simulation']['debit'] / 100, 2, ',', '.') }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2">
                                                <i class="fas fa-qrcode fa-lg text-success"></i>
                                            </div>
                                            <h6 class="text-muted mb-2">PIX</h6>
                                            <h5 class="text-success fw-bold mb-0">
                                                R$ {{ number_format($simulacao['simulation']['pix'] / 100, 2, ',', '.') }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tabela de parcelamento -->
                            @if(isset($simulacao['simulation']['credit']))
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-transparent border-0 pb-0">
                                                <h6 class="card-title mb-2 fw-bold">
                                                    <i class="fas fa-credit-card me-2"></i>
                                                    Cartão de Crédito - Opções de Parcelamento
                                                </h6>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="table-responsive">
                                                    <table class="table table-hover mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th class="border-0 px-3 py-2 fw-bold">Parcelas</th>
                                                                <th class="border-0 px-3 py-2 fw-bold text-center">Valor da Parcela</th>
                                                                <th class="border-0 px-3 py-2 fw-bold text-end">Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($simulacao['simulation']['credit'] as $parcelas => $dados)
                                                                <tr class="border-bottom">
                                                                    <td class="px-3 py-2">
                                                                        <span class="badge bg-primary">{{ $parcelas }}</span>
                                                                    </td>
                                                                    <td class="px-3 py-2 text-center">
                                                                        <span class="text-muted">R$ {{ number_format($dados['installment'] / 100, 2, ',', '.') }}</span>
                                                                    </td>
                                                                    <td class="px-3 py-2 text-end">
                                                                        <span class="fw-bold text-success">R$ {{ number_format($dados['total'] / 100, 2, ',', '.') }}</span>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                           
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Estilos personalizados -->
<style>


.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,0.05);
}

.badge {
    font-size: 0.875rem !important;
}

.form-floating > .form-control:focus,
.form-floating > .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.btn-lg {
    border-radius: 0.75rem;
}

.alert {
    border-radius: 0.75rem;
}

.card {
    border-radius: 0.75rem;
}
</style>



@push('scripts')
<script>
$(document).ready(function() {
    // Máscara simples para valores monetários (como na cobrança única)
    $('#amount').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 0) {
            value = (value/100).toFixed(2) + '';
            value = value.replace(".", ",");
            value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
            value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
            this.value = 'R$ ' + value;
        }
    });

    // Validação do formulário
    $('#formSimularTransacao').on('submit', function(e) {
        let isValid = true;
        
        // Limpar classes de erro anteriores
        $('.is-invalid').removeClass('is-invalid');
        
        // Validar campo amount
        let amount = $('#amount').val();
        if (!amount || amount.trim() === '') {
            $('#amount').addClass('is-invalid');
            isValid = false;
        }
        
        // Validar campo flag_id
        let flagId = $('#flag_id').val();
        if (!flagId || flagId === '') {
            $('#flag_id').addClass('is-invalid');
            isValid = false;
        }
        
        // Validar campo interest
        let interest = $('#interest').val();
        if (!interest || interest === '') {
            $('#interest').addClass('is-invalid');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            
            // Mostrar mensagem de erro específica
            let errorMsg = 'Por favor, corrija os seguintes campos:\n';
            if (!$('#amount').val()) errorMsg += '• Valor da Transação\n';
            if (!$('#flag_id').val()) errorMsg += '• Bandeira do Cartão\n';
            if (!$('#interest').val()) errorMsg += '• Quem paga as taxas\n';
            
            alert(errorMsg);
            return false;
        }
        
        // Se chegou até aqui, o formulário é válido
        console.log('Formulário válido, enviando...');
    });
});
</script>
@endpush
@endsection
@extends('templates.dashboard-template')

@section('title', 'Link de Pagamento - PIX')
@push('styles')
<style>
    .fade-in {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb
    :items="[
        ['label' => 'Links de Pagamento PIX', 'icon' => 'fas fa-qrcode', 'url' => route('links-pagamento-pix.index')],
        ['label' => 'Criar Novo', 'icon' => 'fas fa-plus', 'url' => '#']
    ]" />

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">
                            <i class="fas fa-qrcode mr-2 text-primary"></i>
                            Link de Pagamento - PIX
                        </h3>
                        <p class="text-muted mb-0">Configure um link para seus clientes realizarem pagamentos PIX instantâneos</p>
                    </div>
                    <div>
                        <a href="{{ route('links-pagamento-pix.index') }}" class="btn btn-secondary">
                            <i class="fas fa-home mr-2"></i>
                        </a>
                    </div>
                </div>

                @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif

                <form action="{{ route('links-pagamento-pix.store') }}" method="POST" id="formLinkPagamentoPix">
                    @csrf

                    <div class="row pt-3">
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">Informações Básicas</h5>
                            <div class="mb-3">
                                <label for="descricao" class="form-label fw-bold">Descrição</label>
                                <input type="text"
                                    class="form-control @error('descricao') is-invalid @enderror"
                                    id="descricao"
                                    name="descricao"
                                    value="{{ old('descricao') }}"
                                    placeholder="Descreva o que o cliente está pagando...">
                                @error('descricao')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                            <label for="juros" class="form-label fw-bold">
                                    Quem paga as taxas <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('juros') is-invalid @enderror" id="juros" name="juros" required>
                                    <option value="">Selecione...</option>
                                    <option value="CLIENT" {{ old('juros') == 'CLIENT' ? 'selected' : '' }}>
                                        Cliente
                                    </option>
                                    <option value="ESTABLISHMENT" {{ old('juros') == 'ESTABLISHMENT' ? 'selected' : '' }}>
                                        Estabelecimento
                                    </option>
                                </select>
                                @error('juros')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">Valor e Configurações</h5>
                            <div class="mb-3">
                                <label for="valor" class="form-label fw-bold">
                                    Valor <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('valor') is-invalid @enderror"
                                    id="valor"
                                    name="valor"
                                    value="{{ old('valor') }}"
                                    placeholder="0,00"
                                    required>
                                @error('valor')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                            <label for="data_expiracao" class="form-label fw-bold">Data de expiração do link</label>
                                <input type="date"
                                    class="form-control @error('data_expiracao') is-invalid @enderror"
                                    id="data_expiracao"
                                    name="data_expiracao"
                                    value="{{ old('data_expiracao') }}"
                                    min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                <small class="text-muted">Opcional - Deixe em branco para link sem expiração</small>
                                @error('data_expiracao')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                 
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="font-weight-bold mb-0">Dados do Cliente (Opcionais)</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggleDadosCliente">
                                    <label class="custom-control-label" for="toggleDadosCliente">
                                        Preencher dados do cliente
                                    </label>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Para PIX:</strong> Os dados do cliente são opcionais. Se preenchidos, serão exibidos no formulário de pagamento.
                            </div>
                        </div>
                    </div>

                    <div id="dadosClienteSection" style="display: none;">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label fw-bold">Nome do cliente</label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.nome') is-invalid @enderror"
                                    id="nome"
                                    name="dados_cliente_preenchidos[nome]"
                                    value="{{ old('dados_cliente_preenchidos.nome') }}"
                                    placeholder="Nome completo">
                                @error('dados_cliente_preenchidos.nome')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                            
                                <label for="sobrenome" class="form-label fw-bold">Sobrenome</label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.sobrenome') is-invalid @enderror"
                                    id="sobrenome"
                                    name="dados_cliente_preenchidos[sobrenome]"
                                    value="{{ old('dados_cliente_preenchidos.sobrenome') }}"
                                    placeholder="Sobrenome">
                                @error('dados_cliente_preenchidos.sobrenome')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                            </div>
                            </div>
                          
                        </div>
                        <div class="col-md-6">
                        <div class="mb-3">
                                <label for="documento" class="form-label fw-bold">CPF/CNPJ</label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.documento') is-invalid @enderror"
                                    id="documento"
                                    name="dados_cliente_preenchidos[documento]"
                                    value="{{ old('dados_cliente_preenchidos.documento') }}"
                                    placeholder="000.000.000-00">
                                @error('dados_cliente_preenchidos.documento')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">Email</label>
                                <input type="email"
                                    class="form-control @error('dados_cliente_preenchidos.email') is-invalid @enderror"
                                    id="email"
                                    name="dados_cliente_preenchidos[email]"
                                    value="{{ old('dados_cliente_preenchidos.email') }}"
                                    placeholder="email@exemplo.com">
                                @error('dados_cliente_preenchidos.email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefone" class="form-label fw-bold">Telefone</label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.telefone') is-invalid @enderror"
                                    id="telefone"
                                    name="dados_cliente_preenchidos[telefone]"
                                    value="{{ old('dados_cliente_preenchidos.telefone') }}"
                                    placeholder="(00) 00000-0000">
                                @error('dados_cliente_preenchidos.telefone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                 
                    </div> <!-- Fechamento da div dadosClienteSection -->


                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('links-pagamento-pix.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times mr-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>Criar Link PIX
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection



@push('scripts')
<script>
$(document).ready(function() {
    // Máscara para valor monetário
    $('#valor').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 0) {
            value = (value/100).toFixed(2) + '';
            value = value.replace(".", ",");
            value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
            value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
            this.value = 'R$ ' + value;
        }
    });

    // Máscara para telefone
    $('#telefone').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length <= 11) {
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            this.value = value;
        }
    });


    // Máscara para CPF/CNPJ
    $('#documento').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length <= 11) {
            // CPF
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        } else {
            // CNPJ
            value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        }
        this.value = value;
    });

    // Event listener para o switch
    $('#toggleDadosCliente').on('change', function() {
        const $section = $('#dadosClienteSection');
        
        if ($(this).is(':checked')) {
            $section.slideDown(300).addClass('fade-in');
        } else {
            $section.slideUp(300).removeClass('fade-in');
        }
    });

});
</script>


@endpush

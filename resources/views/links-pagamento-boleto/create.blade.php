@extends('templates.dashboard-template')

@section('title', 'Link de Pagamento - Boleto')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb
    :items="[
        ['label' => 'Links de Pagamento Boleto', 'icon' => 'fas fa-file-invoice', 'url' => route('links-pagamento-boleto.index')],
        ['label' => 'Criar Novo', 'icon' => 'fas fa-plus', 'url' => '#']
    ]" />

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">
                            <i class="fas fa-file-invoice mr-2 text-warning"></i>
                            Link de Pagamento - Boleto
                        </h3>
                        <p class="text-muted mb-0">Configure um link para seus clientes realizarem pagamentos com boleto bancário</p>
                    </div>
                    <div>
                        <a href="{{ route('links-pagamento-boleto.index') }}" class="btn btn-secondary">
                            <i class="fas fa-home mr-2"></i>
                        </a>
                    </div>
                </div>

                @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif

                <form action="{{ route('links-pagamento-boleto.store') }}" method="POST" id="formLinkPagamentoBoleto">
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
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">Datas do Boleto</h5>
                            <div class="mb-3">
                                <label for="data_vencimento" class="form-label fw-bold">
                                    Data de vencimento <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                    class="form-control @error('data_vencimento') is-invalid @enderror"
                                    id="data_vencimento"
                                    name="data_vencimento"
                                    value="{{ old('data_vencimento') }}"
                                    min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                    required>
                                @error('data_vencimento')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">Data Limite de Pagamento</h5>
                            <div class="mb-3">
                                <label for="data_limite_pagamento" class="form-label fw-bold">
                                    Data limite
                                </label>
                                <input type="date"
                                    class="form-control @error('data_limite_pagamento') is-invalid @enderror"
                                    id="data_limite_pagamento"
                                    name="data_limite_pagamento"
                                    value="{{ old('data_limite_pagamento') }}"
                                    min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                <small class="text-muted">Opcional - Data limite após o vencimento</small>
                                @error('data_limite_pagamento')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-12">
                            <h5 class="fw-bold mb-3">Instruções do Boleto</h5>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Para Boleto:</strong> Configure as instruções que aparecerão no boleto bancário.
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">Descrição</label>
                                <input type="text"
                                    class="form-control @error('instrucoes_boleto.description') is-invalid @enderror"
                                    id="description"
                                    name="instrucoes_boleto[description]"
                                    value="{{ old('instrucoes_boleto.description') }}"
                                    placeholder="Descrição do boleto">
                                <small class="text-muted">Opcional - Descrição exibida no boleto</small>
                                @error('instrucoes_boleto.description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="discount_limit_date" class="form-label fw-bold">
                                    Data limite para desconto <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                    class="form-control @error('instrucoes_boleto.discount.limit_date') is-invalid @enderror"
                                    id="discount_limit_date"
                                    name="instrucoes_boleto[discount][limit_date]"
                                    value="{{ old('instrucoes_boleto.discount.limit_date') }}"
                                    required>
                                @error('instrucoes_boleto.discount.limit_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="late_fee" class="form-label fw-bold">
                                    Multa por atraso <span class="text-danger">*</span>
                                </label>
                                <div class="input-group no-wrap">
                                    <input type="text"
                                        class="form-control @error('instrucoes_boleto.late_fee.amount') is-invalid @enderror"
                                        id="late_fee"
                                        name="instrucoes_boleto[late_fee][amount]"
                                        value="{{ old('instrucoes_boleto.late_fee.amount') }}"
                                        placeholder="2,00"
                                        required>
                                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                </div>
                                <small class="text-muted">Ex: 2,00 para 2%</small>
                                @error('instrucoes_boleto.late_fee.amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="interest" class="form-label fw-bold">
                                    Juros ao mês <span class="text-danger">*</span>
                                </label>
                                <div class="input-group no-wrap">
                                    <input type="text"
                                        class="form-control @error('instrucoes_boleto.interest.amount') is-invalid @enderror"
                                        id="interest"
                                        name="instrucoes_boleto[interest][amount]"
                                        value="{{ old('instrucoes_boleto.interest.amount') }}"
                                        placeholder="1,00"
                                        required>
                                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                </div>
                                <small class="text-muted">Ex: 1,00 para 1%</small>
                                @error('instrucoes_boleto.interest.amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="discount" class="form-label fw-bold">
                                    Desconto <span class="text-danger">*</span>
                                </label>
                                <div class="input-group no-wrap">
                                    <input type="text"
                                        class="form-control @error('instrucoes_boleto.discount.amount') is-invalid @enderror"
                                        id="discount"
                                        name="instrucoes_boleto[discount][amount]"
                                        value="{{ old('instrucoes_boleto.discount.amount') }}"
                                        placeholder="5,00"
                                        required>
                                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                </div>
                                <small class="text-muted">Ex: 5,00 para 5%</small>
                                @error('instrucoes_boleto.discount.amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                  

                    <div class="row">
                        <div class="col-12">
                            <h5 class="fw-bold mb-3">Dados do Cliente (Obrigatórios)</h5>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Para Boleto:</strong> Os dados do cliente são obrigatórios para emissão do boleto bancário.
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="form-label fw-bold">
                                        Nome do cliente <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                        class="form-control @error('dados_cliente_preenchidos.nome') is-invalid @enderror"
                                        id="nome"
                                        name="dados_cliente_preenchidos[nome]"
                                        value="{{ old('dados_cliente_preenchidos.nome') }}"
                                        placeholder="Nome"
                                        required>
                                    @error('dados_cliente_preenchidos.nome')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sobrenome" class="form-label fw-bold">
                                        Sobrenome <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                        class="form-control @error('dados_cliente_preenchidos.sobrenome') is-invalid @enderror"
                                        id="sobrenome"
                                        name="dados_cliente_preenchidos[sobrenome]"
                                        value="{{ old('dados_cliente_preenchidos.sobrenome') }}"
                                        placeholder="Sobrenome"
                                        required>
                                    @error('dados_cliente_preenchidos.sobrenome')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="documento" class="form-label fw-bold">
                                    CPF/CNPJ <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.documento') is-invalid @enderror"
                                    id="documento"
                                    name="dados_cliente_preenchidos[documento]"
                                    value="{{ old('dados_cliente_preenchidos.documento') }}"
                                    placeholder="000.000.000-00"
                                    required>
                                @error('dados_cliente_preenchidos.documento')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email"
                                    class="form-control @error('dados_cliente_preenchidos.email') is-invalid @enderror"
                                    id="email"
                                    name="dados_cliente_preenchidos[email]"
                                    value="{{ old('dados_cliente_preenchidos.email') }}"
                                    placeholder="email@exemplo.com"
                                    required>
                                @error('dados_cliente_preenchidos.email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="telefone" class="form-label fw-bold">
                                    Telefone <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.telefone') is-invalid @enderror"
                                    id="telefone"
                                    name="dados_cliente_preenchidos[telefone]"
                                    value="{{ old('dados_cliente_preenchidos.telefone') }}"
                                    placeholder="(00) 00000-0000"
                                    required>
                                @error('dados_cliente_preenchidos.telefone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h5 class="fw-bold mb-3">Endereço (Obrigatório)</h5>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="rua" class="form-label fw-bold">
                                    Rua <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.endereco.rua') is-invalid @enderror"
                                    id="rua"
                                    name="dados_cliente_preenchidos[endereco][rua]"
                                    value="{{ old('dados_cliente_preenchidos.endereco.rua') }}"
                                    placeholder="Nome da rua"
                                    required>
                                @error('dados_cliente_preenchidos.endereco.rua')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="numero" class="form-label fw-bold">
                                    Número <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.endereco.numero') is-invalid @enderror"
                                    id="numero"
                                    name="dados_cliente_preenchidos[endereco][numero]"
                                    value="{{ old('dados_cliente_preenchidos.endereco.numero') }}"
                                    placeholder="123"
                                    required>
                                @error('dados_cliente_preenchidos.endereco.numero')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="complemento" class="form-label fw-bold">Complemento</label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.endereco.complemento') is-invalid @enderror"
                                    id="complemento"
                                    name="dados_cliente_preenchidos[endereco][complemento]"
                                    value="{{ old('dados_cliente_preenchidos.endereco.complemento') }}"
                                    placeholder="Apto 101">
                                @error('dados_cliente_preenchidos.endereco.complemento')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="bairro" class="form-label fw-bold">
                                    Bairro <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.endereco.bairro') is-invalid @enderror"
                                    id="bairro"
                                    name="dados_cliente_preenchidos[endereco][bairro]"
                                    value="{{ old('dados_cliente_preenchidos.endereco.bairro') }}"
                                    placeholder="Centro"
                                    required>
                                @error('dados_cliente_preenchidos.endereco.bairro')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cep" class="form-label fw-bold">
                                    CEP <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.endereco.cep') is-invalid @enderror"
                                    id="cep"
                                    name="dados_cliente_preenchidos[endereco][cep]"
                                    value="{{ old('dados_cliente_preenchidos.endereco.cep') }}"
                                    placeholder="00000-000"
                                    required>
                                @error('dados_cliente_preenchidos.endereco.cep')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="cidade" class="form-label fw-bold">
                                    Cidade <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('dados_cliente_preenchidos.endereco.cidade') is-invalid @enderror"
                                    id="cidade"
                                    name="dados_cliente_preenchidos[endereco][cidade]"
                                    value="{{ old('dados_cliente_preenchidos.endereco.cidade') }}"
                                    placeholder="Nome da cidade"
                                    required>
                                @error('dados_cliente_preenchidos.endereco.cidade')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="estado" class="form-label fw-bold">
                                    Estado <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('dados_cliente_preenchidos.endereco.estado') is-invalid @enderror" id="estado" name="dados_cliente_preenchidos[endereco][estado]" required>
                                    <option value="">Selecione...</option>
                                    <option value="AC" {{ old('dados_cliente_preenchidos.endereco.estado') == 'AC' ? 'selected' : '' }}>Acre</option>
                                    <option value="AL" {{ old('dados_cliente_preenchidos.endereco.estado') == 'AL' ? 'selected' : '' }}>Alagoas</option>
                                    <option value="AP" {{ old('dados_cliente_preenchidos.endereco.estado') == 'AP' ? 'selected' : '' }}>Amapá</option>
                                    <option value="AM" {{ old('dados_cliente_preenchidos.endereco.estado') == 'AM' ? 'selected' : '' }}>Amazonas</option>
                                    <option value="BA" {{ old('dados_cliente_preenchidos.endereco.estado') == 'BA' ? 'selected' : '' }}>Bahia</option>
                                    <option value="CE" {{ old('dados_cliente_preenchidos.endereco.estado') == 'CE' ? 'selected' : '' }}>Ceará</option>
                                    <option value="DF" {{ old('dados_cliente_preenchidos.endereco.estado') == 'DF' ? 'selected' : '' }}>Distrito Federal</option>
                                    <option value="ES" {{ old('dados_cliente_preenchidos.endereco.estado') == 'ES' ? 'selected' : '' }}>Espírito Santo</option>
                                    <option value="GO" {{ old('dados_cliente_preenchidos.endereco.estado') == 'GO' ? 'selected' : '' }}>Goiás</option>
                                    <option value="MA" {{ old('dados_cliente_preenchidos.endereco.estado') == 'MA' ? 'selected' : '' }}>Maranhão</option>
                                    <option value="MT" {{ old('dados_cliente_preenchidos.endereco.estado') == 'MT' ? 'selected' : '' }}>Mato Grosso</option>
                                    <option value="MS" {{ old('dados_cliente_preenchidos.endereco.estado') == 'MS' ? 'selected' : '' }}>Mato Grosso do Sul</option>
                                    <option value="MG" {{ old('dados_cliente_preenchidos.endereco.estado') == 'MG' ? 'selected' : '' }}>Minas Gerais</option>
                                    <option value="PA" {{ old('dados_cliente_preenchidos.endereco.estado') == 'PA' ? 'selected' : '' }}>Pará</option>
                                    <option value="PB" {{ old('dados_cliente_preenchidos.endereco.estado') == 'PB' ? 'selected' : '' }}>Paraíba</option>
                                    <option value="PR" {{ old('dados_cliente_preenchidos.endereco.estado') == 'PR' ? 'selected' : '' }}>Paraná</option>
                                    <option value="PE" {{ old('dados_cliente_preenchidos.endereco.estado') == 'PE' ? 'selected' : '' }}>Pernambuco</option>
                                    <option value="PI" {{ old('dados_cliente_preenchidos.endereco.estado') == 'PI' ? 'selected' : '' }}>Piauí</option>
                                    <option value="RJ" {{ old('dados_cliente_preenchidos.endereco.estado') == 'RJ' ? 'selected' : '' }}>Rio de Janeiro</option>
                                    <option value="RN" {{ old('dados_cliente_preenchidos.endereco.estado') == 'RN' ? 'selected' : '' }}>Rio Grande do Norte</option>
                                    <option value="RS" {{ old('dados_cliente_preenchidos.endereco.estado') == 'RS' ? 'selected' : '' }}>Rio Grande do Sul</option>
                                    <option value="RO" {{ old('dados_cliente_preenchidos.endereco.estado') == 'RO' ? 'selected' : '' }}>Rondônia</option>
                                    <option value="RR" {{ old('dados_cliente_preenchidos.endereco.estado') == 'RR' ? 'selected' : '' }}>Roraima</option>
                                    <option value="SC" {{ old('dados_cliente_preenchidos.endereco.estado') == 'SC' ? 'selected' : '' }}>Santa Catarina</option>
                                    <option value="SP" {{ old('dados_cliente_preenchidos.endereco.estado') == 'SP' ? 'selected' : '' }}>São Paulo</option>
                                    <option value="SE" {{ old('dados_cliente_preenchidos.endereco.estado') == 'SE' ? 'selected' : '' }}>Sergipe</option>
                                    <option value="TO" {{ old('dados_cliente_preenchidos.endereco.estado') == 'TO' ? 'selected' : '' }}>Tocantins</option>
                                </select>
                                @error('dados_cliente_preenchidos.endereco.estado')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('links-pagamento-boleto.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times mr-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>Criar Link Boleto
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
    // Máscara para valor
    $('#valor').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        value = (value / 100).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
        this.value = value;
    });

    // Máscara para telefone
    $('#telefone').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length <= 11) {
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        }
        this.value = value;
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

    // Máscara para CEP
    $('#cep').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        value = value.replace(/(\d{5})(\d{3})/, '$1-$2');
        this.value = value;
    });
});
</script>
@endpush
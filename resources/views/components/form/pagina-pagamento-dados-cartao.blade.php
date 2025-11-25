@props(['link'])

<form id="creditForm" data-url="{{ route('pagamento.cartao', $link->codigo_unico) }}">
    @csrf

    <!-- Dados do Cartão -->
    <div class="row">
        <div class="col-12">
            <div class="form-section">
                <h6 class="section-title">
                    <i class="fas fa-credit-card me-2"></i>
                    Dados do Cartão
                </h6>

                <!-- Parcelamento integrado -->
                @if ($link->parcelas > 1)
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">
                                Parcelas <span class="text-danger">*</span>
                            </label>
                            <select name="installments" class="form-select" required>
                                <option value="">Selecione...</option>
                                @for ($i = 1; $i <= $link->parcelas; $i++)
                                    <option value="{{ $i }}">
                                        {{ $i }}x de R$
                                        {{ number_format($link->valor / $i, 2, ',', '.') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Nome do titular <span class="text-danger">*</span></label>
                        <input type="text" name="card[holder_name]" class="form-control" placeholder="Nome completo"
                            required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Número do cartão <span class="text-danger">*</span></label>
                        <div class="form-group">
                            <input type="text" name="card[card_number]" class="form-control"
                                placeholder="0000 0000 0000 0000" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <label class="form-label">Mês <span class="text-danger">*</span></label>
                        <select name="card[expiration_month]" class="form-select" required>
                            <option value="">MM</option>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">
                                    {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3 mb-4">
                        <label class="form-label">Ano <span class="text-danger">*</span></label>
                        <select name="card[expiration_year]" class="form-select" required>
                            <option value="">AAAA</option>
                            @for ($y = date('Y'); $y <= date('Y') + 10; $y++)
                                <option value="{{ $y }}">{{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3 mb-4">
                        <label class="form-label">CVV <span class="text-danger">*</span></label>
                        <div class="form-group">
                            <input type="text" name="card[security_code]" class="form-control" placeholder="123"
                                maxlength="4" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dados do Cliente e Endereço -->

        <div class="col-12">
            <div class="form-section">
                <h6 class="section-title">
                    <i class="fas fa-user me-2"></i>
                    Dados do Cliente
                </h6>

                <!-- Resumo dos dados preenchidos -->
                @if (isset($link->dados_cliente['preenchidos']) &&
                        $link->dados_cliente['preenchidos']['nome'] &&
                        $link->dados_cliente['preenchidos']['sobrenome'] &&
                        $link->dados_cliente['preenchidos']['email'] &&
                        $link->dados_cliente['preenchidos']['telefone'] &&
                        $link->dados_cliente['preenchidos']['documento']
                )
                    <div class="prefilled-data-card">
                        <div class="prefilled-header">
                            <div class="prefilled-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="prefilled-title">
                                <h6>Dados Pré-preenchidos</h6>
                                <small>Informações já cadastradas</small>
                            </div>
                        </div>
                        <div class="prefilled-content">
                            <div class="data-item">
                                <i class="fas fa-user"></i>
                                <span>{{ $link->dados_cliente['preenchidos']['nome'] }}
                                    {{ $link->dados_cliente['preenchidos']['sobrenome'] }}</span>
                            </div>
                            <div class="data-item">
                                <i class="fas fa-envelope"></i>
                                <span>{{ $link->dados_cliente['preenchidos']['email'] }}</span>
                            </div>
                            <div class="data-item">
                                <i class="fas fa-phone"></i>
                                <span>{{ $link->dados_cliente['preenchidos']['telefone'] }}</span>
                            </div>
                            <div class="data-item">
                                <i class="fas fa-id-card"></i>
                                <span>{{ $link->dados_cliente['preenchidos']['documento'] }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Campos editáveis (mostrar se não preenchidos ou se clicou em editar) -->
                <div id="clientFields"
                    style="display: {{ isset($link->dados_cliente['preenchidos']) && $link->dados_cliente['preenchidos']['nome'] && $link->dados_cliente['preenchidos']['sobrenome'] && $link->dados_cliente['preenchidos']['email'] && $link->dados_cliente['preenchidos']['telefone'] && $link->dados_cliente['preenchidos']['documento'] ? 'none' : 'block' }};">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" name="client[first_name]" class="form-control"
                                placeholder="Nome completo"
                                value="{{ $link->dados_cliente['preenchidos']['nome'] ?? '' }}" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Sobrenome <span class="text-danger">*</span></label>
                            <input type="text" name="client[last_name]" class="form-control" placeholder="Sobrenome"
                                value="{{ $link->dados_cliente['preenchidos']['sobrenome'] ?? '' }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <div class="form-group">
                                <input type="email" name="client[email]" class="form-control"
                                    placeholder="email@exemplo.com"
                                    value="{{ $link->dados_cliente['preenchidos']['email'] ?? '' }}" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Telefone <span class="text-danger">*</span></label>
                            <div class="form-group">
                                <input type="text" name="client[phone]" class="form-control"
                                    placeholder="(00) 00000-0000"
                                    value="{{ $link->dados_cliente['preenchidos']['telefone'] ?? '' }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label">CPF/CNPJ <span class="text-danger">*</span></label>
                            <div class="form-group">
                                <input type="text" name="client[document]" class="form-control"
                                    placeholder="000.000.000-00"
                                    value="{{ $link->dados_cliente['preenchidos']['documento'] ?? '' }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumo do endereço -->
                @if (isset($link->dados_cliente['preenchidos']['endereco']) &&
                        $link->dados_cliente['preenchidos']['endereco']['rua'] &&
                        $link->dados_cliente['preenchidos']['endereco']['numero'] &&
                        $link->dados_cliente['preenchidos']['endereco']['bairro'] &&
                        $link->dados_cliente['preenchidos']['endereco']['cidade'] &&
                        $link->dados_cliente['preenchidos']['endereco']['estado'] &&
                        $link->dados_cliente['preenchidos']['endereco']['cep']
                )
                    <div class="prefilled-data-card">
                        <div class="prefilled-header">
                            <div class="prefilled-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="prefilled-title">
                                <h6>Endereço Pré-preenchido</h6>
                                <small>Informações de entrega já cadastradas</small>
                            </div>
                        </div>
                        <div class="prefilled-content">
                            <div class="data-item">
                                <i class="fas fa-road"></i>
                                <span>{{ $link->dados_cliente['preenchidos']['endereco']['rua'] }},
                                    {{ $link->dados_cliente['preenchidos']['endereco']['numero'] }}</span>
                            </div>
                            <div class="data-item">
                                <i class="fas fa-map-pin"></i>
                                <span>{{ $link->dados_cliente['preenchidos']['endereco']['bairro'] }}
                                    -
                                    {{ $link->dados_cliente['preenchidos']['endereco']['cidade'] }}/{{ $link->dados_cliente['preenchidos']['endereco']['estado'] }}</span>
                            </div>
                            <div class="data-item">
                                <i class="fas fa-mail-bulk"></i>
                                <span>CEP:
                                    {{ $link->dados_cliente['preenchidos']['endereco']['cep'] }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Campos de endereço editáveis (mostrar se não preenchidos ou se clicou em editar) -->
                <div id="addressFields"
                    style="display: {{ isset($link->dados_cliente['preenchidos']['endereco']) && $link->dados_cliente['preenchidos']['endereco']['rua'] && $link->dados_cliente['preenchidos']['endereco']['numero'] && $link->dados_cliente['preenchidos']['endereco']['bairro'] && $link->dados_cliente['preenchidos']['endereco']['cidade'] && $link->dados_cliente['preenchidos']['endereco']['estado'] && $link->dados_cliente['preenchidos']['endereco']['cep'] ? 'none' : 'block' }};">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <label class="form-label">CEP <span class="text-danger">*</span></label>
                            <div class="form-group">
                                <input type="text" name="client[address][zip_code]" class="form-control"
                                    placeholder="00000-000"
                                    value="{{ $link->dados_cliente['preenchidos']['endereco']['cep'] ?? '' }}"
                                    required>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-search me-1"></i>Digite o CEP para
                                auto-preencher
                            </small>
                        </div>
                        <div class="col-md-8 mb-4">
                            <label class="form-label">Rua <span class="text-danger">*</span></label>
                            <input type="text" name="client[address][street]" class="form-control"
                                placeholder="Nome da rua"
                                value="{{ $link->dados_cliente['preenchidos']['endereco']['rua'] ?? '' }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <label class="form-label">Número <span class="text-danger">*</span></label>
                            <input type="text" name="client[address][number]" class="form-control"
                                placeholder="123"
                                value="{{ $link->dados_cliente['preenchidos']['endereco']['numero'] ?? '' }}"
                                required>
                        </div>
                        <div class="col-md-3 mb-4">
                            <label class="form-label">Complemento</label>
                            <input type="text" name="client[address][complement]" class="form-control"
                                placeholder="Apto, casa, etc."
                                value="{{ $link->dados_cliente['preenchidos']['endereco']['complemento'] ?? '' }}">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Bairro <span class="text-danger">*</span></label>
                            <input type="text" name="client[address][neighborhood]" class="form-control"
                                placeholder="Bairro"
                                value="{{ $link->dados_cliente['preenchidos']['endereco']['bairro'] ?? '' }}"
                                required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-4">
                            <label class="form-label">Cidade <span class="text-danger">*</span></label>
                            <input type="text" name="client[address][city]" class="form-control"
                                placeholder="Cidade"
                                value="{{ $link->dados_cliente['preenchidos']['endereco']['cidade'] ?? '' }}"
                                required>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label">Estado <span class="text-danger">*</span></label>
                            <select name="client[address][state]" class="form-select" required>
                                <option value="">Selecione...</option>
                                <option value="AC"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'AC' ? 'selected' : '' }}>
                                    Acre</option>
                                <option value="AL"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'AL' ? 'selected' : '' }}>
                                    Alagoas</option>
                                <option value="AP"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'AP' ? 'selected' : '' }}>
                                    Amapá</option>
                                <option value="AM"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'AM' ? 'selected' : '' }}>
                                    Amazonas</option>
                                <option value="BA"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'BA' ? 'selected' : '' }}>
                                    Bahia</option>
                                <option value="CE"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'CE' ? 'selected' : '' }}>
                                    Ceará</option>
                                <option value="DF"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'DF' ? 'selected' : '' }}>
                                    Distrito Federal</option>
                                <option value="ES"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'ES' ? 'selected' : '' }}>
                                    Espírito Santo</option>
                                <option value="GO"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'GO' ? 'selected' : '' }}>
                                    Goiás</option>
                                <option value="MA"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'MA' ? 'selected' : '' }}>
                                    Maranhão</option>
                                <option value="MT"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'MT' ? 'selected' : '' }}>
                                    Mato Grosso</option>
                                <option value="MS"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'MS' ? 'selected' : '' }}>
                                    Mato Grosso do Sul</option>
                                <option value="MG"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'MG' ? 'selected' : '' }}>
                                    Minas Gerais</option>
                                <option value="PA"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'PA' ? 'selected' : '' }}>
                                    Pará</option>
                                <option value="PB"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'PB' ? 'selected' : '' }}>
                                    Paraíba</option>
                                <option value="PR"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'PR' ? 'selected' : '' }}>
                                    Paraná</option>
                                <option value="PE"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'PE' ? 'selected' : '' }}>
                                    Pernambuco</option>
                                <option value="PI"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'PI' ? 'selected' : '' }}>
                                    Piauí</option>
                                <option value="RJ"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'RJ' ? 'selected' : '' }}>
                                    Rio de Janeiro</option>
                                <option value="RN"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'RN' ? 'selected' : '' }}>
                                    Rio Grande do Norte</option>
                                <option value="RS"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'RS' ? 'selected' : '' }}>
                                    Rio Grande do Sul</option>
                                <option value="RO"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'RO' ? 'selected' : '' }}>
                                    Rondônia</option>
                                <option value="RR"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'RR' ? 'selected' : '' }}>
                                    Roraima</option>
                                <option value="SC"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'SC' ? 'selected' : '' }}>
                                    Santa Catarina</option>
                                <option value="SP"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'SP' ? 'selected' : '' }}>
                                    São Paulo</option>
                                <option value="SE"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'SE' ? 'selected' : '' }}>
                                    Sergipe</option>
                                <option value="TO"
                                    {{ ($link->dados_cliente['preenchidos']['endereco']['estado'] ?? '') == 'TO' ? 'selected' : '' }}>
                                    Tocantins</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="text-center mt-4">
            <button type="submit" class="btn btn-payment">
                <i class="fas fa-credit-card me-2"></i>
                Finalizar Pagamento
            </button>
        </div>
    </div>
</form>

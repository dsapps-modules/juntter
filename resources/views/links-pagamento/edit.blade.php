@extends('templates.dashboard-template')

@section('title', 'Editar Link de Pagamento - Cartão')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Links de Pagamento', 'icon' => 'fas fa-link', 'url' => route('links-pagamento.index')],
        ['label' => 'Editar', 'icon' => 'fas fa-edit', 'url' => '#']
    ]"
/>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">Editar Link de Pagamento - Cartão</h3>
                        <p class="text-muted mb-0">Atualize as configurações do seu link de pagamento com cartão de crédito</p>
                    </div>
                    <div>
                        <a href="{{ route('links-pagamento.show', $linkPagamento->id) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye me-2 mr-2 d-inline-block"></i>Visualizar
                        </a>
                        <a href="{{ route('links-pagamento.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2 mr-2 d-inline-block"></i>Voltar
                        </a>
                    </div>
                </div>
                
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                
                <form action="{{ route('links-pagamento.update', $linkPagamento->id) }}" method="POST" id="formLinkPagamento">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">Informações Básicas</h5>
                            <div class="mb-3">
                                <label for="descricao" class="form-label fw-bold">Descrição</label>
                                <input type="text" 
                                       class="form-control @error('descricao') is-invalid @enderror" 
                                       id="descricao" 
                                       name="descricao" 
                                       value="{{ old('descricao', $linkPagamento->descricao) }}" 
                                       placeholder="Descreva o que o cliente está pagando...">
                                @error('descricao')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if(!$linkPagamento->is_avista)
                            <div class="mb-3">
                                <label for="parcelas" class="form-label">Número máximo de parcelas:</label>
                                <select class="form-select" id="parcelas" name="parcelas">
                                    <option value="1" {{ old('parcelas', $linkPagamento->parcelas) == '1' ? 'selected' : '' }}>À vista (1x)</option>
                                    @for($i = 2; $i <= 18; $i++)
                                        <option value="{{ $i }}" {{ old('parcelas', $linkPagamento->parcelas) == $i ? 'selected' : '' }}>Até {{ $i }}x sem juros</option>
                                    @endfor
                                </select>
                                
                                <div id="parcelas-info" class="mt-2 small text-muted" style="display: none;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <span id="parcelas-possiveis"></span>
                                </div>

                                @error('parcelas')
                                <div class="text-danger small mt-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                </div>
                                @enderror
                            </div>
                            @else
                            <div class="mb-3">
                                <label class="form-label">Tipo de pagamento:</label>
                                <div class="form-control-plaintext">
                                    <span class="badge bg-primary">À vista (1x)</span>
                                    <small class="text-muted d-block">Este link foi criado para pagamentos à vista</small>
                                </div>
                                <input type="hidden" name="parcelas" value="1">
                            </div>
                            @endif
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
                                       value="{{ old('valor', $linkPagamento->valor) }}" 
                                       placeholder="0,00" 
                                       required>
                                @error('valor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="juros" class="form-label fw-bold">
                                    Quem paga as taxas <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('juros') is-invalid @enderror" id="juros" name="juros" required>
                                    <option value="">Selecione...</option>
                                    <option value="CLIENT" {{ old('juros', $linkPagamento->juros) == 'CLIENT' ? 'selected' : '' }}>
                                        Cliente
                                    </option>
                                    <option value="ESTABLISHMENT" {{ old('juros', $linkPagamento->juros) == 'ESTABLISHMENT' ? 'selected' : '' }}>
                                        Estabelecimento
                                    </option>
                                </select>
                                @error('juros')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="data_expiracao" class="form-label fw-bold">Data de Expiração</label>
                                <input type="date" 
                                       class="form-control @error('data_expiracao') is-invalid @enderror" 
                                       id="data_expiracao" 
                                       name="data_expiracao" 
                                       value="{{ old('data_expiracao', $linkPagamento->data_expiracao ? $linkPagamento->data_expiracao->format('Y-m-d') : '') }}" 
                                       min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                @error('data_expiracao')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Deixe em branco para não expirar
                                </small>
                            </div>
                            </div>
                        </div>
                    </div>
                    
                    @if(!$linkPagamento->is_avista)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="font-weight-bold mb-0">Dados do Cliente (Opcionais)</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggleDadosCliente">
                                    <label class="custom-control-label" for="toggleDadosCliente">
                                        Preencher dados
                                    </label>
                                </div>
                            </div>
                           

                            @php
                                $dadosCliente = $linkPagamento->dados_cliente['preenchidos'] ?? [];
                            @endphp
                            <div id="dadosClienteSection" style="display: {{ !empty($dadosCliente) ? 'block' : 'none' }};">
                            <div class="mb-3">
                             
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nome_cliente" class="form-label">Nome do Cliente</label>
                                        <input type="text" class="form-control" id="nome_cliente" name="dados_cliente_preenchidos[nome]" value="{{ old('dados_cliente_preenchidos.nome', $linkPagamento->dados_cliente['preenchidos']['nome'] ?? '') }}" placeholder="Nome">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="sobrenome_cliente" class="form-label">Sobrenome do Cliente</label>
                                        <input type="text" class="form-control" id="sobrenome_cliente" name="dados_cliente_preenchidos[sobrenome]" value="{{ old('dados_cliente_preenchidos.sobrenome', $linkPagamento->dados_cliente['preenchidos']['sobrenome'] ?? '') }}" placeholder="Sobrenome">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email_cliente" class="form-label">Email do Cliente</label>
                                    <input type="email" class="form-control" id="email_cliente" name="dados_cliente_preenchidos[email]" value="{{ old('dados_cliente_preenchidos.email', $linkPagamento->dados_cliente['preenchidos']['email'] ?? '') }}" placeholder="email@exemplo.com">
                                </div>

                                <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="documento_cliente" class="form-label">CPF/CNPJ do Cliente</label>
                                    <input type="text" class="form-control" id="documento_cliente" name="dados_cliente_preenchidos[documento]" value="{{ old('dados_cliente_preenchidos.documento', $linkPagamento->dados_cliente['preenchidos']['documento'] ?? '') }}" placeholder="000.000.000-00">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telefone_cliente" class="form-label">Telefone do Cliente</label>
                                    <input type="text" class="form-control" id="telefone_cliente" name="dados_cliente_preenchidos[telefone]" value="{{ old('dados_cliente_preenchidos.telefone', $linkPagamento->dados_cliente['preenchidos']['telefone'] ?? '') }}" placeholder="(00) 00000-0000">
                                </div>
                                </div>
                               
                            </div>
                        </div>

                        </div>

                        
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="font-weight-bold mb-0">Endereço (Opcional)</h5>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="toggleEndereco">
                                    <label class="custom-control-label" for="toggleEndereco">
                                        Preencher endereço
                                    </label>
                                </div>
                            </div>
                         

                            @php
                                $enderecoCliente = $linkPagamento->dados_cliente['preenchidos']['endereco'] ?? [];
                            @endphp
                            <div id="enderecoSection" style="display: {{ !empty($enderecoCliente) ? 'block' : 'none' }};">
                                
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="rua_cliente" class="form-label">Rua</label>
                                        <input type="text" class="form-control" id="rua_cliente" name="dados_cliente_preenchidos[endereco][rua]" value="{{ old('dados_cliente_preenchidos.endereco.rua', $linkPagamento->dados_cliente['preenchidos']['endereco']['rua'] ?? '') }}" placeholder="Nome da rua">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="numero_cliente" class="form-label">Número</label>
                                        <input type="text" class="form-control" id="numero_cliente" name="dados_cliente_preenchidos[endereco][numero]" value="{{ old('dados_cliente_preenchidos.endereco.numero', $linkPagamento->dados_cliente['preenchidos']['endereco']['numero'] ?? '') }}" placeholder="123">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="bairro_cliente" class="form-label">Bairro</label>
                                        <input type="text" class="form-control" id="bairro_cliente" name="dados_cliente_preenchidos[endereco][bairro]" value="{{ old('dados_cliente_preenchidos.endereco.bairro', $linkPagamento->dados_cliente['preenchidos']['endereco']['bairro'] ?? '') }}" placeholder="Nome do bairro">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="cidade_cliente" class="form-label">Cidade</label>
                                        <input type="text" class="form-control" id="cidade_cliente" name="dados_cliente_preenchidos[endereco][cidade]" value="{{ old('dados_cliente_preenchidos.endereco.cidade', $linkPagamento->dados_cliente['preenchidos']['endereco']['cidade'] ?? '') }}" placeholder="Nome da cidade">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="cep_cliente" class="form-label">CEP</label>
                                        <input type="text" class="form-control" id="cep_cliente" name="dados_cliente_preenchidos[endereco][cep]" value="{{ old('dados_cliente_preenchidos.endereco.cep', $linkPagamento->dados_cliente['preenchidos']['endereco']['cep'] ?? '') }}" placeholder="00000-000">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="estado_cliente" class="form-label">Estado</label>
                                        <select class="form-select" id="estado_cliente" name="dados_cliente_preenchidos[endereco][estado]">
                                            <option value="">Selecione... </option>
                                            <option value="AC" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'AC' ? 'selected' : '' }}>Acre</option>
                                            <option value="AL" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'AL' ? 'selected' : '' }}>Alagoas</option>
                                            <option value="AP" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'AP' ? 'selected' : '' }}>Amapá</option>
                                            <option value="AM" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'AM' ? 'selected' : '' }}>Amazonas</option>
                                            <option value="BA" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'BA' ? 'selected' : '' }}>Bahia</option>
                                            <option value="CE" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'CE' ? 'selected' : '' }}>Ceará</option>
                                            <option value="DF" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'DF' ? 'selected' : '' }}>Distrito Federal</option>
                                            <option value="ES" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'ES' ? 'selected' : '' }}>Espírito Santo</option>
                                            <option value="GO" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'GO' ? 'selected' : '' }}>Goiás</option>
                                            <option value="MA" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'MA' ? 'selected' : '' }}>Maranhão</option>
                                            <option value="MT" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'MT' ? 'selected' : '' }}>Mato Grosso</option>
                                            <option value="MS" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'MS' ? 'selected' : '' }}>Mato Grosso do Sul</option>
                                            <option value="MG" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'MG' ? 'selected' : '' }}>Minas Gerais</option>
                                            <option value="PA" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'PA' ? 'selected' : '' }}>Pará</option>
                                            <option value="PB" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'PB' ? 'selected' : '' }}>Paraíba</option>
                                            <option value="PR" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'PR' ? 'selected' : '' }}>Paraná</option>
                                            <option value="PE" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'PE' ? 'selected' : '' }}>Pernambuco</option>
                                            <option value="PI" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'PI' ? 'selected' : '' }}>Piauí</option>
                                            <option value="RJ" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'RJ' ? 'selected' : '' }}>Rio de Janeiro</option>
                                            <option value="RN" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'RN' ? 'selected' : '' }}>Rio Grande do Norte</option>
                                            <option value="RS" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'RS' ? 'selected' : '' }}>Rio Grande do Sul</option>
                                            <option value="RO" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'RO' ? 'selected' : '' }}>Rondônia</option>
                                            <option value="RR" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'RR' ? 'selected' : '' }}>Roraima</option>
                                            <option value="SC" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'SC' ? 'selected' : '' }}>Santa Catarina</option>
                                            <option value="SP" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'SP' ? 'selected' : '' }}>São Paulo</option>
                                            <option value="SE" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'SE' ? 'selected' : '' }}>Sergipe</option>
                                            <option value="TO" {{ (old('dados_cliente_preenchidos.endereco.estado', $linkPagamento->dados_cliente['preenchidos']['endereco']['estado'] ?? '')) == 'TO' ? 'selected' : '' }}>Tocantins</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="complemento_cliente" class="form-label">Complemento</label>
                                        <input type="text" class="form-control" id="complemento_cliente" name="dados_cliente_preenchidos[endereco][complemento]" value="{{ old('dados_cliente_preenchidos.endereco.complemento', $linkPagamento->dados_cliente['preenchidos']['endereco']['complemento'] ?? '') }}" placeholder="Apto, casa, etc.">
                                    </div>

                                </div>
                            </div>
                         </div>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-12">
                            <h5 class="fw-bold mb-3">URLs de Configuração</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="url_retorno" class="form-label fw-bold">URL de Retorno</label>
                                <input type="url" 
                                       class="form-control @error('url_retorno') is-invalid @enderror" 
                                       id="url_retorno" 
                                       name="url_retorno" 
                                       value="{{ old('url_retorno', $linkPagamento->url_retorno) }}" 
                                       placeholder="https://seusite.com/obrigado">
                                @error('url_retorno')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    URL para onde o cliente será redirecionado após o pagamento
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="url_webhook" class="form-label fw-bold">URL do Webhook</label>
                                <input type="url" 
                                       class="form-control @error('url_webhook') is-invalid @enderror" 
                                       id="url_webhook" 
                                       name="url_webhook" 
                                       value="{{ old('url_webhook', $linkPagamento->url_webhook) }}" 
                                       placeholder="https://seusite.com/webhook">
                                @error('url_webhook')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    URL para receber notificações de mudança de status
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-end">
                                <a href="{{ route('links-pagamento.show', $linkPagamento->id) }}" class="btn btn-secondary">
                                    <i class="fas fa-times mr-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary ml-2">
                                    <i class="fas fa-save mr-2"></i>Salvar Alterações
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

@push('styles')
<style>
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    const VALOR_MINIMO_PARCELA = 5.00; // R$ 5,00 por parcela
    
    // Função para calcular parcelas possíveis
    function calcularParcelasPossiveis(valor) {
        if (!valor || valor <= 0) return 1;
        
        const valorNumerico = parseFloat(valor.replace(/[R$\s.]/g, '').replace(',', '.'));
        const maxParcelas = Math.floor(valorNumerico / VALOR_MINIMO_PARCELA);
        
        return Math.max(1, Math.min(maxParcelas, 18));
    }
    
    // Função para atualizar opções de parcelas
    function atualizarOpcoesParcelas(valor) {
        const parcelasPossiveis = calcularParcelasPossiveis(valor);
        const selectParcelas = $('#parcelas');
        const parcelasInfo = $('#parcelas-info');
        const parcelasPossiveisSpan = $('#parcelas-possiveis');
        
        // Limpar opções existentes (exceto à vista)
        selectParcelas.find('option:not([value="1"])').remove();
        
        // Converter valor formatado para numérico
        const valorNumerico = parseFloat(valor.replace(/[R$\s.]/g, '').replace(',', '.'));
        
        // Adicionar opções baseadas no valor
        for (let i = 2; i <= parcelasPossiveis; i++) {
            const valorParcela = (valorNumerico / i).toFixed(2).replace('.', ',');
            selectParcelas.append(`<option value="${i}">Até ${i}x sem juros (R$ ${valorParcela} cada)</option>`);
        }
        
        // Mostrar informação sobre parcelas possíveis
        if (parcelasPossiveis > 1) {
            parcelasInfo.show();
            parcelasPossiveisSpan.text(`Com R$ ${valor} você pode parcelar em até ${parcelasPossiveis}x (mínimo R$ ${VALOR_MINIMO_PARCELA.toFixed(2).replace('.', ',')} por parcela)`);
        } else {
            parcelasInfo.hide();
        }
        
        // Ajustar valor selecionado se necessário
        const valorAtual = selectParcelas.val();
        if (valorAtual > parcelasPossiveis) {
            selectParcelas.val('1');
        }
    }
    
    // Máscara para valor monetário
    $('#valor').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 0) {
            value = (value/100).toFixed(2) + '';
            value = value.replace(".", ",");
            value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
            value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
            this.value = 'R$ ' + value;
            
            // Atualizar opções de parcelas
            atualizarOpcoesParcelas(this.value);
        }
    });
    
    // Validação do formulário
    $('#formLinkPagamento').submit(function(e) {
        let parcelas = $('select[name="parcelas"]').val();
        let valor = $('#valor').val();
        
        if (!parcelas) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Atenção!',
                text: 'Selecione uma opção de parcelamento'
            });
            return false;
        }
        
        // Validar se o valor permite a parcela selecionada
        if (parcelas > 1) {
            const valorNumerico = parseFloat(valor.replace(/[R$\s.]/g, '').replace(',', '.'));
            const valorParcela = valorNumerico / parcelas;
            
            if (valorParcela < VALOR_MINIMO_PARCELA) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Valor insuficiente para parcelamento',
                    text: `Para parcelar em ${parcelas}x, cada parcela deve ser pelo menos R$ ${VALOR_MINIMO_PARCELA.toFixed(2).replace('.', ',')}. Valor atual por parcela: R$ ${valorParcela.toFixed(2).replace('.', ',')}`
                });
                return false;
            }
        }
    });

    // Atualizar data mínima de expiração
    $('#data_expiracao').attr('min', new Date().toISOString().split('T')[0]);
    
    // Marcar à vista por padrão se nenhuma parcela estiver selecionada
    if (!$('select[name="parcelas"]').val()) {
        $('#parcelas').val('1');
    }
    
    // Formatar valor existente e inicializar parcelas
    const valorAtual = $('#valor').val();
    if (valorAtual && !valorAtual.includes('R$')) {
        const valorFormatado = 'R$ ' + parseFloat(valorAtual).toFixed(2).replace('.', ',');
        $('#valor').val(valorFormatado);
    }
    
    // Inicializar opções de parcelas com valor existente
    if (valorAtual) {
        atualizarOpcoesParcelas($('#valor').val());
    }

    // Verificar se há dados preenchidos nos campos do cliente
    function verificarDadosCliente() {
        const nome = $('#nome_cliente').val();
        const email = $('#email_cliente').val();
        const telefone = $('#telefone_cliente').val();
        const documento = $('#documento_cliente').val();
        
        return nome || email || telefone || documento;
    }

    // Verificar se há dados preenchidos nos campos de endereço
    function verificarEndereco() {
        const rua = $('#rua_cliente').val();
        const numero = $('#numero_cliente').val();
        const bairro = $('#bairro_cliente').val();
        const cidade = $('#cidade_cliente').val();
        const cep = $('#cep_cliente').val();
        const estado = $('#estado_cliente').val();
        
        return rua || numero || bairro || cidade || cep || estado;
    }
    
    // Definir estado inicial do switch de dados do cliente
    if (verificarDadosCliente()) {
        $('#toggleDadosCliente').prop('checked', true);
        $('#dadosClienteSection').show().addClass('fade-in');
    } else {
        $('#toggleDadosCliente').prop('checked', false);
        $('#dadosClienteSection').hide().removeClass('fade-in');
    }

    // Definir estado inicial do switch de endereço
    if (verificarEndereco()) {
        $('#toggleEndereco').prop('checked', true);
        $('#enderecoSection').show().addClass('fade-in');
    } else {
        $('#toggleEndereco').prop('checked', false);
        $('#enderecoSection').hide().removeClass('fade-in');
    }
    
    // Event listener para o switch de dados do cliente
    $('#toggleDadosCliente').on('change', function() {
        const $section = $('#dadosClienteSection');
        
        if ($(this).is(':checked')) {
            $section.slideDown(300).addClass('fade-in');
        } else {
            $section.slideUp(300).removeClass('fade-in');
        }
    });

    // Event listener para o switch de endereço
    $('#toggleEndereco').on('change', function() {
        const $section = $('#enderecoSection');
        
        if ($(this).is(':checked')) {
            $section.slideDown(300).addClass('fade-in');
        } else {
            $section.slideUp(300).removeClass('fade-in');
        }
    });
});
</script>
@endpush

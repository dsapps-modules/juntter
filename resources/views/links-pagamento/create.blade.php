@extends('templates.dashboard-template')

@section('title', 'Criar Novo Link de Pagamento - Cartão')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb
    :items="[
        ['label' => 'Links de Pagamento', 'icon' => 'fas fa-link', 'url' => route('links-pagamento.index')],
        ['label' => 'Criar Novo', 'icon' => 'fas fa-plus', 'url' => '#']
    ]" />

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="h4 mb-1 fw-bold">Criar Novo Link de Pagamento - Cartão</h3>
                        <p class="text-muted mb-0">Configure um link para seus clientes realizarem pagamentos com cartão de crédito</p>
                    </div>
                    <div>
                        <a href="{{ route('links-pagamento.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2 d-inline-block"></i>Voltar
                        </a>
                    </div>
                </div>

                @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
                @endif

                <form action="{{ route('links-pagamento.store') }}" method="POST" id="formLinkPagamento">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">Informações Básicas</h5>
                            <div class="mb-3">
                                <label for="titulo" class="form-label fw-bold">
                                    Título do Link <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control @error('titulo') is-invalid @enderror"
                                    id="titulo"
                                    name="titulo"
                                    value="{{ old('titulo') }}"
                                    placeholder="Ex: Pagamento Consulta Médica"
                                    required>
                                @error('titulo')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
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
                                <label for="parcelas" class="form-label">Número máximo de parcelas:</label>
                                <select class="form-select" id="parcelas" name="parcelas">
                                    <option value="1" {{ old('parcelas') == '1' ? 'selected' : '' }}>À vista (1x)</option>
                                    @for($i = 2; $i <= 18; $i++)
                                        <option value="{{ $i }}" {{ old('parcelas') == $i ? 'selected' : '' }}>Até {{ $i }}x sem juros</option>
                                        @endfor
                                </select>

                                @error('parcelas')
                                <div class="text-danger small mt-2">
                                    <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
                                </div>
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
                            <div class="mb-3">
                                <label for="data_expiracao" class="form-label fw-bold">Data de Expiração</label>
                                <input type="date"
                                    class="form-control @error('data_expiracao') is-invalid @enderror"
                                    id="data_expiracao"
                                    name="data_expiracao"
                                    value="{{ old('data_expiracao') }}"
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

                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3">Dados do Cliente (Opcional)</h5>
                            <div class="mb-3">
                             
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nome_cliente" class="form-label">Nome do Cliente</label>
                                        <input type="text" class="form-control" id="nome_cliente" name="dados_cliente_preenchidos[nome]" value="{{ old('dados_cliente_preenchidos.nome') }}" placeholder="Nome">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="sobrenome_cliente" class="form-label">Sobrenome do Cliente</label>
                                        <input type="text" class="form-control" id="sobrenome_cliente" name="dados_cliente_preenchidos[sobrenome]" value="{{ old('dados_cliente_preenchidos.sobrenome') }}" placeholder="Sobrenome">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email_cliente" class="form-label">Email do Cliente</label>
                                    <input type="email" class="form-control" id="email_cliente" name="dados_cliente_preenchidos[email]" value="{{ old('dados_cliente_preenchidos.email') }}" placeholder="email@exemplo.com">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="telefone_cliente" class="form-label">Telefone do Cliente</label>
                                    <input type="text" class="form-control" id="telefone_cliente" name="dados_cliente_preenchidos[telefone]" value="{{ old('dados_cliente_preenchidos.telefone') }}" placeholder="(00) 00000-0000">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="documento_cliente" class="form-label">CPF/CNPJ do Cliente</label>
                                    <input type="text" class="form-control" id="documento_cliente" name="dados_cliente_preenchidos[documento]" value="{{ old('dados_cliente_preenchidos.documento') }}" placeholder="000.000.000-00">
                                </div>
                               
                            </div>
                        </div>

                        <div class="col-md-6">
                       
                    <h5 class="fw-bold  mb-3">Endereço (Opcional)</h5>
                                
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="rua_cliente" class="form-label">Rua</label>
                                        <input type="text" class="form-control" id="rua_cliente" name="dados_cliente_preenchidos[endereco][rua]" value="{{ old('dados_cliente_preenchidos.endereco.rua') }}" placeholder="Nome da rua">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="numero_cliente" class="form-label">Número</label>
                                        <input type="text" class="form-control" id="numero_cliente" name="dados_cliente_preenchidos[endereco][numero]" value="{{ old('dados_cliente_preenchidos.endereco.numero') }}" placeholder="123">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="bairro_cliente" class="form-label">Bairro</label>
                                        <input type="text" class="form-control" id="bairro_cliente" name="dados_cliente_preenchidos[endereco][bairro]" value="{{ old('dados_cliente_preenchidos.endereco.bairro') }}" placeholder="Nome do bairro">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="cidade_cliente" class="form-label">Cidade</label>
                                        <input type="text" class="form-control" id="cidade_cliente" name="dados_cliente_preenchidos[endereco][cidade]" value="{{ old('dados_cliente_preenchidos.endereco.cidade') }}" placeholder="Nome da cidade">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="cep_cliente" class="form-label">CEP</label>
                                        <input type="text" class="form-control" id="cep_cliente" name="dados_cliente_preenchidos[endereco][cep]" value="{{ old('dados_cliente_preenchidos.endereco.cep') }}" placeholder="00000-000">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="estado_cliente" class="form-label">Estado</label>
                                        <select class="form-select" id="estado_cliente" name="dados_cliente_preenchidos[endereco][estado]">
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
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="complemento_cliente" class="form-label">Complemento</label>
                                        <input type="text" class="form-control" id="complemento_cliente" name="dados_cliente_preenchidos[endereco][complemento]" value="{{ old('dados_cliente_preenchidos.endereco.complemento') }}" placeholder="Apto, casa, etc.">
                                    </div>

                                </div>
                        </div>
                    </div>
                                    

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
                                    value="{{ old('url_retorno') }}"
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
                                    value="{{ old('url_webhook') }}"
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
                            <button type="submit" class="btn btn-primary me-3">
                                <i class="fas fa-plus-circle mr-2 d-inline-block"></i>Criar Link
                            </button>
                            <a href="{{ route('links-pagamento.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times mr-2 d-inline-block"></i>Cancelar
                            </a>
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
        // Validação do formulário
        $('#formLinkPagamento').submit(function(e) {
            let parcelas = $('select[name="parcelas"]').val();

            if (!parcelas) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Atenção!',
                    text: 'Selecione uma opção de parcelamento'
                });
                return false;
            }
        });

        // Atualizar data mínima de expiração
        $('#data_expiracao').attr('min', new Date().toISOString().split('T')[0]);

        // Método de pagamento fixo: cartão de crédito

        // Marcar à vista por padrão
        if (!$('select[name="parcelas"]').val()) {
            $('#parcelas').val('1');
        }
    });
</script>
@endpush

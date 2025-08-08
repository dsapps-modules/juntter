@extends('templates.dashboard-template')

@section('title', 'Enviar Pix')

@section('content')
<!-- Breadcrumb -->
<x-breadcrumb 
    :items="[
        ['label' => 'Cobranças', 'icon' => 'fas fa-credit-card', 'url' => route('cobranca.index')],
        ['label' => 'PIX', 'icon' => 'fas fa-qrcode', 'url' => '#']
    ]"
/>

<!-- Alert -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning bg-warning text-white border-0 rounded-3 shadow-sm">
            <i class="fas fa-paper-plane me-2"></i>
            Envie pix direto da conta da clínica.
        </div>
    </div>
</div>

<!-- Header com botão -->
<div class="row align-items-center mb-4">
    <div class="col-12 text-center">
        <h1 class="h3 mb-2 fw-bold">Enviar Pix</h1>
        <p class="text-muted mb-3">Gerencie suas transferências Pix</p>
        <button class="btn btn-novo-pagamento shadow-sm" 
                data-bs-toggle="modal" 
                data-bs-target="#modalPix">
            <i class="fas fa-plus me-2"></i>
            Novo Pix
        </button>
    </div>
</div>

<!-- Tabela de Pix -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <!-- Tabela Juntter Style -->
                <div class="table-responsive">
                    <table id="pixTable" class="table table-hover table-striped">
                        <thead>
                            <tr class="table-header-juntter">
                                <th></th>
                                <th>Beneficiário</th>
                                <th>Valor</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td></td>
                                <td><strong>João Silva</strong></td>
                                <td><strong class="text-success">R$ 150,00</strong></td>
                                <td><span class="text-muted">15/12/2024</span></td>
                                <td><span class="badge badge-success">Enviado</span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><strong>Maria Santos</strong></td>
                                <td><strong class="text-warning">R$ 300,00</strong></td>
                                <td><span class="text-muted">12/12/2024</span></td>
                                <td><span class="badge badge-warning">Pendente</span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><strong>Pedro Oliveira</strong></td>
                                <td><strong class="text-danger">R$ 220,00</strong></td>
                                <td><span class="text-muted">10/12/2024</span></td>
                                <td><span class="badge badge-danger">Falhou</span></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-info" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pix -->
<div class="modal fade" id="modalPix" tabindex="-1" aria-labelledby="modalPixLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalPixLabel">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Pix
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs de navegação -->
                <ul class="nav nav-tabs border-0 mb-4" id="pixTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active pix-tab" id="chave-tab" data-bs-toggle="tab" data-bs-target="#chave-content" type="button" role="tab">
                            Transferir via chave Pix
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link pix-tab" id="agencia-tab" data-bs-toggle="tab" data-bs-target="#agencia-content" type="button" role="tab">
                            Transferir via agência e conta
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link pix-tab" id="copia-tab" data-bs-toggle="tab" data-bs-target="#copia-content" type="button" role="tab">
                            Pix copia e cola
                        </button>
                    </li>
                </ul>

                <!-- Conteúdo das tabs -->
                <div class="tab-content" id="pixTabsContent">
                    <!-- Tab Chave Pix -->
                    <div class="tab-pane fade show active" id="chave-content" role="tabpanel">
                        <form id="formPixChave">
                            <div class="mb-3">
                                <label for="tipoChave" class="form-label fw-bold">Tipo de chave</label>
                                <select class="form-select" id="tipoChave" required>
                                    <option value="celular">Celular</option>
                                    <option value="email">E-mail</option>
                                    <option value="cpf">CPF</option>
                                    <option value="cnpj">CNPJ</option>
                                    <option value="aleatoria">Chave Aleatória</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="chavePix" class="form-label fw-bold">Chave Pix</label>
                                <input type="text" class="form-control" id="chavePix" placeholder="Digite a chave Pix" required>
                            </div>

                            <div class="mb-3">
                                <label for="valorTransferir" class="form-label fw-bold">Valor para transferir</label>
                                <input type="text" class="form-control" id="valorTransferir" placeholder="R$ 0,00" required>
                            </div>

                            <div class="mb-3">
                                <label for="informacoesAdicionais" class="form-label fw-bold">Informações adicionais</label>
                                <textarea class="form-control" id="informacoesAdicionais" rows="3" placeholder="Informações adicionais sobre a transferência..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="assinaturaEletronica" class="form-label fw-bold">
                                    Assinatura eletrônica
                                    <i class="fas fa-eye ms-2" style="cursor: pointer;"></i>
                                </label>
                                <input type="password" class="form-control" id="assinaturaEletronica" placeholder="Digite sua assinatura eletrônica" required>
                            </div>
                        </form>
                    </div>

                    <!-- Tab Agência e Conta -->
                    <div class="tab-pane fade" id="agencia-content" role="tabpanel">
                        <form id="formPixAgencia">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="mesmaTitularidade">
                                    <label class="form-check-label fw-bold" for="mesmaTitularidade">
                                        Mesma titularidade
                                    </label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="titular" class="form-label fw-bold">Titular</label>
                                    <input type="text" class="form-control" id="titular" placeholder="Nome do titular" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="documento" class="form-label fw-bold">Documento</label>
                                    <input type="text" class="form-control" id="documento" placeholder="CPF/CNPJ" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="banco" class="form-label fw-bold">Banco</label>
                                    <select class="form-select" id="banco" required>
                                        <option value="">Selecione o banco</option>
                                        <option value="001">Banco do Brasil</option>
                                        <option value="104">Caixa Econômica Federal</option>
                                        <option value="033">Santander</option>
                                        <option value="341">Itaú</option>
                                        <option value="237">Bradesco</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tipoConta" class="form-label fw-bold">Tipo da conta</label>
                                    <select class="form-select" id="tipoConta" required>
                                        <option value="corrente">Corrente</option>
                                        <option value="poupanca">Poupança</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="agencia" class="form-label fw-bold">Agência SEM dígito</label>
                                    <input type="text" class="form-control" id="agencia" placeholder="0000" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="conta" class="form-label fw-bold">Conta COM dígito</label>
                                    <input type="text" class="form-control" id="conta" placeholder="00000-0" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="valorTransferirAgencia" class="form-label fw-bold">Valor para transferir</label>
                                <input type="text" class="form-control" id="valorTransferirAgencia" placeholder="R$ 0,00" required>
                            </div>

                            <div class="mb-3">
                                <label for="assinaturaEletronicaAgencia" class="form-label fw-bold">
                                    Assinatura eletrônica
                                    <i class="fas fa-eye ms-2" style="cursor: pointer;"></i>
                                </label>
                                <input type="password" class="form-control" id="assinaturaEletronicaAgencia" placeholder="Digite sua assinatura eletrônica" required>
                            </div>
                        </form>
                    </div>

                    <!-- Tab Copia e Cola -->
                    <div class="tab-pane fade" id="copia-content" role="tabpanel">
                        <form id="formPixCopia">
                            <div class="mb-3">
                                <label for="qrCodeKey" class="form-label fw-bold">Digite a chave do QR Code</label>
                                <input type="text" class="form-control" id="qrCodeKey" placeholder="Cole aqui a chave do QR Code" required>
                            </div>

                            <div class="mb-3">
                                <label for="informacoesAdicionaisCopia" class="form-label fw-bold">Informações adicionais</label>
                                <textarea class="form-control" id="informacoesAdicionaisCopia" rows="3" placeholder="Informações adicionais sobre a transferência..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="assinaturaEletronicaCopia" class="form-label fw-bold">
                                    Assinatura eletrônica
                                    <i class="fas fa-eye ms-2" style="cursor: pointer;"></i>
                                </label>
                                <input type="password" class="form-control" id="assinaturaEletronicaCopia" placeholder="Digite sua assinatura eletrônica" required>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    Salvar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection


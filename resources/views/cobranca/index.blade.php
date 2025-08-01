@extends('templates.dashboard-template')

@section('title', 'Cobrança Única')



@section('content')
<!-- Breadcrumb -->
<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent p-0 mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-primary text-decoration-none">Juntter</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="#" class="text-primary text-decoration-none">Cobrança</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Cobrança Única</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Alert -->
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning bg-warning text-white border-0 rounded-3 shadow-sm">
            <i class="fas fa-info-circle me-2"></i>
            Gere links de pagamento para seus clientes.
        </div>
    </div>
</div>

<!-- Header com botão -->
<div class="row align-items-center mb-4">
    <div class="col-12 text-center">
        <h1 class="h3 mb-2 fw-bold">Cobrança Única</h1>
        <p class="text-muted mb-3">Gerencie suas cobranças avulsas</p>
        <button class="btn btn-novo-pagamento shadow-sm" 
                data-bs-toggle="modal" 
                data-bs-target="#modalCobranca">
            <i class="fas fa-plus me-2"></i>
            Novo Pagamento Único
        </button>
    </div>
</div>

<!-- Tabela de cobranças -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-lg rounded-4">
            <div class="card-body p-4">
                <!-- Tabela Juntter Style -->
                <div class="table-responsive">
                    <table id="cobrancasTable" class="table table-hover table-striped">
                        <thead>
                            <tr class="table-header-juntter">
                                <th>Cliente</th>
                                <th>Documento</th>
                                <th>Valor</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>João Silva</strong></td>
                                <td><span class="text-muted">123.456.789-00</span></td>
                                <td><strong class="text-success">R$ 150,00</strong></td>
                                <td>15/12/2024</td>
                                <td><span class="badge badge-success">Pago</span></td>
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
                                <td><strong>Maria Santos</strong></td>
                                <td><span class="text-muted">987.654.321-00</span></td>
                                <td><strong class="text-warning">R$ 300,00</strong></td>
                                <td>12/12/2024</td>
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
                                <td><strong>Pedro Oliveira</strong></td>
                                <td><span class="text-muted">456.789.123-00</span></td>
                                <td><strong class="text-danger">R$ 220,00</strong></td>
                                <td>10/12/2024</td>
                                <td><span class="badge badge-danger">Vencido</span></td>
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

<!-- Modal Cobrança -->
<div class="modal fade" id="modalCobranca" tabindex="-1" aria-labelledby="modalCobrancaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalCobrancaLabel">Cobrança única</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCobranca">
                    <!-- Buscar cliente -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Buscar cliente</label>
                        <select class="form-select" id="selectCliente">
                            <option value="">Selecione o Cliente</option>
                            <option value="1">João da Silva - 123.456.789-00</option>
                            <option value="2">Maria Santos - 987.654.321-00</option>
                        </select>
                    </div>

                    <div class="row">
                        <!-- Valor da venda -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Valor da venda</label>
                            <input type="text" class="form-control" id="valorVenda" placeholder="0,00">
                        </div>
                        <!-- Vencimento -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Vencimento (cobrar em)</label>
                            <input type="date" class="form-control" id="vencimento">
                        </div>
                    </div>

                    <!-- Abas de pagamento -->
                    <div class="payment-tabs mb-4">
                        <ul class="nav nav-tabs border-0" id="paymentTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active payment-tab" id="link-tab" data-bs-toggle="tab" data-bs-target="#link-content" type="button" role="tab">
                                    Link de Pagamento
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link payment-tab" id="cartao-tab" data-bs-toggle="tab" data-bs-target="#cartao-content" type="button" role="tab">
                                    Cartão de Crédito
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link payment-tab" id="boleto-tab" data-bs-toggle="tab" data-bs-target="#boleto-content" type="button" role="tab">
                                    Boleto / Pix
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mt-4" id="paymentTabsContent">
                            <!-- Aba Link de Pagamento -->
                            <div class="tab-pane fade show active" id="link-content" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Valor de cada transação</label>
                                        <input type="text" class="form-control" placeholder="0,00">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Vencimento da 1ª transação(cobrar em)</label>
                                        <input type="date" class="form-control">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Núm. parcelas</label>
                                        <select class="form-select">
                                            <option>Indeterminado</option>
                                            <option value="1">1x</option>
                                            <option value="2">2x</option>
                                            <option value="3">3x</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Periodicidade</label>
                                        <select class="form-select">
                                            <option>Mensal</option>
                                            <option>Semanal</option>
                                            <option>Quinzenal</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Informações adicionais</label>
                                    <textarea class="form-control" rows="3" placeholder="Digite informações adicionais..."></textarea>
                                </div>
                            </div>

                            <!-- Aba Cartão de Crédito -->
                            <div class="tab-pane fade" id="cartao-content" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Nome do titular</label>
                                        <input type="text" class="form-control" placeholder="Nome completo">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Número do cartão</label>
                                        <input type="text" class="form-control" placeholder="0000 0000 0000 0000">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Validade</label>
                                        <input type="text" class="form-control" placeholder="MM/AAAA">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Código de segurança</label>
                                        <input type="text" class="form-control" placeholder="000">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Núm. parcelas</label>
                                        <select class="form-select">
                                            <option>Indeterminado</option>
                                            <option value="1">1x</option>
                                            <option value="2">2x</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Boleto/Pix -->
                            <div class="tab-pane fade" id="boleto-content" role="tabpanel">
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase small text-muted mb-3">MULTAS E JUROS</h6>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">Multas por atraso(%)</label>
                                                <input type="text" class="form-control" placeholder="0,00">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">Juros ao mês(%)</label>
                                                <input type="text" class="form-control" placeholder="0,00">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label fw-bold">Prazo máximo para emissão 2ª via</label>
                                                <select class="form-select">
                                                    <option>Selecione...</option>
                                                    <option value="30">30 dias</option>
                                                    <option value="60">60 dias</option>
                                                    <option value="90">90 dias</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="fw-bold text-uppercase small text-muted mb-3">INFORMAÇÕES ADICIONAIS</h6>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Observações do boleto</label>
                                            <textarea class="form-control" rows="3" placeholder="Digite as observações..."></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Descrição do produto/serviço vendido</label>
                                            <textarea class="form-control" rows="3" placeholder="Descreva o produto ou serviço..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
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



@section('scripts')
<script>
$(document).ready(function() {
    // Inicializar DataTable AdminLTE com design Juntter
    if ($.fn.DataTable) {
        $('#cobrancasTable').DataTable({
            language: {
                "sEmptyTable": "Nenhum registro encontrado",
                "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
                "sInfoFiltered": "(Filtrados de _MAX_ registros)",
                "sLengthMenu": "_MENU_ resultados por página",
                "sLoadingRecords": "Carregando...",
                "sProcessing": "Processando...",
                "sZeroRecords": "Nenhum registro encontrado",
                "sSearch": "Pesquisar:",
                "oPaginate": {
                    "sNext": "Próximo",
                    "sPrevious": "Anterior",
                    "sFirst": "Primeiro",
                    "sLast": "Último"
                }
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copy',
                    className: 'btn btn-secondary btn-sm',
                    text: '<i class="fas fa-copy"></i> Copiar'
                },
                {
                    extend: 'excel',
                    className: 'btn btn-success btn-sm',
                    text: '<i class="fas fa-file-excel"></i> Excel'
                },
                {
                    extend: 'csv',
                    className: 'btn btn-info btn-sm',
                    text: '<i class="fas fa-file-csv"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-danger btn-sm',
                    text: '<i class="fas fa-file-pdf"></i> PDF'
                },
                {
                    extend: 'print',
                    className: 'btn btn-warning btn-sm',
                    text: '<i class="fas fa-print"></i> Imprimir'
                }
            ],
            columnDefs: [
                {
                    targets: -1,
                    orderable: false
                }
            ]
        });
        console.log('DataTable AdminLTE inicializado!');
    } else {
        console.error('DataTable AdminLTE não disponível!');
    }
    
    // Estilizar select2 se disponível
    if ($.fn.select2) {
        $('#selectCliente').select2({
            placeholder: 'Selecione o Cliente',
            allowClear: true,
            dropdownParent: $('#modalCobranca')
        });
    }
    
    // Máscara para valores monetários
    $('[placeholder="0,00"]').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        value = (value/100).toFixed(2) + '';
        value = value.replace(".", ",");
        value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
        value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
        this.value = 'R$ ' + value;
    });
    
    // Resetar modal ao fechar
    $('#modalCobranca').on('hidden.bs.modal', function() {
        $('#formCobranca')[0].reset();
        if ($.fn.select2) {
            $('#selectCliente').val(null).trigger('change');
        }
    });
    
    // Modal - garantir que funcione
    $('[data-bs-toggle="modal"]').on('click', function(e) {
        e.preventDefault();
        var targetModal = $(this).attr('data-bs-target');
        $(targetModal).modal('show');
        console.log('Modal aberto via jQuery!');
    });

    // Funcionalidade dos tabs do modal
    $('.payment-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remover active de todos
        $('.payment-tab').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
        // Adicionar active no clicado
        $(this).addClass('active');
        
        // Mostrar conteúdo correspondente
        var target = $(this).attr('data-bs-target');
        $(target).addClass('show active');
        
        console.log('Tab ativado:', target);
    });

    // Eventos do modal
    $('#modalCobranca').on('show.bs.modal', function () {
        console.log('Modal abrindo...');
        // Reset para primeira aba quando abrir
        $('.payment-tab').removeClass('active');
        $('.tab-pane').removeClass('show active');
        $('#link-tab').addClass('active');
        $('#link-content').addClass('show active');
    });
});
</script>
@endsection
/**
 * Dashboard JavaScript Functions
 * Usando jQuery para melhor compatibilidade
 */

$(document).ready(function() {
    
    // Inicializar animações dos cards
    initDashboardAnimations();
    
    // Inicializar contadores
    initCounters();
    
    // Inicializar DataTables e modais
    initDataTables();
    initModals();
    
});

/**
 * Função para trocar abas do analytics
 */
function switchTab(tabName) {
    // Remove active class de todas as abas
    $('.tab-btn').removeClass('active');
    
    // Add active class na aba clicada
    $(event.target).addClass('active');
}

/**
 * Animações dos elementos do dashboard
 */
function initDashboardAnimations() {
    // Animar elementos com fade-in-up
    $('.fade-in-up').each(function(index) {
        const $element = $(this);
        const delay = $element.data('delay') || (index * 0.1);
        
        // Set initial state
        $element.css({
            'opacity': '0',
            'transform': 'translateY(30px)',
            'transition': 'all 0.6s ease'
        });
        
        // Trigger animation with delay
        setTimeout(function() {
            $element.css({
                'opacity': '1',
                'transform': 'translateY(0)'
            });
        }, delay * 1000);
    });
}

/**
 * Animação de contadores (count up)
 */
function initCounters() {
    $('.saldo-valor, .metric-value').each(function() {
        const $element = $(this);
        const text = $element.text();
        
        // Animação básica de fade in
        if (text.includes('R$') || !isNaN(parseInt(text))) {
            $element.css('opacity', '0').animate({'opacity': '1'}, 1000);
        }
    });
}

/**
 * Configuração padrão do DataTable para o projeto Juntter
 */
function getJuntterDataTableConfig(columnDefs = [], options = {}) {
    return {
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
        responsive: {
            details: { type: 'column', target: 0 }
        },
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
        columnDefs: columnDefs,
        ...options
    };
}

/**
 * Função segura para inicializar DataTables
 */
function initDataTableSafely(tableSelector, columnDefs = [], options = {}) {
    try {
        var table = $(tableSelector);
        if (table.length) {
            table.DataTable(getJuntterDataTableConfig(columnDefs, options));
            return true;
        }
    } catch (error) {
        console.error('Erro ao inicializar DataTable ' + tableSelector + ':', error);
    }
    return false;
}

/**
 * Inicializar DataTables com design Juntter
 */
function initDataTables() {
    if ($.fn.DataTable) {
        // DataTable para Cobrança Única
        initDataTableSafely('#cobrancasTable', [
            { className: 'dtr-control', orderable: false, targets: 0 },
            { targets: -1, orderable: false }
        ], {
            responsive: {
                details: { type: 'column', target: 0 }
            },
            order: [[4, 'desc']], // Ordenar pela coluna 5 (Data) de forma decrescente
            columnDefs: [
                { className: 'dtr-control', orderable: false, targets: 0 }
            ]
        });
        
        // DataTable para Cobrança Recorrente
        initDataTableSafely('#cobrancasRecorrentesTable', [
            { className: 'dtr-control', orderable: false, targets: 0 },
            { targets: -1, orderable: false }
        ], {
            responsive: {
                details: { type: 'column', target: 0 }
            }
        });
        
        // DataTable para Regras de Split Pré
        initDataTableSafely('#regrasSplitTable', [
            { className: 'dtr-control', orderable: false, targets: 0 },
            { targets: -1, orderable: false }
        ], {
            responsive: {
                details: { type: 'column', target: 0 }
            }
        });
        
        // DataTable para Planos de Cobrança
        initDataTableSafely('#planosTable', [
            { className: 'dtr-control', orderable: false, targets: 0 },
            { targets: -1, orderable: false }
        ], {
            responsive: {
                details: { type: 'column', target: 0 }
            }
        });
        
        // DataTable para Enviar Pix
        initDataTableSafely('#pixTable', [
            { className: 'dtr-control', orderable: false, targets: 0 },
            { targets: -1, orderable: false }
        ], {
            responsive: {
                details: { type: 'column', target: 0 }
            }
        });
        
        // DataTable para Estabelecimentos
        initDataTableSafely('#estabelecimentos-table', [
            { className: 'dtr-control', orderable: false, targets: 0 },
            { targets: -1, orderable: false }
        ], {
            responsive: {
                details: { type: 'column', target: 0 }
            }
        });

        // DataTable para Transações do Vendedor
        initDataTableSafely('#tabela-transacoes', [
            { className: 'dtr-control', orderable: false, targets: 0 },
            { targets: -1, orderable: false }
        ], {
            responsive: {
                details: { type: 'column', target: 0 }
            },
            order: [[4, 'desc']] // coluna Data
        });
        
        // DataTable para Saldo e Extrato
        initDataTableSafely('#saldoExtratoTable', [
            { className: 'dtr-control', orderable: false, targets: 0 },
            { targets: -1, orderable: false }
        ], {
            responsive: {
                details: { type: 'column', target: 0 }
            },
            order: [[1, 'desc']] // Ordenar pela coluna 1 (Data) de forma decrescente
        });
    } else {
        console.error('DataTable AdminLTE não disponível!');
    }
}



/**
 * Inicializar funcionalidades dos modais
 */
function initModals() {
    // Estilizar select2 se disponível
    if ($.fn.select2) {
        // Select2 para Cobrança Única
        if ($('#selectCliente').length) {
            $('#selectCliente').select2({
                placeholder: 'Selecione o Cliente',
                allowClear: true,
                dropdownParent: $('#modalCobranca')
            });
        }
        
        // Select2 para Cobrança Recorrente
        if ($('#clienteRecorrente').length) {
            $('#clienteRecorrente').select2({
                placeholder: 'Selecione o Paciente',
                allowClear: true,
                dropdownParent: $('#modalCobrancaRecorrente')
            });
        }
    }
    
    // Máscara para valores monetários
    $('[placeholder="0,00"], #valor, #valorParcela, #valorPlano, #precoBoleto, #precoCredito, #valorTransferir, #valorTransferirAgencia, #valorMinimo, #valorPagar, #valorMaximo, #taxaConta').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        value = (value/100).toFixed(2) + '';
        value = value.replace(".", ",");
        value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
        value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
        this.value = 'R$ ' + value;
    });
    
    // Máscara para datas (dd/mm/aaaa) - apenas para campos de texto
    $('#dataVencimento').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2);
        }
        if (value.length >= 5) {
            value = value.substring(0, 5) + '/' + value.substring(5, 9);
        }
        this.value = value;
    });
    
    // Funcionalidade para mostrar/ocultar senha
    $('.fa-eye').on('click', function() {
        var input = $(this).siblings('input');
        var type = input.attr('type');
        
        if (type === 'password') {
            input.attr('type', 'text');
            $(this).removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            $(this).removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Resetar modais ao fechar
    $('#modalCobranca').on('hidden.bs.modal', function() {
        $('#formCobranca')[0].reset();
        if ($.fn.select2) {
            $('#selectCliente').val(null).trigger('change');
        }
    });
    
    $('#modalCobrancaRecorrente').on('hidden.bs.modal', function() {
        $('#formCobrancaRecorrente')[0].reset();
        if ($.fn.select2) {
            $('#clienteRecorrente').val(null).trigger('change');
        }
    });
    
    $('#modalPlano').on('hidden.bs.modal', function() {
        $('#formPlano')[0].reset();
    });
    
    $('#modalPix').on('hidden.bs.modal', function() {
        $('#formPixChave')[0].reset();
        $('#formPixAgencia')[0].reset();
        $('#formPixCopia')[0].reset();
    });
    
    // Modal - garantir que funcione
    $('[data-bs-toggle="modal"]').on('click', function(e) {
        e.preventDefault();
        var targetModal = $(this).attr('data-bs-target');
        $(targetModal).modal('show');
    });
    
    // Garantir que o botão X funcione
    $('.btn-close').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var modal = $(this).closest('.modal');
        modal.modal('hide');
    });
    
    // Garantir que o botão Fechar funcione
    $('.btn-secondary[data-bs-dismiss="modal"]').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var modal = $(this).closest('.modal');
        modal.modal('hide');
    });
    
    // Funcionalidade dos tabs do modal (Cobrança Única)
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
    });
    
    // Funcionalidade dos tabs do modal (Pix)
    $('.pix-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remover active de todos
        $('.pix-tab').removeClass('active');
        $('.tab-pane').removeClass('show active');
        
        // Adicionar active no clicado
        $(this).addClass('active');
        
        // Mostrar conteúdo correspondente
        var target = $(this).attr('data-bs-target');
        $(target).addClass('show active');
    });
    
    // Eventos do modal (Cobrança Única)
    $('#modalCobranca').on('show.bs.modal', function () {
        // Reset para primeira aba quando abrir
        $('.payment-tab').removeClass('active');
        $('.tab-pane').removeClass('show active');
        $('#link-tab').addClass('active');
        $('#link-content').addClass('show active');
    });
    
    // Eventos do modal (Pix)
    $('#modalPix').on('show.bs.modal', function () {
        // Reset para primeira aba quando abrir
        $('.pix-tab').removeClass('active');
        $('.tab-pane').removeClass('show active');
        $('#chave-tab').addClass('active');
        $('#chave-content').addClass('show active');
    });
    
    // Funcionalidades específicas para Pagar Contas
    $('#linhaDigitavel').on('input', function() {
        // Validar linha digitável (47 ou 48 dígitos)
        let value = this.value.replace(/\D/g, '');
        if (value.length > 48) {
            this.value = value.substring(0, 48);
        }
    });
    
    // Botão Enviar (Pagar Contas)
    $('#btnEnviarLinha').on('click', function(e) {
        e.preventDefault();
        var linhaDigitavel = $('#linhaDigitavel').val();
        
        if (!linhaDigitavel) {
            alert('Por favor, informe a linha digitável.');
            return;
        }
        
        // Simular processamento
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processando...');
        
        setTimeout(function() {
            $('#btnEnviarLinha').prop('disabled', false).html('<i class="fas fa-check me-2"></i>Enviar');
            alert('Linha digitável processada com sucesso!');
        }, 2000);
    });
    
    // Botão Solicitar pagamento (Pagar Contas)
    $('#btnSolicitarPagamento').on('click', function(e) {
        e.preventDefault();
        
        // Validar campos obrigatórios
        var assinatura = $('#assinaturaEletronica').val();
        var codigoWhatsApp = $('#codigoWhatsApp').val();
        
        if (!assinatura) {
            alert('Por favor, informe a assinatura eletrônica.');
            return;
        }
        
        if (!codigoWhatsApp) {
            alert('Por favor, informe o código enviado por WhatsApp.');
            return;
        }
        
        // Simular processamento
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processando...');
        
        setTimeout(function() {
            $('#btnSolicitarPagamento').prop('disabled', false).html('<i class="fas fa-file-alt me-2"></i>Solicitar pagamento');
            alert('Pagamento solicitado com sucesso!');
        }, 2000);
    });
    
    // Botão Buscar (Saldo e Extrato)
    $('#btnBuscarExtrato').on('click', function(e) {
        e.preventDefault();
        
        var dataInicio = $('#dataInicio').val();
        var dataTermino = $('#dataTermino').val();
        
        if (!dataInicio || !dataTermino) {
            alert('Por favor, informe o período de início e término.');
            return;
        }
        
        // Simular busca
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Buscando...');
        
        setTimeout(function() {
            $('#btnBuscarExtrato').prop('disabled', false).html('<i class="fas fa-search me-2"></i>Buscar');
            alert('Extrato filtrado com sucesso!');
        }, 1500);
    });
    
    // Select de bandeiras para planos comerciais
    $('#bandeiraSelect').on('change', function() {
        // Esconder todas as bandeiras
        $('.bandeira-detalhes').addClass('d-none');
        
        // Mostrar a bandeira selecionada
        var selectedIndex = $(this).val();
        $('#bandeira-' + selectedIndex).removeClass('d-none');
    });
}

// Expor função globalmente para uso inline
window.switchTab = switchTab;
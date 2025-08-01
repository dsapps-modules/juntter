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
 * Inicializar DataTables com design Juntter
 */
function initDataTables() {
    if ($.fn.DataTable) {
        // DataTable para Cobrança Única
        if ($('#cobrancasTable').length) {
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
        }
        
        // DataTable para Cobrança Recorrente
        if ($('#cobrancasRecorrentesTable').length) {
            $('#cobrancasRecorrentesTable').DataTable({
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
        }
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
    $('[placeholder="0,00"], #valorParcela').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        value = (value/100).toFixed(2) + '';
        value = value.replace(".", ",");
        value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
        value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
        this.value = 'R$ ' + value;
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
    
    // Eventos do modal (Cobrança Única)
    $('#modalCobranca').on('show.bs.modal', function () {
        // Reset para primeira aba quando abrir
        $('.payment-tab').removeClass('active');
        $('.tab-pane').removeClass('show active');
        $('#link-tab').addClass('active');
        $('#link-content').addClass('show active');
    });
}

// Expor função globalmente para uso inline
window.switchTab = switchTab;
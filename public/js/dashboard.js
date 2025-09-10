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

        // DataTable para Extrato do Estabelecimento
        initDataTableSafely('#extratoEstabelecimentoTable', [
            { className: 'dtr-control', orderable: false, targets: 0 },
            { targets: -1, orderable: false }
        ], {
            responsive: {
                details: { type: 'column', target: 0 }
            },
            order: [[3, 'desc']] // Ordenar pela coluna 3 (Data) de forma decrescente
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

/**
 * Função para simular transação via FAB usando rota real (jQuery)
 */
function simularTransacaoFAB() {
    const $form = $('#fabSimulacaoForm');
    
    // Obter valores usando jQuery
    const valor = $form.find('#fabValor').val();
    const parcelas = $form.find('#fabParcelas').val();
    const bandeira = $form.find('#fabBandeira').val();
    const interest = $form.find('#fabInterest').val();
    
    // Validações básicas
    if (!valor || valor === '0,00' || valor === 'R$ 0,00') {
        showFabToast('Por favor, insira um valor válido.', 'error');
        return;
    }
    
    if (!parcelas || parseInt(parcelas) < 1 || parseInt(parcelas) > 18) {
        showFabToast('Por favor, selecione um número válido de parcelas (1x a 18x).', 'error');
        return;
    }
    
    if (!interest || interest === '') {
        showFabToast('Por favor, selecione quem paga as taxas.', 'error');
        return;
    }
    
    // Converter valor para formato brasileiro (como na view de simulação)
    let valorFormatado = valor;
    
    // Se não tem R$ no início, adicionar
    if (!valorFormatado.startsWith('R$ ')) {
        valorFormatado = 'R$ ' + valorFormatado;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Simulando...',
        text: 'Processando simulação com a API',
        icon: 'info',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Fazer requisição usando jQuery AJAX
    $.ajax({
        url: '/cobranca/simular',
        type: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        data: {
            amount: valorFormatado,
            flag_id: bandeira,
            interest: interest,
            _token: $('meta[name="_token"]').attr('content')
        },
        dataType: 'json',
        success: function(data) {
            Swal.close();
            
            if (data.success) {
                // Sucesso - exibir resultado
                exibirResultadoSimulacao(data, valor, parcelas, interest, bandeira);
            } else {
                // Erro da API
                showFabToast(data.message || 'Erro na simulação.', 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            
            let mensagemErro = 'Erro de conexão. Tente novamente.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                mensagemErro = xhr.responseJSON.message;
            }
            
            showFabToast(mensagemErro, 'error');
        }
    });
    
    // Fechar modal do FAB
    $('#fabSimulacaoModal').modal('hide');
    
    // Limpar formulário
    $form[0].reset();
}

/**
 * Exibir resultado da simulação real
 */
function exibirResultadoSimulacao(simulacao, valor, parcelas, interest, bandeira) {
    // Converter valor formatado para número
    const valorNumerico = parseFloat(valor.replace(/[R$\s.]/g, '').replace(',', '.'));
    const valorParcela = valorNumerico / parseInt(parcelas);
    
    // Obter valores da API
    const valorDebito = simulacao.simulation.simulation.debit / 100;
    const valorPix = simulacao.simulation.simulation.pix / 100;
    
    let resultado = `
        <div class="simulacao-resultado">
            <h6 class="text-primary mb-3">
                <i class="fas fa-calculator mr-2"></i>
                Resultado da Simulação
            </h6>
            
            <div class="row">
                <div class="col-6">
                    <div class="info-item">
                        <small class="text-muted">Valor Original</small>
                        <div class="font-weight-bold">R$ ${valorNumerico.toFixed(2).replace('.', ',')}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="info-item">
                        <small class="text-muted">Parcelas</small>
                        <div class="font-weight-bold">${parcelas}x</div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-2">
                <div class="col-6">
                    <div class="info-item">
                        <small class="text-muted">Valor por Parcela</small>
                        <div class="font-weight-bold">R$ ${valorParcela.toFixed(2).replace('.', ',')}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="info-item">
                        <small class="text-muted">Bandeira</small>
                        <div class="font-weight-bold">${getBandeiraNome(bandeira)}</div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-2">
                <div class="col-6">
                    <div class="info-item">
                        <small class="text-muted">Quem paga as taxas</small>
                        <div class="font-weight-bold">${interest === 'CLIENT' ? 'Cliente' : 'Estabelecimento'}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="info-item">
                        <small class="text-muted">Valor por Parcela</small>
                        <div class="font-weight-bold">R$ ${valorParcela.toFixed(2).replace('.', ',')}</div>
                    </div>
                </div>
            </div>
            
            <hr class="my-3">
            
            <div class="row">
                <div class="col-4">
                    <div class="info-item text-center">
                        <small class="text-muted">Débito</small>
                        <div class="font-weight-bold text-info">R$ ${valorDebito.toFixed(2).replace('.', ',')}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="info-item text-center">
                        <small class="text-muted">PIX</small>
                        <div class="font-weight-bold text-success">R$ ${valorPix.toFixed(2).replace('.', ',')}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="info-item text-center">
                        <small class="text-muted">Crédito</small>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleParcelas()">
                            <i class="fas fa-credit-card mr-1"></i>
                            Ver Parcelas
                        </button>
                    </div>
                </div>
            </div>
    `;
    
    // Adicionar tabela de parcelamento se existir
    if (simulacao.simulation.simulation.credit) {
        resultado += `
            <div class="mt-4" id="tabelaParcelas" style="display: none;">
                <h6 class="text-primary mb-3">
                    <i class="fas fa-credit-card mr-2"></i>
                    Opções de Parcelamento
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Parcelas</th>
                                <th class="text-center">Valor da Parcela</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        Object.entries(simulacao.simulation.simulation.credit).forEach(([parcelas, dados]) => {
            resultado += `
                <tr>
                    <td><span class="badge bg-primary">${parcelas}</span></td>
                    <td class="text-center">R$ ${(dados.installment / 100).toFixed(2).replace('.', ',')}</td>
                    <td class="text-end"><strong>R$ ${(dados.total / 100).toFixed(2).replace('.', ',')}</strong></td>
                </tr>
            `;
        });
        
        resultado += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    resultado += `</div>`;
    
    // Mostrar resultado
    Swal.fire({
        title: 'Simulação Concluída',
        html: resultado,
        icon: 'success',
        confirmButtonText: 'Fechar',
        confirmButtonColor: '#FFCF00',
        customClass: {
            popup: 'swal2-popup-custom'
        },
        width: '600px'
    });
}

/**
 * Função para mostrar toast do FAB (jQuery)
 */
function showFabToast(message, type = 'success') {
    const iconClass = type === 'success' ? 'check-circle' : 'exclamation-triangle';
    
    const $toast = $(`
        <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="fas fa-${iconClass} mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `);
    
    $('body').append($toast);
    
    // Auto-remover após 3 segundos
    setTimeout(() => {
        $toast.alert('close');
    }, 3000);
}

/**
 * Função para obter nome da bandeira pelo ID
 */
function getBandeiraNome(bandeiraId) {
    const bandeiras = {
        '1': 'Mastercard',
        '2': 'Visa',
        '3': 'Elo',
        '4': 'American Express',
        '5': 'Hiper/Hipercard',
        '6': 'Outras',
        '8': 'Bacen'
    };
    return bandeiras[bandeiraId] || 'Desconhecida';
}

/**
 * Inicializar validações do FAB
 */
function initFabValidations() {
    // Máscara monetária para o campo valor (igual à view de simulação)
    $('#fabValor').on('input', function() {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 0) {
            value = (value/100).toFixed(2) + '';
            value = value.replace(".", ",");
            value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
            value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
            this.value = value;
        }
        
        // Validação em tempo real
        const valor = $(this).val();
        const $group = $(this).closest('.form-group');
        
        if (valor && valor !== 'R$ 0,00' && valor !== '0,00') {
            $group.removeClass('has-error').addClass('has-success');
            $group.find('.form-control').removeClass('is-invalid').addClass('is-valid');
        } else {
            $group.removeClass('has-success').addClass('has-error');
            $group.find('.form-control').removeClass('is-valid').addClass('is-invalid');
        }
    });
    
    // Validação em tempo real das parcelas
    $('#fabParcelas').on('change', function() {
        const parcelas = $(this).val();
        const $group = $(this).closest('.form-group');
        
        if (parcelas && parseInt(parcelas) >= 1 && parseInt(parcelas) <= 18) {
            $group.removeClass('has-error').addClass('has-success');
            $group.find('.form-control').removeClass('is-invalid').addClass('is-valid');
        } else {
            $group.removeClass('has-success').addClass('has-error');
            $group.find('.form-control').removeClass('is-valid').addClass('is-invalid');
        }
    });
    
    // Validação em tempo real de quem paga as taxas
    $('#fabInterest').on('change', function() {
        const interest = $(this).val();
        const $group = $(this).closest('.form-group');
        
        if (interest && interest !== '') {
            $group.removeClass('has-error').addClass('has-success');
            $group.find('.form-control').removeClass('is-invalid').addClass('is-valid');
        } else {
            $group.removeClass('has-success').addClass('has-error');
            $group.find('.form-control').removeClass('is-valid').addClass('is-invalid');
        }
    });
    
    // Limpar validações quando modal é fechado
    $('#fabSimulacaoModal').on('hidden.bs.modal', function() {
        $('.form-group').removeClass('has-error has-success');
        $('.form-control').removeClass('is-invalid is-valid');
    });
}

// Inicializar validações quando documento estiver pronto
$(document).ready(function() {
    initFabValidations();
});

/**
 * Função para alternar visibilidade da tabela de parcelas (jQuery)
 */
function toggleParcelas() {
    const $tabela = $('#tabelaParcelas');
    const $botao = $(event.target);
    
    if ($tabela.is(':hidden')) {
        $tabela.slideDown(300);
        $botao.html('<i class="fas fa-eye-slash mr-1"></i>Ocultar Parcelas')
               .removeClass('btn-outline-primary')
               .addClass('btn-primary');
    } else {
        $tabela.slideUp(300);
        $botao.html('<i class="fas fa-credit-card mr-1"></i>Ver Parcelas')
               .removeClass('btn-primary')
               .addClass('btn-outline-primary');
    }
}

// Expor funções globalmente
window.simularTransacaoFAB = simularTransacaoFAB;
window.showFabToast = showFabToast;
window.getBandeiraNome = getBandeiraNome;
window.toggleParcelas = toggleParcelas;
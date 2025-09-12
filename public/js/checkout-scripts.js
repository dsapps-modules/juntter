// Checkout Scripts - Juntter
// Funções para o sistema de checkout

// Funções auxiliares
function updateCheckoutSteps(activeStep) {
    $('.step').removeClass('active completed').addClass('pending');
    
    for (let i = 0; i < activeStep; i++) {
        $('.step').eq(i).removeClass('pending').addClass('completed');
    }
    
    if (activeStep < 3) {
        $('.step').eq(activeStep).removeClass('pending').addClass('active');
    }
}

function showError(message) {
    // Criar toast de erro
    const toast = document.createElement('div');
    toast.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}

function showSuccess(message) {
    // Criar toast de sucesso
    const toast = document.createElement('div');
    toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}

// Processar pagamento com cartão
function processarCartao(form) {
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.html();
    
    // Mostrar loading
    submitBtn.html('<span class="loading-spinner"></span> Processando...');
    submitBtn.prop('disabled', true);
    
    const url = form.data('url') || window.location.href;
    const data = form.serialize();
    
    $.post(url, data)
        .done(function(response) {
            if (response.success) {
                // Atualizar steps
                updateCheckoutSteps(2);
                $('#successModal').modal('show');
            } else {
                showError(response.error || 'Erro ao processar pagamento');
            }
        })
        .fail(function(xhr) {
            let error = 'Erro ao processar pagamento. Tente novamente.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                error = xhr.responseJSON.error;
            }
            showError(error);
        })
        .always(function() {
            // Restaurar botão
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        });
}

// Processar Boleto
function processarBoleto() {
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    
    // Mostrar loading
    button.innerHTML = '<span class="loading-spinner"></span> Gerando boleto...';
    button.disabled = true;
    
    // Dados mínimos para Boleto (dados vêm do link)
    const dados = {
        _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    // Fazer requisição para criar boleto
    $.post(button.dataset.url || window.location.href, dados)
        .done(function(response) {
            if (response.success && response.boleto_data) {
                // Mostrar dados do boleto
                const boletoData = response.boleto_data;
                
                // Atualizar código de barras se disponível
                if (boletoData.boleto_barcode) {
                    const barcodeContainer = document.getElementById('boletoBarcode');
                    barcodeContainer.innerHTML = `
                        <div class="mb-2">
                            <small class="text-muted">Código de Barras:</small>
                        </div>
                        <div class="text-center">
                            <div class="boleto-barcode-text">${boletoData.boleto_barcode}</div>
                            <p class="mt-2 mb-0 text-muted">Copie o código acima</p>
                        </div>
                    `;
                }
                
                // Atualizar botão
                button.innerHTML = '<i class="fas fa-check me-2"></i>Boleto Gerado';
                button.classList.remove('btn-payment');
                button.classList.add('btn-success');
                button.disabled = true;
                
                // Se tiver URL do boleto, mostrar botão para abrir
                if (boletoData.boleto_url) {
                    const urlButton = document.createElement('a');
                    urlButton.href = boletoData.boleto_url;
                    urlButton.target = '_blank';
                    urlButton.className = 'btn btn-outline-primary btn-sm mt-2';
                    urlButton.innerHTML = '<i class="fas fa-external-link-alt me-2"></i>Abrir Boleto';
                    
                    const boletoContainer = document.querySelector('.boleto-info');
                    boletoContainer.appendChild(urlButton);
                }
                
                // Atualizar steps
                updateCheckoutSteps(2);
                showSuccess('Boleto gerado com sucesso!');
                
            } else {
                showError('Erro ao gerar boleto: ' + (response.error || 'Erro desconhecido'));
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .fail(function(xhr) {
            let error = 'Erro ao processar boleto. Tente novamente.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                error = xhr.responseJSON.error;
            }
            showError(error);
            button.innerHTML = originalText;
            button.disabled = false;
        });
}

// Gerar QR Code PIX
function gerarQRCode() {
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    const qrContainer = document.getElementById('qrcode-container');
    
    // Mostrar loading
    button.innerHTML = '<span class="loading-spinner"></span> Gerando QR Code...';
    button.disabled = true;
    qrContainer.innerHTML = '<div class="pix-qr-code"><i class="fas fa-spinner fa-spin"></i></div>';
    
    // Dados mínimos para PIX (dados vêm do link)
    const dados = {
        _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    // Fazer requisição para criar transação PIX
    $.post(button.dataset.url || window.location.href, dados)
        .done(function(response) {
            console.log('Dados PIX recebidos:', response);
            
            // Buscar QR Code em base64
            let qrCodeBase64 = '';
            if (response.pix_data && response.pix_data.qr_code && response.pix_data.qr_code.qrcode) {
                qrCodeBase64 = response.pix_data.qr_code.qrcode;
            } else if (response.pix_data && response.pix_data.qr_code && typeof response.pix_data.qr_code === 'string' && response.pix_data.qr_code.startsWith('data:image')) {
                qrCodeBase64 = response.pix_data.qr_code;
            }
            
            // Buscar código PIX
            let pixCode = '';
            if (response.pix_data && response.pix_data.qr_code && response.pix_data.qr_code.emv) {
                pixCode = response.pix_data.qr_code.emv;
            } else if (response.pix_data && response.pix_data.transacao && response.pix_data.transacao.emv) {
                pixCode = response.pix_data.transacao.emv;
            }
            
            console.log('QR Code base64 encontrado:', qrCodeBase64 ? 'Sim' : 'Não');
            console.log('Código PIX encontrado:', pixCode);
            
            if (qrCodeBase64) {
                // Mostrar imagem base64 diretamente
                qrContainer.innerHTML = `<img src="${qrCodeBase64}" alt="QR Code PIX" class="img-fluid" style="max-width: 200px;">`;
                
                // Preencher código PIX se disponível
                if (pixCode) {
                    document.getElementById('pix-code').value = pixCode;
                }
                
                // Mostrar botão de download
                document.getElementById('downloadBtn').style.display = 'inline-block';
                
                // Atualizar botão principal
                button.innerHTML = '<i class="fas fa-check me-2"></i>QR Code Gerado';
                button.classList.remove('btn-payment');
                button.classList.add('btn-success');
                button.disabled = true;
                
                // Atualizar steps
                updateCheckoutSteps(2);
                showSuccess('QR Code PIX gerado com sucesso!');
                
            } else {
                console.error('QR Code base64 não encontrado nos dados:', response);
                qrContainer.innerHTML = '<div class="pix-qr-code"><i class="fas fa-qrcode"></i></div>';
                showError('Erro: QR Code não encontrado');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .fail(function(xhr) {
            qrContainer.innerHTML = '<div class="pix-qr-code"><i class="fas fa-qrcode"></i></div>';
            let error = 'Erro ao gerar QR Code PIX. Tente novamente.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                error = xhr.responseJSON.error;
            }
            showError(error);
            button.innerHTML = originalText;
            button.disabled = false;
        });
}

// Copiar código PIX
function copyPixCode() {
    const pixCode = document.getElementById('pix-code');
    pixCode.select();
    pixCode.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Mostrar feedback
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i>';
    button.classList.remove('btn-outline-secondary');
    button.classList.add('btn-success');
    
    showSuccess('Código PIX copiado com sucesso!');
    
    setTimeout(function() {
        button.innerHTML = originalText;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}

// Baixar QR Code
function downloadQrCode() {
    const qrImg = document.querySelector('#qrcode-container img');
    if (qrImg) {
        const link = document.createElement('a');
        link.download = 'qrcode-pix.png';
        link.href = qrImg.src;
        link.click();
    }
}

// Funções para mostrar/ocultar campos editáveis
function toggleClientFields() {
    const clientFields = document.getElementById('clientFields');
    if (clientFields) {
        const isHidden = clientFields.style.display === 'none';
        clientFields.style.display = isHidden ? 'block' : 'none';
        
        // Atualizar texto do botão
        const button = event.target.closest('button');
        button.innerHTML = isHidden ? 
            '<i class="fas fa-times me-1"></i>Voltar' : 
            '<i class="fas fa-edit me-1"></i>Editar';
    }
}

function toggleAddressFields() {
    const addressFields = document.getElementById('addressFields');
    if (addressFields) {
        const isHidden = addressFields.style.display === 'none';
        addressFields.style.display = isHidden ? 'block' : 'none';
        
        // Atualizar texto do botão
        const button = event.target.closest('button');
        button.innerHTML = isHidden ? 
            '<i class="fas fa-times me-1"></i>Voltar' : 
            '<i class="fas fa-edit me-1"></i>Editar';
    }
}

// Inicialização quando o documento estiver pronto
$(document).ready(function() {
    // Máscaras para cartão
    $('input[name="card[card_number]"]').mask('0000 0000 0000 0000');
    
    // Máscaras para cliente (todos os tipos)
    $('input[name="client[phone]"]').mask('(00) 00000-0000');
    $('input[name="client[document]"]').mask('000.000.000-00');
    $('input[name="client[address][zip_code]"]').mask('00000-000');
    
    // Form submit para cartão
    $('#creditForm').submit(function(e) {
        e.preventDefault();
        processarCartao($(this));
    });
    
    // Validação em tempo real
    $('input[required]').on('blur', function() {
        const $this = $(this);
        const value = $this.val().trim();
        
        if (value === '') {
            $this.addClass('is-invalid');
        } else {
            $this.removeClass('is-invalid').addClass('is-valid');
        }
    });
});

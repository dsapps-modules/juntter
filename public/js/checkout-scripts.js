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
                // Verificar se precisa de autenticação 3DS
                if (response.requires_3ds && response.session_id) {
                    processar3DS(response.session_id, response.transaction_id, form, submitBtn, originalText);
                } else {
                    // Sucesso sem 3DS
                    updateCheckoutSteps(2);
                    $('#successModal').modal('show');
                }
            } else {
                showError(response.error || 'Erro ao processar pagamento');
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        })
        .fail(function(xhr) {
            let error = 'Erro ao processar pagamento. Tente novamente.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                error = xhr.responseJSON.error;
            }
            showError(error);
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        });
}

// Processar autenticação 3DS
function processar3DS(sessionId, transactionId, form, submitBtn, originalText) {
    try {
        // Configurar SDK PagSeguro
        PagSeguro.setUp({
            session: sessionId,
            env: 'SANDBOX' // ou 'PROD' para produção
        });

        // Coletar dados do formulário
        const formData = new FormData(form[0]);
        const data = {};
        
        // Converter dados do formulário
        for (let [key, value] of formData.entries()) {
            const keys = key.split(/[\[\]]/).filter(k => k !== '');
            let current = data;
            
            for (let i = 0; i < keys.length - 1; i++) {
                if (!current[keys[i]]) {
                    current[keys[i]] = {};
                }
                current = current[keys[i]];
            }
            current[keys[keys.length - 1]] = value;
        }

        // Montar payload
        const request = {
            data: {
                customer: {
                    name: data.client.first_name + ' ' + (data.client.last_name || ''),
                    mail: data.client.email,
                    phones: [
                        {
                            country: '55',
                            area: data.client.phone.substring(0, 2),
                            number: data.client.phone.substring(2),
                            type: 'MOBILE'
                        }
                    ]
                },
                paymentMethod: {
                    type: 'CREDIT_CARD',
                    installments: parseInt(data.installments) || 1,
                    card: {
                        number: data.card.card_number.replace(/\s/g, ''),
                        expMonth: data.card.expiration_month.toString().padStart(2, '0'),
                        expYear: data.card.expiration_year.toString(),
                        holder: {
                            name: data.card.holder_name
                        }
                    }
                },
                amount: {
                    value: getAmountFromForm(form),
                    currency: 'BRL'
                },
                billingAddress: {
                    street: data.client.address.street,
                    number: data.client.address.number,
                    complement: data.client.address.complement || '',
                    regionCode: data.client.address.state,
                    country: 'BRA',
                    city: data.client.address.city,
                    postalCode: data.client.address.zip_code.replace(/\D/g, '')
                },
                shippingAddress: {
                    street: data.client.address.street,
                    number: data.client.address.number,
                    complement: data.client.address.complement || '',
                    regionCode: data.client.address.state,
                    country: 'BRA',
                    city: data.client.address.city,
                    postalCode: data.client.address.zip_code.replace(/\D/g, '')
                },
                dataOnly: false
            }
        };

        // Executar autenticação 3DS
        PagSeguro.authenticate3DS(request)
            .then(function(result) {
                // Enviar resultado para o endpoint
                enviarResultado3DS(transactionId, result, submitBtn, originalText);
            })
            .catch(function(err) {
                console.error('Erro no SDK 3DS:', err);
                
                if (err instanceof PagSeguro.PagSeguroError) {
                    showError('Erro na autenticação 3DS: ' + err.message);
                } else {
                    showError('Erro na autenticação 3DS. Tente novamente.');
                }
                
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            });

    } catch (error) {
        console.error('Erro ao configurar 3DS:', error);
        showError('Erro ao configurar autenticação 3DS');
        submitBtn.html(originalText);
        submitBtn.prop('disabled', false);
    }
}

// Enviar resultado do 3DS para o backend
function enviarResultado3DS(transactionId, result, submitBtn, originalText) {
    const authData = {
        id: result.id,
        status: result.status,
        authentication_status: result.authentication_status || 'NOT_AUTHENTICATED',
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    // Determinar URL baseada no contexto
    const isCobrancaUnica = window.location.pathname.includes('/cobranca');
    const url = isCobrancaUnica 
        ? `/cobranca/transacao/${transactionId}/antifraud-auth`
        : `/pagamento/${window.location.pathname.split('/')[2]}/antifraud-auth`;

    $.post(url, authData)
        .done(function(response) {
            if (response.success) {
                updateCheckoutSteps(2);
                $('#successModal').modal('show');
                showSuccess('Pagamento processado com sucesso!');
            } else {
                showError(response.message || 'Erro ao processar autenticação');
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        })
        .fail(function(xhr) {
            console.error('Erro:', xhr);
            showError('Erro ao processar autenticação. Tente novamente.');
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        });
}

// Função auxiliar para obter valor em centavos
function getAmountFromForm(form) {
    const amountField = form.find('input[name*="amount"], .order-summary .total-value');
    if (amountField.length > 0) {
        const valueText = amountField.first().text() || amountField.first().val() || '0';
        const value = parseFloat(valueText.replace(/[R$\s]/g, '').replace(',', '.'));
        return Math.round(value * 100); 
    }
    
    return 100; 
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
            } else if (response.pix_data && response.pix_data.pix_code) {
                pixCode = response.pix_data.pix_code;
            }
          
            
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

// Validação de Cartão de Crédito
function validateCardNumber(cardNumber) {
    // Remove espaços e caracteres não numéricos
    const cleanNumber = cardNumber.replace(/\s/g, '');
    
    // Verifica se tem apenas números
    if (!/^\d+$/.test(cleanNumber)) {
        return { valid: false, type: null, message: 'Número inválido' };
    }
    
    // Verifica comprimento mínimo
    if (cleanNumber.length < 13 || cleanNumber.length > 19) {
        return { valid: false, type: null, message: 'Número muito curto' };
    }
    
    // Algoritmo de Luhn
    if (!luhnCheck(cleanNumber)) {
        return { valid: false, type: null, message: 'Número inválido' };
    }
    
    // Identifica bandeira
    const cardType = identifyCardType(cleanNumber);
    
    return { valid: true, type: cardType, message: 'Válido' };
}

// Algoritmo de Luhn para validação
function luhnCheck(cardNumber) {
    let sum = 0;
    let isEven = false;
    
    for (let i = cardNumber.length - 1; i >= 0; i--) {
        let digit = parseInt(cardNumber.charAt(i));
        
        if (isEven) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }
        
        sum += digit;
        isEven = !isEven;
    }
    
    return sum % 10 === 0;
}

// Identifica tipo de cartão
function identifyCardType(cardNumber) {
    const patterns = {
        visa: /^4/,
        mastercard: /^5[1-5]/,
        amex: /^3[47]/,
        discover: /^6(?:011|5)/,
        diners: /^3[0689]/,
        elo: /^((((636368)|(438935)|(504175)|(451416)|(636297))[0-9]{0,10})|((5067)|(4576)|(4011))[0-9]{0,12})$/,
        hipercard: /^(606282|3841)/,
        jcb: /^35/
    };
    
    for (const [type, pattern] of Object.entries(patterns)) {
        if (pattern.test(cardNumber)) {
            return type;
        }
    }
    
    return 'unknown';
}

// Validação de CVV
function validateCVV(cvv, cardType) {
    if (!/^\d+$/.test(cvv)) {
        return { valid: false, message: 'CVV inválido' };
    }
    
    const length = cardType === 'amex' ? 4 : 3;
    if (cvv.length !== length) {
        return { valid: false, message: `CVV deve ter ${length} dígitos` };
    }
    
    return { valid: true, message: 'Válido' };
}

// Validação de data de validade
function validateExpiryDate(month, year) {
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth() + 1;
    
    const expYear = parseInt(year);
    const expMonth = parseInt(month);
    
    if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) {
        return { valid: false, message: 'Cartão expirado' };
    }
    
    if (expYear > currentYear + 20) {
        return { valid: false, message: 'Data muito distante' };
    }
    
    return { valid: true, message: 'Válido' };
}

// Validação de CPF/CNPJ
function validateDocument(document) {
    const cleanDoc = document.replace(/\D/g, '');
    
    if (cleanDoc.length === 11) {
        return validateCPF(cleanDoc);
    } else if (cleanDoc.length === 14) {
        return validateCNPJ(cleanDoc);
    }
    
    return { valid: false, message: 'Documento inválido' };
}

function validateCPF(cpf) {
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
        return { valid: false, message: 'CPF inválido' };
    }
    
    let sum = 0;
    for (let i = 0; i < 9; i++) {
        sum += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let remainder = 11 - (sum % 11);
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.charAt(9))) {
        return { valid: false, message: 'CPF inválido' };
    }
    
    sum = 0;
    for (let i = 0; i < 10; i++) {
        sum += parseInt(cpf.charAt(i)) * (11 - i);
    }
    remainder = 11 - (sum % 11);
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.charAt(10))) {
        return { valid: false, message: 'CPF inválido' };
    }
    
    return { valid: true, message: 'CPF válido' };
}

function validateCNPJ(cnpj) {
    if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) {
        return { valid: false, message: 'CNPJ inválido' };
    }
    
    let sum = 0;
    let weight = 2;
    for (let i = 11; i >= 0; i--) {
        sum += parseInt(cnpj.charAt(i)) * weight;
        weight = weight === 9 ? 2 : weight + 1;
    }
    let remainder = sum % 11;
    let digit1 = remainder < 2 ? 0 : 11 - remainder;
    if (digit1 !== parseInt(cnpj.charAt(12))) {
        return { valid: false, message: 'CNPJ inválido' };
    }
    
    sum = 0;
    weight = 2;
    for (let i = 12; i >= 0; i--) {
        sum += parseInt(cnpj.charAt(i)) * weight;
        weight = weight === 9 ? 2 : weight + 1;
    }
    remainder = sum % 11;
    let digit2 = remainder < 2 ? 0 : 11 - remainder;
    if (digit2 !== parseInt(cnpj.charAt(13))) {
        return { valid: false, message: 'CNPJ inválido' };
    }
    
    return { valid: true, message: 'CNPJ válido' };
}

// Validação de email
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return {
        valid: emailRegex.test(email),
        message: emailRegex.test(email) ? 'Email válido' : 'Email inválido'
    };
}

// Validação de telefone
function validatePhone(phone) {
    const cleanPhone = phone.replace(/\D/g, '');
    return {
        valid: cleanPhone.length === 11,
        message: cleanPhone.length === 11 ? 'Telefone válido' : 'Telefone inválido'
    };
}

// Mostrar feedback visual
function showFieldValidation(field, isValid, message, type = null) {
    const $field = $(field);
    const $feedback = $field.siblings('.invalid-feedback, .valid-feedback');
    
    // Remove classes anteriores
    $field.removeClass('is-valid is-invalid');
    
    // Remove ícones anteriores
    $field.siblings('.field-icon').remove();
    
    if (isValid) {
        $field.addClass('is-valid');
        $feedback.removeClass('invalid-feedback').addClass('valid-feedback').text(message);
        
        // Adiciona ícone de sucesso
        $field.after('<i class="fas fa-check-circle field-icon text-success"></i>');
    } else {
        $field.addClass('is-invalid');
        $feedback.removeClass('valid-feedback').addClass('invalid-feedback').text(message);
        
        // Adiciona ícone de erro
        $field.after('<i class="fas fa-exclamation-circle field-icon text-danger"></i>');
    }
    
    // Atualiza ícone baseado no tipo de cartão
    if (type && field.name === 'card[card_number]') {
        updateCardTypeIcon(type);
    }
}

// Atualiza ícone do tipo de cartão
function updateCardTypeIcon(cardType) {
    // Remove ícone anterior do tipo de cartão
    $('.card-type-icon').remove();
    
    const icons = {
        visa: 'fab fa-cc-visa',
        mastercard: 'fab fa-cc-mastercard',
        amex: 'fab fa-cc-amex',
        discover: 'fab fa-cc-discover',
        diners: 'fab fa-cc-diners-club',
        elo: 'fas fa-credit-card',
        hipercard: 'fas fa-credit-card',
        jcb: 'fab fa-cc-jcb',
        unknown: 'fas fa-credit-card'
    };
    
    // Adiciona novo ícone
    $('input[name="card[card_number]"]').after(`<i class="${icons[cardType] || icons.unknown} card-type-icon"></i>`);
}

// Busca CEP via ViaCEP
function buscarCEP(cep) {
    // Remove caracteres não numéricos
    cep = cep.replace(/\D/g, '');
    
    // Verifica se CEP tem 8 dígitos
    if (cep.length !== 8) {
        return;
    }
    
    // Mostra loading no campo CEP
    const cepField = $('input[name="client[address][zip_code]"]');
    cepField.addClass('is-loading');
    
    // Busca CEP na API ViaCEP
    $.get(`https://viacep.com.br/ws/${cep}/json/`)
        .done(function(data) {
            if (data.erro) {
                showFieldValidation(cepField[0], false, 'CEP não encontrado');
                return;
            }
            
            // Preenche os campos automaticamente
            $('input[name="client[address][street]"]').val(data.logradouro);
            $('input[name="client[address][neighborhood]"]').val(data.bairro);
            $('input[name="client[address][city]"]').val(data.localidade);
            $('select[name="client[address][state]"]').val(data.uf);
            
            // Valida CEP como válido
            showFieldValidation(cepField[0], true, 'CEP encontrado');
            
            // Foca no campo número
            $('input[name="client[address][number]"]').focus();
            
            showSuccess('Endereço preenchido automaticamente!');
        })
        .fail(function() {
            showFieldValidation(cepField[0], false, 'Erro ao buscar CEP');
        })
        .always(function() {
            cepField.removeClass('is-loading');
        });
}

// Inicialização quando o documento estiver pronto
$(document).ready(function() {
    // Máscaras para cartão
    $('input[name="card[card_number]"]').mask('0000 0000 0000 0000');
    
    // Máscaras para cliente (todos os tipos)
    $('input[name="client[phone]"]').mask('(00) 00000-0000');
    $('input[name="client[document]"]').mask('000.000.000-00');
    $('input[name="client[address][zip_code]"]').mask('00000-000');
    
    // Busca CEP automaticamente
    $('input[name="client[address][zip_code]"]').on('blur', function() {
        const cep = $(this).val();
        if (cep.length === 9) { // 00000-000
            buscarCEP(cep);
        }
    });
    
    // Validação em tempo real do cartão
    $('input[name="card[card_number]"]').on('input', function() {
        const value = $(this).val();
        const cleanValue = value.replace(/\s/g, '');
        
        // Só valida se tiver pelo menos 13 dígitos
        if (cleanValue.length >= 13) {
            const validation = validateCardNumber(value);
            showFieldValidation(this, validation.valid, validation.message, validation.type);
        } else {
            // Remove validação se estiver muito curto
            $(this).removeClass('is-valid is-invalid');
            $(this).siblings('.field-icon, .card-type-icon').remove();
        }
    });
    
    // Validação do CVV
    $('input[name="card[security_code]"]').on('input', function() {
        const cvv = $(this).val();
        const cardNumber = $('input[name="card[card_number]"]').val();
        const cardType = identifyCardType(cardNumber.replace(/\s/g, ''));
        
        // Só valida se tiver pelo menos 3 dígitos
        if (cvv.length >= 3) {
            const validation = validateCVV(cvv, cardType);
            showFieldValidation(this, validation.valid, validation.message);
        } else {
            // Remove validação se estiver muito curto
            $(this).removeClass('is-valid is-invalid');
            $(this).siblings('.field-icon').remove();
        }
    });
    
    // Validação da data de validade
    $('select[name="card[expiry_month]"], select[name="card[expiry_year]"]').on('change', function() {
        const month = $('select[name="card[expiry_month]"]').val();
        const year = $('select[name="card[expiry_year]"]').val();
        
        if (month && year) {
            const validation = validateExpiryDate(month, year);
            showFieldValidation(this, validation.valid, validation.message);
        }
    });
    
    // Validação do documento
    $('input[name="client[document]"]').on('blur', function() {
        const value = $(this).val();
        const validation = validateDocument(value);
        showFieldValidation(this, validation.valid, validation.message);
    });
    
    // Validação do email
    $('input[name="client[email]"]').on('blur', function() {
        const value = $(this).val();
        const validation = validateEmail(value);
        showFieldValidation(this, validation.valid, validation.message);
    });
    
    // Validação do telefone
    $('input[name="client[phone]"]').on('blur', function() {
        const value = $(this).val();
        const validation = validatePhone(value);
        showFieldValidation(this, validation.valid, validation.message);
    });
    
    // Form submit para cartão
    $('#creditForm').submit(function(e) {
        e.preventDefault();
        
        // Validação final antes de enviar
        let isValid = true;
        const requiredFields = $(this).find('input[required], select[required]');
        
        requiredFields.each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (value === '') {
                $field.addClass('is-invalid');
                isValid = false;
            }
        });
        
        if (isValid) {
            processarCartao($(this));
        } else {
            showError('Por favor, preencha todos os campos obrigatórios');
        }
    });
    
    // Validação em tempo real para outros campos
    $('input[required]:not([name="card[card_number]"], [name="card[cvv]"], [name="client[document]"], [name="client[email]"], [name="client[phone]"])').on('blur', function() {
        const $this = $(this);
        const value = $this.val().trim();
        
        if (value === '') {
            $this.addClass('is-invalid').removeClass('is-valid');
        } else {
            $this.addClass('is-valid').removeClass('is-invalid');
        }
    });
});

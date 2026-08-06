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

function normalizeDigits(value) {
    return String(value ?? '').replace(/\D/g, '');
}

function formatCpf(value) {
    const digits = normalizeDigits(value).slice(0, 11);

    if (digits.length <= 3) {
        return digits;
    }

    if (digits.length <= 6) {
        return `${digits.slice(0, 3)}.${digits.slice(3)}`;
    }

    if (digits.length <= 9) {
        return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;
    }

    return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
}

function formatCnpj(value) {
    const digits = normalizeDigits(value).slice(0, 14);

    if (digits.length <= 2) {
        return digits;
    }

    if (digits.length <= 5) {
        return `${digits.slice(0, 2)}.${digits.slice(2)}`;
    }

    if (digits.length <= 8) {
        return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
    }

    if (digits.length <= 12) {
        return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8)}`;
    }

    return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12)}`;
}

function formatDocument(value) {
    const digits = normalizeDigits(value);

    if (digits.length > 11) {
        return formatCnpj(digits);
    }

    return formatCpf(digits);
}

function formatCurrencyFromCents(value) {
    const numericValue = Number(value ?? 0) / 100;

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(Number.isFinite(numericValue) ? numericValue : 0);
}

function normalizeCardBrandKey(value) {
    return String(value ?? '').toLowerCase().replace(/[^a-z0-9]+/g, '');
}

function getCreditCardPricingConfig() {
    return window.JuntterRoutes?.credit_card_pricing || {};
}

function resolveCardBrandAliases(cardType) {
    const normalizedCardType = normalizeCardBrandKey(cardType);

    const aliases = {
        visa: ['visa'],
        mastercard: ['mastercard', 'mastercardcredit'],
        amex: ['amex', 'americanexpress'],
        discover: ['discover'],
        diners: ['diners', 'dinersclub', 'dinersclubinternational'],
        elo: ['elo'],
        hipercard: ['hipercard', 'hiper'],
        jcb: ['jcb'],
    };

    return aliases[normalizedCardType] || [normalizedCardType];
}

function resolvePricingFlagForCardBrand(flags, cardType) {
    if (!Array.isArray(flags) || flags.length === 0) {
        return null;
    }

    const aliases = resolveCardBrandAliases(cardType);

    return flags.find((flag) => aliases.includes(normalizeCardBrandKey(flag?.name))) || null;
}

function resolveCreditCardInstallmentNumbers() {
    const pricingConfig = getCreditCardPricingConfig();
    const configuredInstallments = Array.isArray(pricingConfig.allowed_installments) ? pricingConfig.allowed_installments : [];
    const numericInstallments = configuredInstallments
        .map((value) => Number.parseInt(String(value), 10))
        .filter((value) => Number.isFinite(value) && value >= 1)
        .sort((left, right) => left - right);

    if (numericInstallments.length > 0) {
        return numericInstallments;
    }

    const maxInstallments = Number.parseInt(String(pricingConfig.max_installments || 0), 10) || 0;

    if (maxInstallments > 1) {
        return Array.from({ length: maxInstallments }, (_, index) => index + 1);
    }

    return Array.from({ length: 10 }, (_, index) => index + 1);
}

function buildInstallmentOptionLabel(installmentCount, installmentValueCents) {
    return `${installmentCount}x ${formatCurrencyFromCents(installmentValueCents)}`;
}

function renderInstallmentsForCardBrand(cardType) {
    const $select = $('#installmentsSelect');

    if (!$select.length) {
        return;
    }

    const pricingConfig = getCreditCardPricingConfig();
    const flags = Array.isArray(pricingConfig.flags) ? pricingConfig.flags : [];
    const matchedFlag = resolvePricingFlagForCardBrand(flags, cardType);
    const fallbackFlag = flags.find((flag) => Array.isArray(flag?.fees?.credit) && Object.keys(flag.fees.credit).length > 0) || null;
    const pricingFlag = matchedFlag || fallbackFlag;
    const baseAmountCents = Number.parseInt(String(pricingConfig.base_amount_cents || 0), 10) || 0;
    const interestMode = String(pricingConfig.interest || 'ESTABLISHMENT').toUpperCase();
    const allowedInstallments = resolveCreditCardInstallmentNumbers();

    if (!cardType && !pricingFlag) {
        $select
            .prop('disabled', true)
            .empty()
            .append($('<option>', {
                value: '',
                text: 'Digite o número do cartão para carregar as parcelas',
            }));
        return;
    }

    const creditFees = pricingFlag?.fees?.credit || {};
    const configuredInstallments = Object.entries(creditFees)
        .map(([key, value]) => {
            const installmentCount = Number.parseInt(String(key), 10);
            const rate = Number.parseFloat(String(value));

            return {
                installmentCount,
                rate,
            };
        })
        .filter((item) => Number.isFinite(item.installmentCount) && Number.isFinite(item.rate))
        .sort((left, right) => left.installmentCount - right.installmentCount)
        .filter((item) => allowedInstallments.includes(item.installmentCount));

    const installmentSource = configuredInstallments.length > 0
        ? configuredInstallments
        : allowedInstallments.map((installmentCount) => ({
            installmentCount,
            rate: 0,
        }));

    const options = installmentSource.map(({ installmentCount, rate }) => {
        const customerChargeCents = creditFees && Object.keys(creditFees).length > 0
            ? (interestMode === 'CLIENT'
                ? baseAmountCents + Math.round(baseAmountCents * (rate / 100))
                : baseAmountCents)
            : baseAmountCents;
        const installmentValueCents = installmentCount > 0
            ? Math.round(customerChargeCents / installmentCount)
            : 0;

        return {
            label: buildInstallmentOptionLabel(installmentCount, installmentValueCents),
            value: String(installmentCount),
        };
    });

    const currentValue = String($select.val() || '');

    $select.empty();

    if (options.length === 0) {
        $select
            .prop('disabled', true)
            .append($('<option>', {
                value: '',
                text: 'Nenhuma parcela disponível',
            }));
        return;
    }

    $select.prop('disabled', false);
    $select.append($('<option>', {
        value: '',
        text: 'Selecione...',
        selected: !currentValue,
        disabled: true,
    }));

    options.forEach((option) => {
        $select.append($('<option>', {
            value: option.value,
            text: option.label,
        }));
    });

    if (currentValue && options.some((option) => option.value === currentValue)) {
        $select.val(currentValue);
    } else {
        $select.val(options[0].value);
    }
}

function syncCreditCardBrand(cardType) {
    $('input[name="card_brand"]').val(cardType || '');
    renderInstallmentsForCardBrand(cardType);
}

function applyDocumentMask(field) {
    const $field = $(field);

    if (!$field.length) {
        return;
    }

    $field.val(formatDocument($field.val()));
}

function redirectToPaymentSuccess() {
    const successUrl = window.JuntterRoutes?.pagamento_sucesso;

    if (!successUrl) {
        showError('A página de confirmação não está disponível.');
        return;
    }

    const url = new URL(successUrl, window.location.origin);
    const sellerBrand = window.JuntterRoutes?.seller_brand || {};

    if (sellerBrand.mode) {
        url.searchParams.set('seller_brand_mode', sellerBrand.mode);
    }

    if (sellerBrand.label) {
        url.searchParams.set('seller_brand_label', sellerBrand.label);
    }

    if (sellerBrand.logoUrl) {
        url.searchParams.set('seller_brand_logo_url', sellerBrand.logoUrl);
    }

    location.href = url.toString();
}

function redirectToPaymentError(message) {
    const errorUrl = window.JuntterRoutes?.pagamento_erro;

    if (!errorUrl) {
        showError(message || 'Não foi possível concluir o pagamento.');
        return;
    }

    const url = new URL(errorUrl, window.location.origin);
    const sellerBrand = window.JuntterRoutes?.seller_brand || {};

    if (sellerBrand.mode) {
        url.searchParams.set('seller_brand_mode', sellerBrand.mode);
    }

    if (sellerBrand.label) {
        url.searchParams.set('seller_brand_label', sellerBrand.label);
    }

    if (sellerBrand.logoUrl) {
        url.searchParams.set('seller_brand_logo_url', sellerBrand.logoUrl);
    }

    url.searchParams.set('message', message || 'Não foi possível concluir o pagamento.');
    url.searchParams.set('return_url', window.location.href);

    location.href = url.toString();
}

// Processar pagamento com cartão
function processarCartao(form) {
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.html();

    // Mostrar loading
    submitBtn.html('<span class="loading-spinner"></span> Processando...');
    submitBtn.prop('disabled', true);

    const url = form.data('url');
    const data = form.serialize();

    $.post(url, data)
        // Verificar se precisa de autenticação 3DS
        .done(function (response) {
            console.log('4. Solicita autenticação 3Ds com esses dados:')
            console.log(response)
            if (response.success) {
                if (response.requires_3ds && response.session_id) {
                    processar3DS(response.session_id, response.transaction_id, form, submitBtn, originalText);
                } else {
                    // Sucesso sem 3DS
                    updateCheckoutSteps(2);
                    redirectToPaymentSuccess();
                }
            } else {
                redirectToPaymentError(response.error || 'Erro ao processar pagamento');
                if (response.paytime_response) {
                    showPaytimeError(response.paytime_response);
                }
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        })
        .fail(function (xhr) {
            let error = 'Erro ao processar pagamento. Tente novamente.';
            let paytimeResponse = null;
            if (xhr.responseJSON) {
                if (xhr.responseJSON.error) error = xhr.responseJSON.error;
                if (xhr.responseJSON.paytime_response) paytimeResponse = xhr.responseJSON.paytime_response;
            }
            redirectToPaymentError(error);
            if (paytimeResponse) {
                showPaytimeError(paytimeResponse);
            }
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        });
}

function showPaytimeError(response) {
    const alert = $('#paytime-error-alert');
    if (alert.length) {
        alert.find('.paytime-error-content').text(JSON.stringify(response, null, 4));
        alert.removeClass('d-none');
        // Scroll para o alerta
        $('html, body').animate({
            scrollTop: alert.offset().top - 100
        }, 500);
    }
}

// Processar autenticação 3DS
function processar3DS(sessionId, transactionId, form, submitBtn, originalText) {
    try {
        // Configurar SDK PagSeguro
        PagSeguro.setUp({
            session: sessionId,
            env: 'SANDBOX' // TODO ou 'PROD' para produção
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

        const phone = data.client.phone.replace(/[()\s-]+/g, '')

        // Montar payload
        const request = {
            data: {
                customer: {
                    name: data.client.first_name + ' ' + (data.client.last_name || ''),
                    email: data.client.email,
                    phones: [
                        {
                            country: '55',
                            area: parseInt(phone.substring(0, 2)),
                            number: parseInt(phone.substring(2)),
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
                        holder: { name: data.card.holder_name }
                    }
                },
                amount: {
                    value: getAmountFromForm(form),
                    currency: 'BRL'
                },
                billingAddress: {
                    street: data.client.address.street,
                    number: data.client.address.number,
                    complement: data.client.address.complement || null,
                    regionCode: data.client.address.state,
                    country: 'BRA',
                    city: data.client.address.city,
                    postalCode: data.client.address.zip_code.replace(/\D/g, '')
                },
                shippingAddress: {
                    street: data.client.address.street,
                    number: data.client.address.number,
                    complement: data.client.address.complement || null,
                    regionCode: data.client.address.state,
                    country: 'BRA',
                    city: data.client.address.city,
                    postalCode: data.client.address.zip_code.replace(/\D/g, '')
                },
                dataOnly: false
            }
        };

        console.log('5. Obtém os dados do form para enviar ao PagSeguro')
        console.log(data)

        // Executar autenticação 3DS
        PagSeguro.authenticate3DS(request)
            .then(function (result) {
                // Enviar resultado para o endpoint
                console.log(result)
                enviarResultado3DS(transactionId, result, submitBtn, originalText);
            })
            .catch(function (err) {
                console.error('Erro no SDK 3DS:', err);

                if (err instanceof PagSeguro.PagSeguroError) {
                    redirectToPaymentError('Erro na autenticação 3DS: ' + err.message);
                } else {
                    redirectToPaymentError('Erro na autenticação 3DS. Tente novamente.');
                }

                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            });

    } catch (error) {
        console.error('Erro ao configurar 3DS:', error);
        redirectToPaymentError('Erro ao configurar autenticação 3DS');
        submitBtn.html(originalText);
        submitBtn.prop('disabled', false);
    }
}

// Enviar resultado do 3DS para o backend
function enviarResultado3DS(transactionId, result, submitBtn, originalText) {
    const authData = {
        id: result.id,
        status: result.status,
        authentication_status: result.authenticationStatus,
        _token: $('meta[name="_token"]').attr('content')
    };

    const cod = window.location.pathname.split('/')[2];
    const cpl = cod ? `/${cod}` : '';
    const url = `/pagamento/confirmar3ds/${transactionId}${cpl}`

    console.log('6. Faz post no url:' + url + ' com os dados abaixo, recebidos do PagSeguro:')
    console.log(authData)

    $.post(url, authData)
        .done(function (response) {
            console.log(response)

                if (response.status == 'PENDING') {
                    updateCheckoutSteps(2);
                    console.log('9. Recebe a confirmação da API');
                    redirectToPaymentSuccess();
                } else {
                    redirectToPaymentError(response.message || 'Erro ao processar autenticação');
                    submitBtn.html(originalText);
                    submitBtn.prop('disabled', false);
                }
            })
            .fail(function (xhr) {
                console.error('Erro:', xhr);
                redirectToPaymentError('Erro ao processar autenticação. Tente novamente.');
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
        return parseInt(Math.round(value * 100), 10);
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
        _token: $('meta[name="_token"]').attr('content')
    };

    // Fazer requisição para criar boleto
    $.post(button.dataset.url || window.location.href, dados)
        .done(function (response) {
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
        .fail(function (xhr) {
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
        _token: $('meta[name="_token"]').attr('content')
    };

    // Fazer requisição para criar transação PIX
    $.post(button.dataset.url || window.location.href, dados)
        .done(function (response) {
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
        .fail(function (xhr) {
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

    setTimeout(function () {
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
        diners: /^3[0689]/,
        elo: /^(?:4011|4312|4389|4514|4576|5041|506[67]|509\d|6277|6362|6363|6504|6505|6506|6507|6508|6509|651\d|6550)/,
        discover: /^6(?:011|5)/,
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

// Atualiza a bandeira identificada do cartão
function updateCardTypeIcon(cardType) {
    const $preview = $('#cardBrandPreview');

    if (!$preview.length) {
        syncCreditCardBrand(cardType);
        return;
    }

    const brands = {
        visa: {
            icon: 'fab fa-cc-visa',
            label: 'Visa',
        },
        mastercard: {
            icon: 'fab fa-cc-mastercard',
            label: 'Mastercard',
        },
        amex: {
            icon: 'fab fa-cc-amex',
            label: 'Amex',
        },
        discover: {
            icon: 'fab fa-cc-discover',
            label: 'Discover',
        },
        diners: {
            icon: 'fab fa-cc-diners-club',
            label: 'Diners Club',
        },
        elo: {
            icon: 'fas fa-credit-card',
            label: 'Elo',
        },
        hipercard: {
            icon: 'fas fa-credit-card',
            label: 'Hipercard',
        },
        jcb: {
            icon: 'fab fa-cc-jcb',
            label: 'JCB',
        },
    };

    const brand = brands[cardType];

    if (!brand) {
        $preview.removeClass('is-populated').empty();
        syncCreditCardBrand('');
        return;
    }

    $preview
        .addClass('is-populated')
        .html(`<i class="${brand.icon} card-brand-preview-icon" aria-hidden="true"></i><span>${brand.label}</span>`);

    syncCreditCardBrand(cardType);
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
        .done(function (data) {
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
        .fail(function () {
            showFieldValidation(cepField[0], false, 'Erro ao buscar CEP');
        })
        .always(function () {
            cepField.removeClass('is-loading');
        });
}

// Inicialização quando o documento estiver pronto
$(document).ready(function () {
    // Máscaras para cartão
    $('input[name="card[card_number]"]').mask('0000 0000 0000 0000');
    updateCardTypeIcon(identifyCardType(($('input[name="card[card_number]"]').val() || '').replace(/\s/g, '')));
    $('input[name="card[holder_document]"]').on('input blur', function () {
        applyDocumentMask(this);
    });
    $('input[name="card[holder_document]"]').each(function () {
        applyDocumentMask(this);
    });

    // Máscaras para cliente (todos os tipos)
    $('input[name="client[phone]"]').mask('(00) 00000-0000');
    $('input[name="client[document]"]').on('input blur', function () {
        applyDocumentMask(this);
    });
    $('input[name="client[document]"]').each(function () {
        applyDocumentMask(this);
    });
    $('input[name="client[address][zip_code]"]').mask('00000-000');

    // Busca CEP automaticamente
    $('input[name="client[address][zip_code]"]').on('blur', function () {
        const cep = $(this).val();
        if (cep.length === 9) { // 00000-000
            buscarCEP(cep);
        }
    });

    // Validação em tempo real do cartão
    $('input[name="card[card_number]"]').on('input', function () {
        const value = $(this).val();
        const cleanValue = value.replace(/\s/g, '');
        const cardType = identifyCardType(cleanValue);

        updateCardTypeIcon(cardType);

        // Só valida se tiver pelo menos 13 dígitos
        if (cleanValue.length >= 13) {
            const validation = validateCardNumber(value);
            showFieldValidation(this, validation.valid, validation.message, validation.type);
        } else {
            // Remove validação se estiver muito curto
            $(this).removeClass('is-valid is-invalid');
            $(this).siblings('.field-icon').remove();
        }
    });

    // Validação do CVV
    $('input[name="card[security_code]"]').on('input', function () {
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
    $('select[name="card[expiry_month]"], select[name="card[expiry_year]"]').on('change', function () {
        const month = $('select[name="card[expiry_month]"]').val();
        const year = $('select[name="card[expiry_year]"]').val();

        if (month && year) {
            const validation = validateExpiryDate(month, year);
            showFieldValidation(this, validation.valid, validation.message);
        }
    });

    // Validação do documento
    $('input[name="client[document]"]').on('blur', function () {
        const value = $(this).val();
        const validation = validateDocument(value);
        showFieldValidation(this, validation.valid, validation.message);
    });

    // Validação do email
    $('input[name="client[email]"]').on('blur', function () {
        const value = $(this).val();
        const validation = validateEmail(value);
        showFieldValidation(this, validation.valid, validation.message);
    });

    // Validação do telefone
    $('input[name="client[phone]"]').on('blur', function () {
        const value = $(this).val();
        const validation = validatePhone(value);
        showFieldValidation(this, validation.valid, validation.message);
    });

    // Form submit para cartão
    $('#creditForm').submit(function (e) {
        e.preventDefault();

        // Validação final antes de enviar
        let isValid = true;
        const requiredFields = $(this).find('input[required], select[required]');

        requiredFields.each(function () {
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
    $('input[required]:not([name="card[card_number]"], [name="card[cvv]"], [name="client[document]"], [name="client[email]"], [name="client[phone]"])').on('blur', function () {
        const $this = $(this);
        const value = $this.val().trim();

        if (value === '') {
            $this.addClass('is-invalid').removeClass('is-valid');
        } else {
            $this.addClass('is-valid').removeClass('is-invalid');
        }
    });

    // Validação do valor do boleto (R$ 10,00 mínimo)
    $('#billet-amount').on('input blur', function () {
        const val = $(this).val().replace(/[R$\s.]/g, '').replace(',', '.');
        const amount = parseFloat(val);

        if (!isNaN(amount) && amount < 10) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
});

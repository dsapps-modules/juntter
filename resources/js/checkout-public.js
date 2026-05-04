import React from 'react';
import { createRoot } from 'react-dom/client';
import { QRCodeSVG } from '@rc-component/qrcode';

const stepOrder = ['identification', 'delivery', 'payment', 'waiting'];

const stepLabels = {
    identification: 'Identificação',
    delivery: 'Entrega',
    payment: 'Pagamento',
    waiting: 'Pagamento em andamento',
    confirmation: 'Confirmação',
};

function readConfig() {
    const configElement = document.getElementById('checkout-public-data');

    if (!configElement) {
        return null;
    }

    try {
        return JSON.parse(configElement.textContent || '{}');
    } catch (error) {
        return null;
    }
}

function formatCurrency(value) {
    const numericValue = Number(value ?? 0);

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(Number.isNaN(numericValue) ? 0 : numericValue);
}

function normalizeDigits(value) {
    return String(value ?? '').replace(/\D+/g, '');
}

function formatPhone(value) {
    const digits = normalizeDigits(value).slice(0, 11);

    if (!digits) {
        return '';
    }

    if (digits.length <= 2) {
        return `(${digits}`;
    }

    if (digits.length <= 6) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
    }

    if (digits.length <= 10) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
    }

    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
}

function formatCpf(value) {
    const digits = normalizeDigits(value).slice(0, 11);

    if (!digits) {
        return '';
    }

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

    if (!digits) {
        return '';
    }

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

function formatIdentificationDocument(value, documentType) {
    return documentType === 'cnpj' ? formatCnpj(value) : formatCpf(value);
}

function formatZipcode(value) {
    const digits = normalizeDigits(value).slice(0, 8);

    if (digits.length <= 5) {
        return digits;
    }

    return `${digits.slice(0, 5)}-${digits.slice(5)}`;
}

function isValidZipcode(value) {
    return normalizeDigits(value).length === 8;
}

async function lookupAddressByZipcode(zipcode) {
    const response = await fetch(`https://viacep.com.br/ws/${normalizeDigits(zipcode)}/json/`, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('Não foi possível consultar o CEP.');
    }

    const payload = await response.json();

    if (payload.erro) {
        throw new Error('CEP não encontrado.');
    }

    return payload;
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function setFeedback(message, type = 'error') {
    const feedback = document.querySelector('[data-feedback]');

    if (!feedback) {
        return;
    }

    feedback.textContent = message;
    feedback.classList.add('is-visible');
    feedback.classList.toggle('is-error', type === 'error');
    feedback.classList.toggle('is-success', type === 'success');
}

function clearFeedback() {
    const feedback = document.querySelector('[data-feedback]');

    if (!feedback) {
        return;
    }

    feedback.textContent = '';
    feedback.classList.remove('is-visible', 'is-error', 'is-success');
}

function clearFieldErrors() {
    document.querySelectorAll('[data-error-for]').forEach((element) => {
        element.textContent = '';
    });
}

function setFieldErrors(errors = {}) {
    Object.entries(errors).forEach(([field, messages]) => {
        const elements = document.querySelectorAll(`[data-error-for="${field}"]`);

        if (!elements.length) {
            return;
        }

        const firstMessage = Array.isArray(messages) ? messages[0] : messages;
        elements.forEach((element) => {
            element.textContent = firstMessage ?? '';
        });
    });
}

function setBusy(form, busy, label = 'Processando...') {
    const submitButton = form.querySelector('button[type="submit"]');

    if (!submitButton) {
        return;
    }

    if (busy) {
        submitButton.dataset.originalLabel = submitButton.textContent ?? '';
        submitButton.disabled = true;
        submitButton.textContent = label;
        return;
    }

    submitButton.disabled = false;

    if (submitButton.dataset.originalLabel) {
        submitButton.textContent = submitButton.dataset.originalLabel;
    }
}

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrfToken(),
            ...(options.headers || {}),
        },
        ...options,
    });

    const contentType = response.headers.get('content-type') || '';
    const payload = contentType.includes('application/json') ? await response.json() : null;

    if (!response.ok) {
        const error = new Error(payload?.message || 'Não foi possível concluir a requisição.');
        error.status = response.status;
        error.payload = payload;
        throw error;
    }

    return payload;
}

function fillInput(selector, value) {
    const element = document.querySelector(selector);

    if (!element) {
        return;
    }

    if (element.type === 'checkbox') {
        element.checked = Boolean(value);
        return;
    }

    const fieldName = element.name || '';
    const documentType = fieldName === 'customer_document'
        ? String(element.form?.querySelector('[name="customer_document_type"]')?.value || 'cpf').toLowerCase()
        : '';

    if (fieldName === 'customer_phone') {
        element.value = formatPhone(value);
        return;
    }

    if (fieldName === 'customer_document') {
        element.value = formatIdentificationDocument(value, documentType);
        return;
    }

    if (fieldName === 'zipcode') {
        element.value = formatZipcode(value);
        return;
    }

    element.value = value ?? '';
}

function fillFormField(form, selector, value) {
    const element = form?.querySelector(selector);

    if (!element) {
        return;
    }

    if (element.type === 'checkbox') {
        element.checked = Boolean(value);
        return;
    }

    const fieldName = element.name || '';
    const documentType = fieldName === 'customer_document'
        ? String(element.form?.querySelector('[name="customer_document_type"]')?.value || 'cpf').toLowerCase()
        : '';

    if (fieldName === 'customer_phone') {
        element.value = formatPhone(value);
        return;
    }

    if (fieldName === 'customer_document') {
        element.value = formatIdentificationDocument(value, documentType);
        return;
    }

    if (fieldName === 'zipcode') {
        element.value = formatZipcode(value);
        return;
    }

    element.value = value ?? '';
}

function collectFormValues(form) {
    const values = {};
    form.querySelectorAll('input[name], select[name], textarea[name]').forEach((element) => {
        if (element.type === 'checkbox') {
            values[element.name] = element.checked;
            return;
        }

        values[element.name] = element.value;
    });

    return values;
}

function identificationDocumentType(form) {
    return String(form?.querySelector('[name="customer_document_type"]')?.value || 'cpf');
}

function hydrateSession(session, config) {
    if (!session) {
        return;
    }

    fillInput('[name="zipcode"]', session.zipcode);
    fillInput('[name="street"]', session.street);
    fillInput('[name="number"]', session.number);
    fillInput('[name="complement"]', session.complement);
    fillInput('[name="neighborhood"]', session.neighborhood);
    fillInput('[name="city"]', session.city);
    fillInput('[name="state"]', session.state);
    fillInput('[name="recipient_name"]', session.recipient_name);
    const defaultPaymentMethod = session.payment_method
        || (config?.checkoutLink?.allowPix ? 'pix' : (config?.checkoutLink?.allowBoleto ? 'boleto' : (config?.checkoutLink?.allowCreditCard ? 'credit_card' : '')));

    if (defaultPaymentMethod) {
        fillInput('[name="payment_method"]', defaultPaymentMethod);
    }
    fillInput('[name="installments"]', session.installments || 1);
}

function syncRecipientDefault(state) {
    const recipientInput = document.querySelector('[name="recipient_name"]');

    if (!recipientInput) {
        return;
    }

    const defaultRecipientName = (
        state.session?.recipient_name
        || state.session?.customer_name
        || state.identificationDraft?.customer_name
        || ''
    ).trim();

    if (!defaultRecipientName || recipientInput.value.trim()) {
        return;
    }

    fillInput('[name="recipient_name"]', defaultRecipientName);
}

function applyIdentificationDraftToForm(form, draft, personType) {
    if (!form) {
        return;
    }

    const mergedValues = {
        customer_name: draft.customer_name ?? '',
        customer_email: draft.customer_email ?? '',
        customer_phone: draft.customer_phone ?? '',
        customer_document: draft.customer_document ?? '',
        customer_birth_date: draft.customer_birth_date ?? '',
        customer_company_name: draft.customer_company_name ?? '',
        customer_state_registration: draft.customer_state_registration ?? '',
    };

    Object.entries(mergedValues).forEach(([field, value]) => {
        fillFormField(form, `[name="${field}"]`, value);
    });

    fillFormField(form, '[name="customer_document_type"]', personType === 'pj' ? 'cnpj' : 'cpf');
}

function applyIdentificationMask(form, target) {
    if (!(target instanceof HTMLInputElement) || !form?.contains(target)) {
        return;
    }

    if (target.name === 'customer_phone') {
        target.value = formatPhone(target.value);
        return;
    }

    if (target.name === 'customer_document') {
        target.value = formatIdentificationDocument(target.value, identificationDocumentType(form));
    }
}

function applyZipcodeMask(target) {
    if (!(target instanceof HTMLInputElement) || target.name !== 'zipcode') {
        return;
    }

    target.value = formatZipcode(target.value);
}

function applyAddressLookupState(form, data) {
    fillFormField(form, '[name="street"]', data.logradouro || '');
    fillFormField(form, '[name="neighborhood"]', data.bairro || '');
    fillFormField(form, '[name="city"]', data.localidade || '');
    fillFormField(form, '[name="state"]', data.uf || '');
}

function syncIdentificationDraftFromForm(state, form) {
    state.identificationDraft = {
        ...state.identificationDraft,
        ...collectFormValues(form),
    };
}

function getIdentificationForms() {
    return {
        pf: document.querySelector('[data-person-form="pf"]'),
        pj: document.querySelector('[data-person-form="pj"]'),
    };
}

function updatePersonTypeUi(state) {
    const { pf, pj } = getIdentificationForms();
    const switchTrack = document.querySelector('[data-person-switch-track]');
    const switchInput = document.querySelector('[data-person-type-switch]');
    const title = document.querySelector('[data-person-switch-title]');

    if (switchInput) {
        switchInput.checked = state.personType === 'pj';
    }

    if (switchTrack) {
        switchTrack.classList.toggle('is-pj', state.personType === 'pj');
    }

    if (title) {
        title.textContent = state.personType === 'pj' ? 'Pessoa Jurídica' : 'Pessoa Física';
    }

    if (pf) {
        pf.hidden = state.personType !== 'pf';
    }

    if (pj) {
        pj.hidden = state.personType !== 'pj';
    }

    applyIdentificationDraftToForm(pf, state.identificationDraft, 'pf');
    applyIdentificationDraftToForm(pj, state.identificationDraft, 'pj');
}

function updateSummary(config, state) {
    const session = state.session || config.session || {};
    const order = state.order || config.order || null;
    const paymentTransaction = state.paymentTransaction || config.paymentTransaction || null;

    const summaryMap = {
        '[data-summary-subtotal]': formatCurrency(session.subtotal),
        '[data-summary-discount]': formatCurrency(session.discount_total),
        '[data-summary-shipping]': formatCurrency(session.shipping_total),
        '[data-summary-total]': formatCurrency(session.total),
        '[data-summary-product]': config.product?.name || config.checkoutLink?.name || 'Produto',
        '[data-summary-quantity]': session.quantity || config.checkoutLink?.quantity || 1,
        '[data-summary-step]': stepLabels[state.visualStep] || stepLabels[session.status] || session.status || 'Identificação',
        '[data-summary-payment-method]': (order?.payment_method || session.payment_method)
            ? String(order?.payment_method || session.payment_method).toUpperCase()
            : 'Ainda não iniciado',
    };

    Object.entries(summaryMap).forEach(([selector, value]) => {
        const element = document.querySelector(selector);

        if (element) {
            element.textContent = value;
        }
    });

}

function setVisiblePanel(panelName) {
    document.querySelectorAll('[data-step-panel]').forEach((panel) => {
        panel.hidden = panel.dataset.stepPanel !== panelName;
    });
}

function updateStepper(state) {
    document.querySelectorAll('[data-step-button]').forEach((button) => {
        const step = button.dataset.stepButton;
        const isCurrent = step === state.currentStep;
        const isComplete = stepOrder.indexOf(step) < stepOrder.indexOf(state.currentStep);

        button.classList.toggle('is-active', isCurrent);
        button.classList.toggle('is-complete', isComplete);
    });
}

function updatePaymentDetails(state, transaction) {
    const method = transaction?.payment_method || state.session.payment_method || 'pix';
    const statusLabel = transaction?.internal_status || state.session.status || 'pending';
    const pixQrCodeImage = transaction?.response_payload?.pix_qr_code_image
        || transaction?.response_payload?.api_qrcode?.qrcode
        || null;

    const badge = document.querySelector('[data-payment-method-badge]');
    const statusText = document.querySelector('[data-payment-status-text]');
    const message = document.querySelector('[data-payment-message]');

    if (badge) {
        badge.textContent = method === 'credit_card' ? 'Cartão' : method === 'boleto' ? 'Boleto' : 'Pix';
    }

    if (statusText) {
        statusText.textContent = method === 'pix' ? statusLabel : '';
    }

    if (message) {
        if (method === 'pix') {
            message.textContent = 'Escaneie o código ou copie e cole o Pix. A página será atualizada automaticamente quando o pagamento for aprovado.';
        } else {
            message.textContent = 'O pagamento foi enviado para processamento. A página acompanha a confirmação automaticamente.';
        }
    }

    const qrBlock = document.querySelector('[data-pix-block]');
    if (qrBlock) {
        qrBlock.hidden = method !== 'pix';
    }

    const boletoBlock = document.querySelector('[data-boleto-block]');
    if (boletoBlock) {
        boletoBlock.hidden = method !== 'boleto';
    }

    const pixCodeElement = document.querySelector('[data-pix-code]');
    if (pixCodeElement) {
        pixCodeElement.textContent = transaction?.pix_copy_paste || transaction?.pix_qr_code || 'O código Pix será exibido assim que o pagamento for criado.';
    }

    const boletoUrlElement = document.querySelector('[data-boleto-url]');
    if (boletoUrlElement) {
        const boletoUrl = transaction?.boleto_url || '#';
        boletoUrlElement.href = boletoUrl;
        boletoUrlElement.textContent = boletoUrl === '#' ? 'Boleto ainda não disponível' : 'Abrir boleto';
    }

    const boletoBarcodeElement = document.querySelector('[data-boleto-barcode]');
    if (boletoBarcodeElement) {
        boletoBarcodeElement.textContent = transaction?.boleto_barcode || 'O código de barras será exibido assim que o pagamento for criado.';
    }

    const boletoDigitableLineElement = document.querySelector('[data-boleto-digitable-line]');
    if (boletoDigitableLineElement) {
        boletoDigitableLineElement.textContent = transaction?.boleto_digitable_line || 'A linha digitável será exibida assim que o pagamento for criado.';
    }

    const copyButton = document.querySelector('[data-copy-payment]');
    if (copyButton) {
        if (method === 'pix') {
            copyButton.textContent = 'COPIAR CÓDIGO PIX';
        } else if (method === 'boleto') {
            copyButton.textContent = 'COPIAR LINHA DIGITÁVEL';
        } else {
            copyButton.textContent = 'COPIAR REFERÊNCIA';
        }
    }

    const thankYouLink = document.querySelector('[data-thank-you-link]');
    if (thankYouLink) {
        thankYouLink.href = state.thankYouUrl;
    }

    renderPixQrCode({
        value: transaction?.pix_copy_paste || transaction?.pix_qr_code,
        image: pixQrCodeImage,
    }, method);
    updatePixExpiration(transaction);
}
function renderPixQrCode(payload, method) {
    const container = document.querySelector('[data-pix-qr]');
    const placeholder = document.querySelector('[data-pix-qr-placeholder]');

    if (!container || !placeholder) {
        return;
    }

    if (!window.__checkoutPixQrRoot) {
        window.__checkoutPixQrRoot = createRoot(container);
    }

    if (method !== 'pix') {
        window.__checkoutPixQrRoot.render(null);
        placeholder.hidden = true;
        return;
    }

    if (payload?.image) {
        placeholder.hidden = true;

        window.__checkoutPixQrRoot.render(
            React.createElement('img', {
                src: payload.image,
                alt: 'QR Code Pix',
                style: {
                    width: '240px',
                    height: '240px',
                    objectFit: 'contain',
                },
            }),
        );

        return;
    }

    if (!payload?.value) {
        window.__checkoutPixQrRoot.render(null);
        placeholder.hidden = false;
        placeholder.textContent = 'O QR Code do Pix será exibido assim que o pagamento for criado.';
        return;
    }

    placeholder.hidden = true;

    window.__checkoutPixQrRoot.render(
        React.createElement(QRCodeSVG, {
            value: payload.value,
            size: 240,
            level: 'M',
            includeMargin: true,
            marginSize: 2,
            title: 'QR Code Pix',
            bgColor: '#ffffff',
            fgColor: '#111111',
        }),
    );
}

function updatePixExpiration(transaction) {
    const expirationElement = document.querySelector('[data-pix-expiration]');
    if (!expirationElement) {
        return;
    }

    if (!transaction?.pix_expires_at) {
        expirationElement.textContent = '--';
        return;
    }

    const expiresAt = new Date(transaction.pix_expires_at);

    const tick = () => {
        const diff = expiresAt.getTime() - Date.now();

        if (diff <= 0) {
            expirationElement.textContent = 'Expirado';
            return;
        }

        const totalSeconds = Math.floor(diff / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        if (hours > 0) {
            expirationElement.textContent = `${hours}h ${String(minutes).padStart(2, '0')}m`;
            return;
        }

        expirationElement.textContent = `${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`;
    };

    tick();

    if (window.__checkoutPixTimer) {
        clearInterval(window.__checkoutPixTimer);
    }

    window.__checkoutPixTimer = window.setInterval(tick, 1000);
}

function showWaitingPanel(state, transaction) {
    setVisiblePanel('waiting');
    state.visualStep = 'waiting';
    updateStepper(state);
    updatePaymentDetails(state, transaction);
}

function showStep(state, stepName) {
    state.currentStep = stepName;
    state.visualStep = stepName;

    setVisiblePanel(stepName);
    updateStepper(state);
    updateSummary(state.config, state);
}

function redirectToThankYou(state) {
    if (window.__checkoutPixTimer) {
        clearInterval(window.__checkoutPixTimer);
        window.__checkoutPixTimer = null;
    }

    window.location.assign(state.thankYouUrl);
}

function isPaidOrder(order, paymentTransaction, session) {
    return String(order?.status || '').toLowerCase() === 'paid'
        || String(paymentTransaction?.internal_status || '').toLowerCase() === 'paid'
        || String(session?.status || '').toLowerCase() === 'paid';
}

async function refreshStatus(state) {
    if (!state.config.urls.status) {
        return;
    }

    try {
        const payload = await requestJson(state.config.urls.status, {
            method: 'GET',
        });

        state.session = payload.checkout_session || state.session;
        state.order = payload.order || state.order;
        state.paymentTransaction = payload.payment_transaction || state.paymentTransaction;

        hydrateSession(state.session, state.config);
        syncRecipientDefault(state);
        updateSummary(state.config, state);

        if (isPaidOrder(state.order, state.paymentTransaction, state.session)) {
            redirectToThankYou(state);
            return;
        }

        if (state.currentStep === 'payment' && state.paymentTransaction?.payment_method === 'pix') {
            showWaitingPanel(state, state.paymentTransaction);
        }
    } catch (error) {
        setFeedback(error.payload?.message || error.message || 'Falha ao atualizar o pagamento.', 'error');
    }
}

function refreshPaymentState(state) {
    if (state.paymentRefreshInFlight) {
        return state.paymentRefreshInFlight;
    }

    state.paymentRefreshInFlight = refreshStatus(state).finally(() => {
        state.paymentRefreshInFlight = null;
    });

    return state.paymentRefreshInFlight;
}

function copyText(value) {
    if (!value) {
        return Promise.reject(new Error('Nenhum código disponível para copiar.'));
    }

    if (navigator.clipboard?.writeText) {
        return navigator.clipboard.writeText(value);
    }

    return new Promise((resolve, reject) => {
        const textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            const successful = document.execCommand('copy');
            document.body.removeChild(textarea);
            if (successful) {
                resolve();
            } else {
                reject(new Error('Não foi possível copiar o código.'));
            }
        } catch (error) {
            document.body.removeChild(textarea);
            reject(error);
        }
    });
}

function bindNavigation(state) {
    document.querySelectorAll('[data-back-to]').forEach((button) => {
        button.addEventListener('click', () => {
            showStep(state, button.dataset.backTo || 'identification');
        });
    });

    document.querySelectorAll('[data-step-button]').forEach((button) => {
        button.addEventListener('click', () => {
            const step = button.dataset.stepButton || 'identification';

            if (stepOrder.indexOf(step) <= stepOrder.indexOf(state.currentStep)) {
                showStep(state, step);
            }
        });
    });
}

function bindForms(state) {
    const identificationForms = Array.from(document.querySelectorAll('[data-checkout-form="identification"]'));
    const deliveryForm = document.getElementById('checkout-delivery-form');
    const paymentForm = document.getElementById('checkout-payment-form');
    let zipcodeLookupTimer = null;
    let zipcodeLookupRequestId = 0;

    identificationForms.forEach((identificationForm) => {
        identificationForm.addEventListener('input', (event) => {
            applyIdentificationMask(identificationForm, event.target);
            syncIdentificationDraftFromForm(state, identificationForm);
        });

        identificationForm.addEventListener('change', () => {
            syncIdentificationDraftFromForm(state, identificationForm);
        });

        identificationForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearFeedback();
            clearFieldErrors();
            setBusy(identificationForm, true, 'Salvando...');

            try {
                const payload = await requestJson(state.config.urls.identification, {
                    method: 'POST',
                    body: new FormData(identificationForm),
                });

                state.session = payload.checkout_session || state.session;
                state.identificationDraft = {
                    ...state.identificationDraft,
                    ...collectFormValues(identificationForm),
                    customer_document_type: identificationDocumentType(identificationForm),
                };
                hydrateSession(state.session, state.config);
                syncRecipientDefault(state);
                updateSummary(state.config, state);
                showStep(state, 'delivery');
                setFeedback('Identificação salva com sucesso.', 'success');
            } catch (error) {
                if (error.status === 422 && error.payload?.errors) {
                    setFieldErrors(error.payload.errors);
                    setFeedback('Revise os campos destacados antes de continuar.', 'error');
                } else {
                    setFeedback(error.payload?.message || error.message || 'Não foi possível salvar a identificação.', 'error');
                }
            } finally {
                setBusy(identificationForm, false);
            }
        });
    });

    if (deliveryForm) {
        const zipcodeInput = deliveryForm.querySelector('[name="zipcode"]');

        const clearAddressFields = () => {
            fillFormField(deliveryForm, '[name="street"]', '');
            fillFormField(deliveryForm, '[name="neighborhood"]', '');
            fillFormField(deliveryForm, '[name="city"]', '');
            fillFormField(deliveryForm, '[name="state"]', '');
        };

        const lookupAddress = async () => {
            const zipcode = zipcodeInput?.value || '';

            if (!isValidZipcode(zipcode)) {
                clearAddressFields();
                return;
            }

            const requestId = ++zipcodeLookupRequestId;

            try {
                const address = await lookupAddressByZipcode(zipcode);

                if (requestId !== zipcodeLookupRequestId) {
                    return;
                }

                applyAddressLookupState(deliveryForm, address);
                setFieldErrors({ zipcode: [] });
            } catch (error) {
                if (requestId !== zipcodeLookupRequestId) {
                    return;
                }

                setFieldErrors({ zipcode: [error.message || 'Não foi possível consultar o CEP.'] });
            }
        };

        const scheduleLookup = () => {
            if (zipcodeLookupTimer) {
                clearTimeout(zipcodeLookupTimer);
            }

            zipcodeLookupTimer = window.setTimeout(() => {
                void lookupAddress();
            }, 350);
        };

        zipcodeInput?.addEventListener('input', (event) => {
            applyZipcodeMask(event.target);

            const zipcodeError = deliveryForm.querySelector('[data-error-for="zipcode"]');
            if (zipcodeError) {
                zipcodeError.textContent = '';
            }

            if (!isValidZipcode(zipcodeInput.value)) {
                clearAddressFields();
            }

            scheduleLookup();
        });

        zipcodeInput?.addEventListener('blur', () => {
            applyZipcodeMask(zipcodeInput);
            void lookupAddress();
        });
    }

    const personTypeSwitch = document.querySelector('[data-person-type-switch]');

    if (personTypeSwitch) {
        personTypeSwitch.addEventListener('change', () => {
            state.personType = personTypeSwitch.checked ? 'pj' : 'pf';
            updatePersonTypeUi(state);
        });
    }

    if (deliveryForm) {
        deliveryForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearFeedback();
            clearFieldErrors();
            setBusy(deliveryForm, true, 'Salvando...');

            try {
                const payload = await requestJson(state.config.urls.delivery, {
                    method: 'POST',
                    body: new FormData(deliveryForm),
                });

                state.session = payload.checkout_session || state.session;
                hydrateSession(state.session, state.config);
                syncRecipientDefault(state);
                updateSummary(state.config, state);
                showStep(state, 'payment');
                setFeedback('Entrega salva com sucesso.', 'success');
            } catch (error) {
                if (error.status === 422 && error.payload?.errors) {
                    setFieldErrors(error.payload.errors);
                    setFeedback('Revise os campos destacados antes de continuar.', 'error');
                } else {
                    setFeedback(error.payload?.message || error.message || 'Não foi possível salvar a entrega.', 'error');
                }
            } finally {
                setBusy(deliveryForm, false);
            }
        });
    }

    if (paymentForm) {
        paymentForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearFeedback();
            clearFieldErrors();
            setBusy(paymentForm, true, 'Gerando pagamento...');

            try {
                const payload = await requestJson(state.config.urls.payment, {
                    method: 'POST',
                    body: new FormData(paymentForm),
                });

                state.order = payload.order || state.order;
                state.paymentTransaction = payload.payment_transaction || state.paymentTransaction;
                state.session = payload.checkout_session || state.session;
                state.thankYouUrl = payload.thank_you_url || state.thankYouUrl;

                hydrateSession(state.session, state.config);
                syncRecipientDefault(state);
                updateSummary(state.config, state);
                updatePaymentDetails(state, state.paymentTransaction);

                if (state.paymentTransaction?.payment_method === 'pix') {
                    showWaitingPanel(state, state.paymentTransaction);
                } else {
                    showStep(state, 'payment');
                }
                setFeedback('Pagamento iniciado com sucesso. A confirmação será atualizada via webhook.', 'success');

                void refreshPaymentState(state);
            } catch (error) {
                if (error.status === 422 && error.payload?.errors) {
                    setFieldErrors(error.payload.errors);
                    setFeedback('Revise os campos destacados antes de continuar.', 'error');
                } else {
                    setFeedback(error.payload?.message || error.message || 'Não foi possível iniciar o pagamento.', 'error');
                }
            } finally {
                setBusy(paymentForm, false);
            }
        });
    }
}

function bindPixCopy(state) {
    const copyButton = document.querySelector('[data-copy-payment]');

    if (!copyButton) {
        return;
    }

    copyButton.addEventListener('click', async () => {
        const method = state.paymentTransaction?.payment_method || state.session.payment_method;
        const paymentCode = method === 'boleto'
            ? (state.paymentTransaction?.boleto_digitable_line || state.paymentTransaction?.boleto_barcode || state.paymentTransaction?.boleto_url)
            : (state.paymentTransaction?.pix_copy_paste || state.paymentTransaction?.pix_qr_code);

        try {
            await copyText(paymentCode);
            setFeedback(method === 'boleto'
                ? 'Linha digitável copiada para a área de transferência.'
                : 'Código Pix copiado para a área de transferência.', 'success');
        } catch (error) {
            setFeedback(error.message || 'Não foi possível copiar o código.', 'error');
        }
    });
}

function resumeExistingPayment(state) {
    if (!state.paymentTransaction) {
        return;
    }

    const method = state.paymentTransaction.payment_method || state.session.payment_method;

    if (isPaidOrder(state.order, state.paymentTransaction, state.session)) {
        redirectToThankYou(state);
        return;
    }

    if (method === 'pix' && state.paymentTransaction.internal_status) {
        showWaitingPanel(state, state.paymentTransaction);
    } else {
        showStep(state, 'payment');
        updatePaymentDetails(state, state.paymentTransaction);
    }

    if (method) {
        fillInput('[name="payment_method"]', method);
    }

    void refreshPaymentState(state);
}

function bindPaymentStateRefresh(state) {
    const refreshOnFocus = () => {
        if (document.hidden) {
            return;
        }

        if (!state.paymentTransaction && state.currentStep !== 'payment') {
            return;
        }

        void refreshPaymentState(state);
    };

    window.addEventListener('focus', refreshOnFocus);
    window.addEventListener('pageshow', refreshOnFocus);
    document.addEventListener('visibilitychange', refreshOnFocus);
}

function initCheckoutPublic() {
    const config = readConfig();

    if (!config) {
        return;
    }

    const root = document.getElementById('checkout-public-app');

    if (!root) {
        return;
    }

    const state = {
        config,
        session: config.session || {},
        order: config.order || null,
        paymentTransaction: config.paymentTransaction || null,
        currentStep: config.currentStep || 'identification',
        visualStep: config.currentStep || 'identification',
        thankYouUrl: config.thankYouUrl,
        personType: String(config.session?.customer_document_type || 'cpf').toLowerCase() === 'cnpj' ? 'pj' : 'pf',
        identificationDraft: {
            customer_name: config.session?.customer_name || '',
            customer_email: config.session?.customer_email || '',
            customer_phone: config.session?.customer_phone || '',
            customer_document: config.session?.customer_document || '',
            customer_birth_date: config.session?.customer_birth_date || '',
            customer_company_name: config.session?.customer_company_name || '',
            customer_state_registration: config.session?.customer_state_registration || '',
        },
    };

    hydrateSession(state.session, state.config);
    syncRecipientDefault(state);
    updateSummary(config, state);
    bindNavigation(state);
    bindForms(state);
    bindPixCopy(state);
    bindPaymentStateRefresh(state);
    updatePersonTypeUi(state);

    if (isPaidOrder(state.order, state.paymentTransaction, state.session)) {
        redirectToThankYou(state);
        return;
    }

    if (state.paymentTransaction) {
        resumeExistingPayment(state);
        return;
    }

    showStep(state, state.currentStep);

    if (state.currentStep === 'delivery') {
        setVisiblePanel('delivery');
    }

    if (state.currentStep === 'payment') {
        setVisiblePanel('payment');
    }
}

document.addEventListener('DOMContentLoaded', initCheckoutPublic);



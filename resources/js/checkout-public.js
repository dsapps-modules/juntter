import React from 'react';
import { createRoot } from 'react-dom/client';
import { QRCodeSVG } from '@rc-component/qrcode';

const cnpjCompanyLookupCache = new Map();

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

function roundCurrency(value) {
    return Math.round((Number(value) || 0) * 100) / 100;
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

function isValidCpf(value) {
    const cpf = normalizeDigits(value);

    if (cpf.length !== 11) {
        return false;
    }

    if (/^(\d)\1{10}$/.test(cpf)) {
        return false;
    }

    for (let digitPosition = 9; digitPosition < 11; digitPosition += 1) {
        let sum = 0;

        for (let index = 0; index < digitPosition; index += 1) {
            sum += Number(cpf[index]) * ((digitPosition + 1) - index);
        }

        const calculatedDigit = ((sum * 10) % 11) % 10;

        if (Number(cpf[digitPosition]) !== calculatedDigit) {
            return false;
        }
    }

    return true;
}

function isValidCnpj(value) {
    const cnpj = normalizeDigits(value);

    if (cnpj.length !== 14) {
        return false;
    }

    if (/^(\d)\1{13}$/.test(cnpj)) {
        return false;
    }

    const digits = cnpj.split('').map((digit) => Number(digit));
    const weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    const weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    let sum = 0;
    for (let index = 0; index < 12; index += 1) {
        sum += digits[index] * weights1[index];
    }

    let remainder = sum % 11;
    let firstDigit = remainder < 2 ? 0 : 11 - remainder;

    if (digits[12] !== firstDigit) {
        return false;
    }

    sum = 0;
    for (let index = 0; index < 13; index += 1) {
        sum += digits[index] * weights2[index];
    }

    remainder = sum % 11;
    const secondDigit = remainder < 2 ? 0 : 11 - remainder;

    return digits[13] === secondDigit;
}

function isValidDocument(value) {
    const digits = normalizeDigits(value);

    if (digits.length === 11) {
        return isValidCpf(digits);
    }

    if (digits.length === 14) {
        return isValidCnpj(digits);
    }

    return false;
}

function formatIdentificationDocument(value, documentType) {
    return documentType === 'cnpj' ? formatCnpj(value) : formatCpf(value);
}

function formatDocument(value) {
    const digits = normalizeDigits(value);

    return digits.length > 11 ? formatCnpj(digits) : formatCpf(digits);
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
    const { headers: customHeaders = {}, ...fetchOptions } = options;

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...fetchOptions,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrfToken(),
            ...customHeaders,
        },
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

    if (fieldName === 'card[holder_document]') {
        element.value = formatDocument(value);
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

    if (fieldName === 'card[holder_document]') {
        element.value = formatDocument(value);
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

    fillInput('[name="quantity"]', session.quantity || config?.checkoutLink?.quantity || 1);
    fillInput('[name="zipcode"]', session.zipcode);
    fillInput('[name="street"]', session.street);
    fillInput('[name="number"]', session.number);
    fillInput('[name="complement"]', session.complement);
    fillInput('[name="neighborhood"]', session.neighborhood);
    fillInput('[name="city"]', session.city);
    fillInput('[name="state"]', session.state);
    fillInput('[name="recipient_name"]', session.recipient_name);
    fillInput('[name="card[holder_name]"]', session.customer_name);
    fillInput('[name="card[holder_document]"]', session.customer_document);
    const defaultPaymentMethod = session.payment_method
        || (config?.checkoutLink?.allowPix ? 'pix' : (config?.checkoutLink?.allowBoleto ? 'boleto' : (config?.checkoutLink?.allowCreditCard ? 'credit_card' : '')));

    if (defaultPaymentMethod) {
        fillInput('[name="payment_method"]', defaultPaymentMethod);
    }
    fillInput('[name="installments"]', session.installments || 1);
}

function updateInstallmentsVisibility(paymentForm, paymentMethod) {
    if (!paymentForm) {
        return;
    }

    const installmentsWrapper = paymentForm.querySelector('[data-installments-wrapper]');
    const installmentsInput = paymentForm.querySelector('[name="installments"]');

    if (!installmentsWrapper || !installmentsInput) {
        return;
    }

    const shouldShowInstallments = paymentMethod === 'credit_card';

    installmentsWrapper.hidden = !shouldShowInstallments;
    installmentsInput.disabled = !shouldShowInstallments;
    installmentsInput.required = shouldShowInstallments;

    if (shouldShowInstallments && (!installmentsInput.value || Number(installmentsInput.value) < 1)) {
        installmentsInput.value = '1';
    }
}

function updateCreditCardFieldsVisibility(paymentForm, paymentMethod) {
    if (!paymentForm) {
        return;
    }

    const cardFieldsWrapper = paymentForm.querySelector('[data-card-fields-wrapper]');

    if (!cardFieldsWrapper) {
        return;
    }

    const shouldShowCardFields = paymentMethod === 'credit_card';

    cardFieldsWrapper.hidden = !shouldShowCardFields;
    cardFieldsWrapper.querySelectorAll('input, select, textarea').forEach((element) => {
        element.disabled = !shouldShowCardFields;
    });
}

function getAntifraudAuthUrl(config, transactionId) {
    const template = String(config?.urls?.antifraudAuthTemplate || '');

    if (!template || !transactionId) {
        return '';
    }

    return template.replace('__TRANSACTION_ID__', encodeURIComponent(transactionId));
}

function buildCreditCard3DSRequest(state, paymentForm) {
    const session = state.session || {};
    const customerName = String(session.customer_name || '').trim() || 'Cliente';
    const customerEmail = String(session.customer_email || '').trim();
    const customerPhoneDigits = normalizeDigits(session.customer_phone || '');
    const holderName = String(paymentForm?.querySelector('[name="card[holder_name]"]')?.value || customerName).trim() || customerName;
    const cardNumber = normalizeDigits(paymentForm?.querySelector('[name="card[card_number]"]')?.value || '');
    const expirationMonth = String(paymentForm?.querySelector('[name="card[expiration_month]"]')?.value || '').padStart(2, '0');
    const expirationYear = String(paymentForm?.querySelector('[name="card[expiration_year]"]')?.value || '');
    const installments = Math.max(1, Number(paymentForm?.querySelector('[name="installments"]')?.value || 1));
    const amount = Math.round(Number(session.total || state.order?.total || 0) * 100);

    return {
        data: {
            customer: {
                name: customerName,
                email: customerEmail,
                phones: [
                    {
                        country: '55',
                        area: customerPhoneDigits.slice(0, 2) || '00',
                        number: customerPhoneDigits.slice(2) || customerPhoneDigits || '000000000',
                        type: 'MOBILE',
                    },
                ],
            },
            paymentMethod: {
                type: 'CREDIT_CARD',
                installments,
                card: {
                    number: cardNumber,
                    expMonth: expirationMonth,
                    expYear: expirationYear,
                    holder: {
                        name: holderName,
                    },
                },
            },
            amount: {
                value: amount,
                currency: 'BRL',
            },
            billingAddress: {
                street: String(session.street || ''),
                number: String(session.number || ''),
                complement: String(session.complement || ''),
                regionCode: String(session.state || ''),
                country: 'BRA',
                city: String(session.city || ''),
                postalCode: normalizeDigits(session.zipcode || ''),
            },
            shippingAddress: {
                street: String(session.street || ''),
                number: String(session.number || ''),
                complement: String(session.complement || ''),
                regionCode: String(session.state || ''),
                country: 'BRA',
                city: String(session.city || ''),
                postalCode: normalizeDigits(session.zipcode || ''),
            },
            dataOnly: false,
        },
    };
}

async function confirmCreditCard3DS(state, paymentForm, transaction) {
    const sessionId = transaction?.three_ds_session_id
        || transaction?.session_id
        || transaction?.response_payload?.session_id
        || state.paymentTransaction?.three_ds_session_id
        || state.paymentTransaction?.response_payload?.session_id;
    const gatewayTransactionId = transaction?.gateway_transaction_id
        || state.paymentTransaction?.gateway_transaction_id;
    const authUrl = getAntifraudAuthUrl(state.config, gatewayTransactionId);

    if (!sessionId) {
        throw new Error('A sessão 3DS não foi informada pelo gateway.');
    }

    if (!gatewayTransactionId || !authUrl) {
        throw new Error('Não foi possível preparar a confirmação 3DS.');
    }

    if (!window.PagSeguro?.setUp || !window.PagSeguro?.authenticate3DS) {
        throw new Error('O SDK 3DS não está disponível.');
    }

    window.PagSeguro.setUp({
        session: sessionId,
        env: state.threeDsEnv || 'PROD',
    });

    const request = buildCreditCard3DSRequest(state, paymentForm);
    const result = await window.PagSeguro.authenticate3DS(request);

    return requestJson(authUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: result.id,
            status: result.status,
            authentication_status: result.authentication_status || 'NOT_AUTHENTICATED',
        }),
    });
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
        customer_responsible_document: draft.customer_responsible_document ?? '',
        customer_responsible_birth_date: draft.customer_responsible_birth_date ?? '',
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
        return;
    }

    if (target.name === 'customer_responsible_document') {
        target.value = formatCpf(target.value);
    }
}

function applyPaymentMask(target) {
    if (!(target instanceof HTMLInputElement)) {
        return;
    }

    if (target.name === 'card[holder_document]') {
        target.value = formatDocument(target.value);
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

function validateIdentificationForm(form) {
    return validateIdentificationDocument(form, { focusOnError: true });
}

function validateResponsibleCpf(form, { focusOnError = false } = {}) {
    const documentField = form?.querySelector('[name="customer_responsible_document"]');

    if (!isValidCpf(documentField?.value || '')) {
        setFieldErrors({
            customer_responsible_document: ['CPF inválido.'],
        });

        if (focusOnError && documentField instanceof HTMLInputElement) {
            documentField.focus();
        }

        return false;
    }

    setFieldErrors({
        customer_responsible_document: [],
    });

    return true;
}

function validateIdentificationDocument(form, { focusOnError = false } = {}) {
    const documentType = identificationDocumentType(form);
    const documentField = form?.querySelector('[name="customer_document"]');

    if (documentType === 'cpf' && !isValidCpf(documentField?.value || '')) {
        setFieldErrors({
            customer_document: ['CPF inválido.'],
        });
        setFeedback('Informe um CPF válido antes de continuar.', 'error');

        if (focusOnError && documentField instanceof HTMLInputElement) {
            documentField.focus();
        }

        return false;
    }

    if (documentType === 'cnpj' && !isValidCnpj(documentField?.value || '')) {
        setFieldErrors({
            customer_document: ['CNPJ inválido.'],
        });
        setFeedback('Informe um CNPJ válido antes de continuar.', 'error');

        if (focusOnError && documentField instanceof HTMLInputElement) {
            documentField.focus();
        }

        return false;
    }

    setFieldErrors({
        customer_document: [],
    });
    clearFeedback();

    return true;
}

async function lookupCompanyDataByCnpj(state, form) {
    const documentField = form?.querySelector('[name="customer_document"]');
    const cnpjDigits = normalizeDigits(documentField?.value || '');

    if (cnpjDigits.length !== 14) {
        return false;
    }

    const cachedData = cnpjCompanyLookupCache.get(cnpjDigits);

    if (cachedData) {
        applyCompanyLookupToForm(state, form, cachedData);
        return true;
    }

    const lookupTemplate = String(state?.config?.urls?.cnpjLookupTemplate || '');

    if (!lookupTemplate) {
        return false;
    }

    try {
        const payload = await requestJson(lookupTemplate.replace('__CNPJ__', cnpjDigits), {
            method: 'GET',
        });
        if (!payload?.company_name) {
            return false;
        }

        cnpjCompanyLookupCache.set(cnpjDigits, payload);
        applyCompanyLookupToForm(state, form, payload);
        return true;
    } catch (error) {
        return false;
    }
}

function applyCompanyLookupToForm(state, form, payload) {
    const mappings = {
        customer_company_name: payload?.company_name || '',
        customer_email: payload?.email || '',
        customer_phone: payload?.phone || '',
        customer_name: payload?.responsible_name || '',
        customer_responsible_document: payload?.responsible_document || '',
    };

    Object.entries(mappings).forEach(([field, value]) => {
        if (value) {
            fillFormField(form, `[name="${field}"]`, value);
        }
    });

    syncIdentificationDraftFromForm(state, form);
    setFieldErrors({
        customer_company_name: [],
        customer_email: [],
        customer_phone: [],
        customer_name: [],
        customer_responsible_document: [],
    });
}

function validatePaymentForm(paymentForm) {
    const paymentMethod = String(paymentForm?.querySelector('[name="payment_method"]')?.value || '');
    const paymentMethodField = paymentForm?.querySelector('[name="payment_method"]');

    if (!paymentMethod) {
        setFieldErrors({
            payment_method: ['Selecione um método de pagamento.'],
        });
        setFeedback('Selecione um método de pagamento antes de continuar.', 'error');

        if (paymentMethodField instanceof HTMLSelectElement) {
            paymentMethodField.focus();
        }

        return false;
    }

    if (paymentMethod !== 'credit_card') {
        return true;
    }

    const cardFields = [
        { errorKey: 'card.holder_name', name: 'card[holder_name]', message: 'Preencha o nome no cartão.' },
        { errorKey: 'card.holder_document', name: 'card[holder_document]', message: 'Preencha o documento do titular.' },
        { errorKey: 'card.card_number', name: 'card[card_number]', message: 'Preencha o número do cartão.' },
        { errorKey: 'card.expiration_month', name: 'card[expiration_month]', message: 'Preencha o mês de validade.' },
        { errorKey: 'card.expiration_year', name: 'card[expiration_year]', message: 'Preencha o ano de validade.' },
        { errorKey: 'card.security_code', name: 'card[security_code]', message: 'Preencha o CVV.' },
        { errorKey: 'installments', name: 'installments', message: 'Informe o número de parcelas.' },
    ];

    const fieldErrors = {};
    let firstInvalidField = null;

    cardFields.forEach(({ errorKey, name, message }) => {
        const field = paymentForm?.querySelector(`[name="${name}"]`);

        if (!(field instanceof HTMLInputElement)) {
            return;
        }

        const value = String(field.value || '').trim();

        if (value) {
            if (name === 'card[holder_document]') {
                const isValid = isValidDocument(value);
                if (!isValid) {
                    fieldErrors[errorKey] = ['Informe um CPF/CNPJ válido.'];
                    firstInvalidField ||= field;
                }
            }

            if (name === 'installments' && Number(value) < 1) {
                fieldErrors[errorKey] = ['Informe ao menos 1 parcela.'];
                firstInvalidField ||= field;
            }

            return;
        }

        fieldErrors[errorKey] = [message];
        firstInvalidField ||= field;
    });

    if (Object.keys(fieldErrors).length > 0) {
        setFieldErrors(fieldErrors);
        setFeedback('Revise os campos destacados antes de continuar.', 'error');

        if (firstInvalidField instanceof HTMLInputElement) {
            firstInvalidField.focus();
        }

        return false;
    }

    return true;
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
    const title = document.querySelector('[data-person-type-title]');
    if (switchInput) {
        switchInput.checked = state.personType === 'pj';
    }

    if (switchTrack) {
        switchTrack.classList.toggle('is-pj', state.personType === 'pj');
    }

    if (title) {
        title.textContent = state.personType === 'pj' ? 'Dados da empresa' : 'Dados pessoais';
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

function syncPersonTypeFromSwitch(state, switchInput) {
    if (!(switchInput instanceof HTMLInputElement)) {
        return;
    }

    const nextPersonType = switchInput.checked ? 'pj' : 'pf';

    if (state.personType === nextPersonType) {
        return;
    }

    state.personType = nextPersonType;
    updatePersonTypeUi(state);
}

function schedulePersonTypeSync(state, switchInput) {
    window.setTimeout(() => {
        syncPersonTypeFromSwitch(state, switchInput);
    }, 0);
}

function normalizeCheckoutQuantity(value) {
    const quantity = Number.parseInt(String(value ?? '').trim(), 10);

    return Number.isFinite(quantity) && quantity >= 1 ? quantity : 1;
}

function setSummaryFieldValue(selector, value) {
    const element = document.querySelector(selector);

    if (!element) {
        return;
    }

    if (element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement || element instanceof HTMLSelectElement) {
        element.value = String(value);
        return;
    }

    element.textContent = String(value);
}

function syncQuantityControlState(state, config) {
    const quantityInput = document.querySelector('[data-summary-quantity-input]');
    const decrementButton = document.querySelector('[data-summary-quantity-decrement]');
    const incrementButton = document.querySelector('[data-summary-quantity-increment]');
    const disabled = Boolean(state.order || state.paymentTransaction);

    if (!quantityInput) {
        return;
    }

    quantityInput.min = '1';
    quantityInput.step = '1';
    quantityInput.value = String(normalizeCheckoutQuantity(state.session?.quantity || config.checkoutLink?.quantity || 1));
    quantityInput.disabled = disabled;

    if (decrementButton) {
        decrementButton.disabled = disabled;
    }

    if (incrementButton) {
        incrementButton.disabled = disabled;
    }
}

function updateSummary(config, state) {
    const session = state.session || config.session || {};
    syncQuantityControlState(state, config);

    setSummaryFieldValue('[data-summary-subtotal]', formatCurrency(session.subtotal));
    setSummaryFieldValue('[data-summary-discount]', formatCurrency(session.discount_total));
    setSummaryFieldValue('[data-summary-shipping]', formatCurrency(session.shipping_total));
    setSummaryFieldValue('[data-summary-total]', formatCurrency(session.total));
    setSummaryFieldValue('[data-summary-product]', config.product?.name || config.checkoutLink?.name || 'Produto');
}


function updatePaymentDetails(state, transaction) {
    const method = transaction?.payment_method || state.session.payment_method || 'pix';
    const rawStatus = transaction?.internal_status || state.session.status || 'pending';
    const statusLabel = String(rawStatus || '').toLowerCase() === 'pending' ? 'Pendente' : rawStatus;
    const pixQrCodeImage = transaction?.response_payload?.pix_qr_code_image
        || transaction?.response_payload?.api_qrcode?.qrcode
        || null;
    const showBoletoLoading = method === 'boleto'
        && Boolean(state.boletoLoading)
        && !hasCompleteBoletoDetails(transaction)
        && !hasFailedBoletoDetails(transaction);

    const badge = document.querySelector('[data-payment-method-badge]');
    const statusText = document.querySelector('[data-payment-status-text]');
    const message = document.querySelector('[data-payment-message]');

    if (badge) {
        badge.textContent = method === 'credit_card' ? 'Cartão' : method === 'boleto' ? 'Boleto' : 'Pix';
    }

    if (statusText) {
        statusText.textContent = statusLabel;
    }

    if (message) {
        if (method === 'pix') {
            message.textContent = 'Escaneie o código ou copie e cole o Pix.';
        } else if (method === 'credit_card') {
            message.textContent = 'Pagamento em processamento';
        } else if (showBoletoLoading) {
            message.textContent = 'Estamos gerando o boleto. Aguarde alguns segundos para ver os dados e abrir o documento.';
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

    const boletoLoadingBlock = document.querySelector('[data-boleto-loading]');
    const boletoGrid = document.querySelector('[data-boleto-grid]');

    if (boletoLoadingBlock) {
        boletoLoadingBlock.hidden = !showBoletoLoading;
    }

    if (boletoGrid) {
        boletoGrid.hidden = showBoletoLoading;
    }

    const pixCodeElement = document.querySelector('[data-pix-code]');
    if (pixCodeElement) {
        pixCodeElement.textContent = transaction?.pix_copy_paste || transaction?.pix_qr_code || 'O código Pix será exibido assim que o pagamento for criado.';
    }

    const boletoUrlElement = document.querySelector('[data-boleto-url]');
    if (boletoUrlElement) {
        const boletoUrl = transaction?.boleto_url || '#';
        boletoUrlElement.href = boletoUrl;
        boletoUrlElement.target = '_blank';
        boletoUrlElement.rel = 'noreferrer';
        if (transaction?.internal_status === 'failed') {
            boletoUrlElement.textContent = 'Falha ao gerar boleto';
        } else {
            boletoUrlElement.textContent = boletoUrl === '#' ? 'Boleto ainda não disponível' : 'Abrir boleto';
        }
    }

    const boletoDigitableLine = transaction?.boleto_digitable_line || 'A linha digitável será exibida assim que o pagamento for criado.';
    const boletoDigitableLineElement = document.querySelector('[data-boleto-digitable-line]');
    if (boletoDigitableLineElement) {
        boletoDigitableLineElement.textContent = boletoDigitableLine;
    }

    const boletoBarcode = transaction?.boleto_barcode || 'O código de barras será exibido assim que o pagamento for criado.';
    const boletoBarcodeElement = document.querySelector('[data-boleto-barcode]');
    if (boletoBarcodeElement) {
        boletoBarcodeElement.textContent = boletoBarcode;
    }

    const boletoPixCopyPaste = transaction?.pix_copy_paste || transaction?.pix_qr_code || 'O Pix copia e cola será exibido assim que o pagamento for criado.';
    const boletoPixCopyPasteElement = document.querySelector('[data-boleto-pix-copy-paste]');
    if (boletoPixCopyPasteElement) {
        boletoPixCopyPasteElement.textContent = boletoPixCopyPaste;
    }

    const copyDigitableLineButton = document.querySelector('[data-copy-boleto-digitable-line]');
    if (copyDigitableLineButton) {
        copyDigitableLineButton.disabled = !Boolean(transaction?.boleto_digitable_line);
    }

    const copyBarcodeButton = document.querySelector('[data-copy-boleto-barcode]');
    if (copyBarcodeButton) {
        copyBarcodeButton.disabled = !Boolean(transaction?.boleto_barcode);
    }

    const copyPixButton = document.querySelector('[data-copy-boleto-pix-copy-paste]');
    if (copyPixButton) {
        copyPixButton.disabled = !Boolean(transaction?.pix_copy_paste || transaction?.pix_qr_code);
    }

    const paymentButton = document.querySelector('[data-open-payment]');
    if (paymentButton) {
        if (showBoletoLoading) {
            paymentButton.textContent = 'AGUARDANDO BOLETO...';
            paymentButton.disabled = true;
        } else if (method === 'pix') {
            paymentButton.textContent = 'Copiar código PIX';
            paymentButton.disabled = !Boolean(transaction?.pix_copy_paste || transaction?.pix_qr_code);
        } else if (method === 'boleto') {
            paymentButton.textContent = transaction?.internal_status === 'failed' ? 'BOLETO INDISPONÍVEL' : 'ABRIR BOLETO';
            paymentButton.disabled = !Boolean(transaction?.boleto_url);
        } else {
            paymentButton.textContent = 'COPIAR REFERÊNCIA';
            paymentButton.disabled = false;
        }
    }

    renderPixQrCode({
        value: transaction?.pix_copy_paste || transaction?.pix_qr_code,
        image: pixQrCodeImage,
    }, method);
    updatePixExpiration(transaction);
}

function hasCompleteBoletoDetails(transaction) {
    return Boolean(transaction?.boleto_url);
}

function hasFailedBoletoDetails(transaction) {
    if (!transaction) {
        return false;
    }

    if (String(transaction?.internal_status || '').toLowerCase() === 'failed') {
        return true;
    }

    if (String(transaction?.gateway_status || '').toUpperCase() === 'FAILED') {
        return true;
    }

    return Boolean(transaction?.response_payload?.polling_error || transaction?.response_payload?.message);
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
    updatePaymentDetails(state, transaction);
}

function setBoletoLoadingState(state, isLoading) {
    state.boletoLoading = isLoading;
    updatePaymentDetails(state, state.paymentTransaction);
}

function sleep(ms) {
    return new Promise((resolve) => {
        window.setTimeout(resolve, ms);
    });
}

async function refreshBoletoUntilReady(state) {
    const maxAttempts = 8;
    const intervalMs = 1500;

    setBoletoLoadingState(state, true);

    for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
        await refreshExistingPayment(state);

        if (hasCompleteBoletoDetails(state.paymentTransaction) || hasFailedBoletoDetails(state.paymentTransaction)) {
            setBoletoLoadingState(state, false);
            return;
        }

        if (attempt < maxAttempts - 1) {
            setBoletoLoadingState(state, true);
            await sleep(intervalMs);
        }
    }

    setBoletoLoadingState(state, false);
}

function autoSubmitPaymentDetails(state, paymentForm) {
    if (!(paymentForm instanceof HTMLFormElement)) {
        return;
    }

    const method = String(state.session?.payment_method || '').toLowerCase();

    if (!['pix', 'boleto'].includes(method)) {
        return;
    }

    if (state.paymentTransaction) {
        return;
    }

    window.setTimeout(() => {
        if (!paymentForm.isConnected) {
            return;
        }

        paymentForm.requestSubmit();
    }, 0);
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
        || (
            String(paymentTransaction?.payment_method || '').toLowerCase() === 'credit_card'
            && ['authorized', 'paid'].includes(String(paymentTransaction?.internal_status || '').toLowerCase())
        )
        || String(paymentTransaction?.internal_status || '').toLowerCase() === 'paid'
        || String(session?.status || '').toLowerCase() === 'paid';
}

function openBoletoDocument(url) {
    if (!url) {
        return false;
    }

    const openedWindow = window.open(url, '_blank', 'noopener,noreferrer');

    if (openedWindow) {
        openedWindow.opener = null;
        return true;
    }

    return false;
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

function bindForms(state) {
    const identificationForms = Array.from(document.querySelectorAll('[data-checkout-form="identification"]'));
    const deliveryForm = document.getElementById('checkout-delivery-form');
    const paymentForm = document.getElementById('checkout-payment-form');
    const quantityInput = document.querySelector('[data-summary-quantity-input]');
    const quantityDecrementButton = document.querySelector('[data-summary-quantity-decrement]');
    const quantityIncrementButton = document.querySelector('[data-summary-quantity-increment]');
    let zipcodeLookupTimer = null;
    let zipcodeLookupRequestId = 0;
    let quantityUpdateTimer = null;
    let quantityUpdateRequestId = 0;

    const syncQuantityFromInput = async () => {
        if (!(quantityInput instanceof HTMLInputElement) || quantityInput.disabled) {
            return;
        }

        const previousQuantity = normalizeCheckoutQuantity(state.session?.quantity || state.config.checkoutLink?.quantity || 1);
        const nextQuantity = normalizeCheckoutQuantity(quantityInput.value);

        quantityInput.value = String(nextQuantity);

        if (nextQuantity === previousQuantity) {
            updateSummary(state.config, state);
            return;
        }

        state.session = {
            ...state.session,
            quantity: nextQuantity,
            subtotal: roundCurrency(nextQuantity * Number(state.config.checkoutLink?.unitPrice || 0)),
            total: roundCurrency(nextQuantity * Number(state.config.checkoutLink?.unitPrice || 0)),
            discount_total: 0,
            shipping_total: 0,
        };
        updateSummary(state.config, state);

        const requestId = ++quantityUpdateRequestId;

        try {
            const payload = await requestJson(state.config.urls.quantity, {
                method: 'POST',
                body: JSON.stringify({ quantity: nextQuantity }),
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            if (requestId !== quantityUpdateRequestId) {
                return;
            }

            state.session = payload.checkout_session || state.session;
            hydrateSession(state.session, state.config);
            updateSummary(state.config, state);
        } catch (error) {
            if (requestId !== quantityUpdateRequestId) {
                return;
            }

            state.session = {
                ...state.session,
                quantity: previousQuantity,
                subtotal: roundCurrency(previousQuantity * Number(state.config.checkoutLink?.unitPrice || 0)),
                total: roundCurrency(previousQuantity * Number(state.config.checkoutLink?.unitPrice || 0)),
            };
            hydrateSession(state.session, state.config);
            updateSummary(state.config, state);
            setFeedback(error.payload?.message || error.message || 'Não foi possível atualizar a quantidade.', 'error');
        }
    };

    const changeQuantityBy = (step) => {
        if (!(quantityInput instanceof HTMLInputElement) || quantityInput.disabled) {
            return;
        }

        quantityInput.value = String(normalizeCheckoutQuantity(Number(quantityInput.value || 0) + step));
        void syncQuantityFromInput();
    };

    identificationForms.forEach((identificationForm) => {
        identificationForm.addEventListener('input', (event) => {
            applyIdentificationMask(identificationForm, event.target);
            syncIdentificationDraftFromForm(state, identificationForm);

            if (event.target instanceof HTMLInputElement && event.target.name === 'customer_document') {
                setFieldErrors({ customer_document: [] });
            }

            if (event.target instanceof HTMLInputElement && event.target.name === 'customer_responsible_document') {
                setFieldErrors({ customer_responsible_document: [] });
            }
        });

        identificationForm.addEventListener('change', () => {
            syncIdentificationDraftFromForm(state, identificationForm);
        });

        identificationForm.addEventListener('blur', (event) => {
            if (event.target instanceof HTMLInputElement && event.target.name === 'customer_document') {
                if (validateIdentificationDocument(identificationForm)) {
                    lookupCompanyDataByCnpj(state, identificationForm);
                }
            }

            if (event.target instanceof HTMLInputElement && event.target.name === 'customer_responsible_document') {
                validateResponsibleCpf(identificationForm);
            }
        }, true);

        identificationForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearFeedback();
            clearFieldErrors();

            const personFormType = String(identificationForm.dataset.personForm || 'pf');
            const shouldValidateResponsibleDocument = personFormType === 'pj';

            if (
                !validateIdentificationForm(identificationForm)
                || (shouldValidateResponsibleDocument && !validateResponsibleCpf(identificationForm, { focusOnError: true }))
            ) {
                return;
            }

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

                if (!state.config.checkoutLink?.requestAddress) {
                    window.location.assign(payload.next_url || state.config.urls.payment);
                    return;
                }

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

    if (quantityInput instanceof HTMLInputElement) {
        quantityInput.addEventListener('input', () => {
            if (quantityUpdateTimer) {
                clearTimeout(quantityUpdateTimer);
            }

            const value = quantityInput.value;
            if (value === '') {
                return;
            }

            quantityUpdateTimer = window.setTimeout(() => {
                void syncQuantityFromInput();
            }, 350);
        });

        quantityInput.addEventListener('change', () => {
            if (quantityUpdateTimer) {
                clearTimeout(quantityUpdateTimer);
            }

            void syncQuantityFromInput();
        });

        quantityInput.addEventListener('blur', () => {
            if (quantityUpdateTimer) {
                clearTimeout(quantityUpdateTimer);
            }

            if (quantityInput.value === '') {
                quantityInput.value = String(normalizeCheckoutQuantity(state.session?.quantity || state.config.checkoutLink?.quantity || 1));
                updateSummary(state.config, state);
                return;
            }

            void syncQuantityFromInput();
        });
    }

    if (quantityDecrementButton instanceof HTMLButtonElement) {
        quantityDecrementButton.addEventListener('click', () => {
            changeQuantityBy(-1);
        });
    }

    if (quantityIncrementButton instanceof HTMLButtonElement) {
        quantityIncrementButton.addEventListener('click', () => {
            changeQuantityBy(1);
        });
    }

    const personTypeSwitch = document.querySelector('[data-person-type-switch]');
    const personTypeSwitchTrack = document.querySelector('[data-person-switch-track]');

    if (personTypeSwitch) {
        personTypeSwitch.addEventListener('change', () => {
            schedulePersonTypeSync(state, personTypeSwitch);
        });

        personTypeSwitch.addEventListener('click', () => {
            schedulePersonTypeSync(state, personTypeSwitch);
        });
    }

    if (personTypeSwitchTrack) {
        personTypeSwitchTrack.addEventListener('click', () => {
            schedulePersonTypeSync(state, personTypeSwitch);
        });
    }

    if (deliveryForm) {
        deliveryForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearFeedback();
            clearFieldErrors();
            setBusy(deliveryForm, true, 'Salvando...');

            try {
                const deliveryPayload = new FormData(deliveryForm);
                const identificationForm = document.querySelector('[data-checkout-form="identification"]:not([hidden])');

                if (identificationForm instanceof HTMLFormElement) {
                    const personFormType = String(identificationForm.dataset.personForm || 'pf');
                    const shouldValidateResponsibleDocument = personFormType === 'pj';

                    if (
                        !validateIdentificationForm(identificationForm)
                        || (shouldValidateResponsibleDocument && !validateResponsibleCpf(identificationForm, { focusOnError: true }))
                    ) {
                        return;
                    }

                    const identificationPayload = await requestJson(state.config.urls.identification, {
                        method: 'POST',
                        body: new FormData(identificationForm),
                    });

                    state.session = identificationPayload.checkout_session || state.session;
                    state.identificationDraft = {
                        ...state.identificationDraft,
                        ...collectFormValues(identificationForm),
                        customer_document_type: identificationDocumentType(identificationForm),
                    };
                    hydrateSession(state.session, state.config);
                    syncRecipientDefault(state);
                    updateSummary(state.config, state);
                }

                const payload = await requestJson(state.config.urls.delivery, {
                    method: 'POST',
                    body: deliveryPayload,
                });

                state.session = payload.checkout_session || state.session;
                hydrateSession(state.session, state.config);
                syncRecipientDefault(state);
                updateSummary(state.config, state);
                if (payload.payment_url) {
                    window.location.assign(payload.payment_url);
                    return;
                }

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
        const initialPaymentMethod = paymentForm.querySelector('[name="payment_method"]')?.value || '';
        updateInstallmentsVisibility(paymentForm, initialPaymentMethod);
        updateCreditCardFieldsVisibility(paymentForm, initialPaymentMethod);

        paymentForm.addEventListener('input', (event) => {
            applyPaymentMask(event.target);

            if (event.target instanceof HTMLInputElement && event.target.name === 'card[holder_document]') {
                setFieldErrors({ 'card.holder_document': [] });
            }
        });

        paymentForm.addEventListener('change', (event) => {
            if (!(event.target instanceof HTMLSelectElement) || event.target.name !== 'payment_method') {
                return;
            }

            updateInstallmentsVisibility(paymentForm, event.target.value);
            updateCreditCardFieldsVisibility(paymentForm, event.target.value);
        });

        paymentForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearFeedback();
            clearFieldErrors();

            if (!validatePaymentForm(paymentForm)) {
                return;
            }

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
                let shouldShowStartedMessage = true;

                if (state.paymentTransaction?.payment_method === 'pix') {
                    showWaitingPanel(state, state.paymentTransaction);
                } else if (state.paymentTransaction?.payment_method === 'boleto') {
                    if (!hasCompleteBoletoDetails(state.paymentTransaction)) {
                        await refreshExistingPayment(state);
                    }
                } else if (state.paymentTransaction?.payment_method === 'credit_card') {
                    const requires3ds = Boolean(
                        state.paymentTransaction?.requires_3ds
                        || state.paymentTransaction?.response_payload?.requires_3ds
                        || payload.requires_3ds
                    );
                    const transactionStatus = String(state.paymentTransaction?.internal_status || '').toLowerCase();

                    if (requires3ds) {
                        shouldShowStartedMessage = false;
                        setFeedback('Autenticação 3DS iniciada. Conclua o desafio do cartão.', 'success');
                        const authResponse = await confirmCreditCard3DS(state, paymentForm, state.paymentTransaction);

                        state.order = authResponse.order || state.order;
                        state.paymentTransaction = authResponse.payment_transaction || state.paymentTransaction;
                        state.session = authResponse.checkout_session || state.session;
                        state.thankYouUrl = authResponse.thank_you_url || state.thankYouUrl;

                        hydrateSession(state.session, state.config);
                        syncRecipientDefault(state);
                        updateSummary(state.config, state);
                        updatePaymentDetails(state, state.paymentTransaction);
                        setFeedback(authResponse.message || 'Autenticação 3DS processada com sucesso.', 'success');

                        const confirmedStatus = String(state.paymentTransaction?.internal_status || '').toLowerCase();

                        if (['authorized', 'paid'].includes(confirmedStatus) || isPaidOrder(state.order, state.paymentTransaction, state.session)) {
                            redirectToThankYou(state);
                            return;
                        }
                    } else if (['authorized', 'paid'].includes(transactionStatus)) {
                        redirectToThankYou(state);
                        return;
                    }
                }

                if (shouldShowStartedMessage) {
                    setFeedback('Pagamento iniciado com sucesso. A confirmação será atualizada via webhook.', 'success');
                }

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

function bindPaymentAction(state) {
    const actionButton = document.querySelector('[data-open-payment]');
    const copyDigitableLineButton = document.querySelector('[data-copy-boleto-digitable-line]');
    const copyBarcodeButton = document.querySelector('[data-copy-boleto-barcode]');
    const copyPixButton = document.querySelector('[data-copy-boleto-pix-copy-paste]');

    if (!actionButton) {
        return;
    }

    actionButton.addEventListener('click', async () => {
        const method = state.paymentTransaction?.payment_method || state.session.payment_method;
        const boletoUrl = state.paymentTransaction?.boleto_url || '';
        const paymentCode = method === 'boleto'
            ? boletoUrl
            : (state.paymentTransaction?.pix_copy_paste || state.paymentTransaction?.pix_qr_code);

        try {
            if (method === 'boleto') {
                if (!boletoUrl) {
                    throw new Error('Boleto ainda não disponível.');
                }

                openBoletoDocument(boletoUrl);
                setFeedback('Abrindo o boleto em uma nova aba.', 'success');
                return;
            }

            await copyText(paymentCode);
            setFeedback('Código Pix copiado para a área de transferência.', 'success');
        } catch (error) {
            setFeedback(error.message || 'Não foi possível concluir a ação.', 'error');
        }
    });

    copyDigitableLineButton?.addEventListener('click', async () => {
        const value = state.paymentTransaction?.boleto_digitable_line || '';

        try {
            await copyText(value);
            setFeedback('Linha digitável copiada para a área de transferência.', 'success');
        } catch (error) {
            setFeedback(error.message || 'Não foi possível copiar a linha digitável.', 'error');
        }
    });

    copyBarcodeButton?.addEventListener('click', async () => {
        const value = state.paymentTransaction?.boleto_barcode || '';

        try {
            await copyText(value);
            setFeedback('Código de barras copiado para a área de transferência.', 'success');
        } catch (error) {
            setFeedback(error.message || 'Não foi possível copiar o código de barras.', 'error');
        }
    });

    copyPixButton?.addEventListener('click', async () => {
        const value = state.paymentTransaction?.pix_copy_paste || state.paymentTransaction?.pix_qr_code || '';

        try {
            await copyText(value);
            setFeedback('Pix copia e cola copiado para a área de transferência.', 'success');
        } catch (error) {
            setFeedback(error.message || 'Não foi possível copiar o Pix copia e cola.', 'error');
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
    } else if (method === 'boleto' && hasFailedBoletoDetails(state.paymentTransaction)) {
        updatePaymentDetails(state, state.paymentTransaction);
    } else if (method === 'boleto' && !hasCompleteBoletoDetails(state.paymentTransaction)) {
        setBoletoLoadingState(state, true);
        updatePaymentDetails(state, state.paymentTransaction);
    } else {
        updatePaymentDetails(state, state.paymentTransaction);
    }
}

async function refreshExistingPayment(state) {
    try {
        const payload = await requestJson(state.config.urls.status, {
            method: 'GET',
        });

        state.session = payload.checkout_session || state.session;
        state.order = payload.order || state.order;
        state.paymentTransaction = payload.payment_transaction || state.paymentTransaction;
        state.thankYouUrl = payload.thank_you_url || state.thankYouUrl;

        hydrateSession(state.session, state.config);
        syncRecipientDefault(state);
        updateSummary(state.config, state);
        updatePaymentDetails(state, state.paymentTransaction);
    } catch (error) {
        setFeedback(error.payload?.message || error.message || 'Não foi possível atualizar o pagamento.', 'error');
    }
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
        boletoLoading: false,
        threeDsEnv: root.dataset.threeDsEnv || 'PROD',
        currentStep: config.currentStep || 'identification',
        thankYouUrl: config.thankYouUrl,
        personType: String(config.session?.customer_document_type || 'cpf').toLowerCase() === 'cnpj' ? 'pj' : 'pf',
        identificationDraft: {
            customer_name: config.session?.customer_name || '',
            customer_email: config.session?.customer_email || '',
            customer_phone: config.session?.customer_phone || '',
            customer_document: config.session?.customer_document || '',
            customer_birth_date: config.session?.customer_birth_date || '',
            customer_company_name: config.session?.customer_company_name || '',
            customer_responsible_document: config.session?.customer_responsible_document || '',
            customer_responsible_birth_date: config.session?.customer_responsible_birth_date || '',
        },
    };

    hydrateSession(state.session, state.config);
    syncRecipientDefault(state);
    updateSummary(config, state);
    bindForms(state);
    bindPaymentAction(state);
    updatePersonTypeUi(state);
    autoSubmitPaymentDetails(state, document.getElementById('checkout-payment-form'));

    if (isPaidOrder(state.order, state.paymentTransaction, state.session)) {
        redirectToThankYou(state);
        return;
    }

    if (state.paymentTransaction) {
        if (
            String(state.paymentTransaction.payment_method || '').toLowerCase() === 'boleto'
            && !hasCompleteBoletoDetails(state.paymentTransaction)
        ) {
            setBoletoLoadingState(state, true);
            void refreshBoletoUntilReady(state);
        }
        resumeExistingPayment(state);
        return;
    }
}

initCheckoutPublic();

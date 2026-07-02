import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/checkout-spa.css';

const cnpjCompanyLookupCache = new Map();

function readCheckoutSpaData() {
    const element = document.getElementById('checkout-spa-data');

    if (!element) {
        return null;
    }

    try {
        return JSON.parse(element.textContent || '{}');
    } catch (error) {
        return null;
    }
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
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

function normalizeDigits(value) {
    return String(value ?? '').replace(/\D+/g, '');
}

function normalizeQuantity(value) {
    const quantity = Number.parseInt(String(value ?? '').trim(), 10);

    if (!Number.isFinite(quantity) || quantity < 1) {
        return 1;
    }

    return Math.min(quantity, 999);
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

function formatDocument(value, personType) {
    return personType === 'pj' ? formatCnpj(value) : formatCpf(value);
}

function fillFormField(form, name, value) {
    const element = form?.querySelector(`[name="${name}"]`);

    if (!(element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement)) {
        return;
    }

    element.value = value ?? '';
}

function formatCardNumber(value) {
    const digits = normalizeDigits(value).slice(0, 19);

    return digits.replace(/(.{4})/g, '$1 ').trim();
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

async function lookupCompanyByCnpj(cnpj, lookupTemplate) {
    const cnpjDigits = normalizeDigits(cnpj);

    if (cnpjDigits.length !== 14 || !lookupTemplate) {
        return null;
    }

    const cachedData = cnpjCompanyLookupCache.get(cnpjDigits);

    if (cachedData) {
        return cachedData;
    }

    const payload = await requestJson(lookupTemplate.replace('__CNPJ__', cnpjDigits), {
        method: 'GET',
    });

    if (!payload?.company_name) {
        return null;
    }

    cnpjCompanyLookupCache.set(cnpjDigits, payload);

    return payload;
}

function applyCompanyLookupToForm(form, payload) {
    fillFormField(form, 'customer_company_name', payload?.company_name || '');
    fillFormField(form, 'customer_email', payload?.email || '');
    fillFormField(form, 'customer_phone', payload?.phone || '');
    fillFormField(form, 'customer_name', payload?.responsible_name || '');
    fillFormField(form, 'customer_responsible_document', payload?.responsible_document || '');
}

function isPaidState(order, paymentTransaction) {
    const orderStatus = String(order?.status || '').toLowerCase();
    const paymentStatus = String(paymentTransaction?.internal_status || '').toLowerCase();

    if (orderStatus === 'paid') {
        return true;
    }

    return ['authorized', 'paid'].includes(paymentStatus);
}

function resolvePaymentMethods(checkoutLink) {
    return [
        checkoutLink?.allow_pix
            ? {
                value: 'pix',
                label: 'Pix',
                description: 'Confirmação imediata e fluxo mais curto.',
            }
            : null,
        checkoutLink?.allow_boleto
            ? {
                value: 'boleto',
                label: 'Boleto',
                description: 'Mostra os dados do boleto na própria tela.',
            }
            : null,
        checkoutLink?.allow_credit_card
            ? {
                value: 'credit_card',
                label: 'Cartão',
                description: 'Pagamento com cartão e parcelamento.',
            }
            : null,
    ].filter(Boolean);
}

function resolveDefaultPaymentMethod(checkoutLink) {
    const methods = resolvePaymentMethods(checkoutLink);

    if (methods.length === 1) {
        return methods[0].value;
    }

    return '';
}

function calculatePricing(checkoutLink, quantity, paymentMethod) {
    const subtotal = roundCurrency(Number(quantity || 1) * Number(checkoutLink?.unit_price || 0));
    let discount = 0;

    if (paymentMethod === 'pix') {
        if (checkoutLink?.pix_discount_type === 'fixed') {
            discount = Math.min(subtotal, Number(checkoutLink?.pix_discount_value || 0));
        } else if (checkoutLink?.pix_discount_type === 'percentage') {
            discount = (subtotal * Number(checkoutLink?.pix_discount_value || 0)) / 100;
        }
    }

    if (paymentMethod === 'boleto') {
        if (checkoutLink?.boleto_discount_type === 'fixed') {
            discount = Math.min(subtotal, Number(checkoutLink?.boleto_discount_value || 0));
        } else if (checkoutLink?.boleto_discount_type === 'percentage') {
            discount = (subtotal * Number(checkoutLink?.boleto_discount_value || 0)) / 100;
        }
    }

    const shipping = checkoutLink?.free_shipping ? 0 : 0;
    const total = roundCurrency(Math.max(0, subtotal - discount + shipping));

    return {
        quantity: normalizeQuantity(quantity),
        unit_price: roundCurrency(Number(checkoutLink?.unit_price || 0)),
        subtotal: roundCurrency(subtotal),
        discount_total: roundCurrency(discount),
        shipping_total: roundCurrency(shipping),
        total,
    };
}

function resolveInitialStep(config, defaultPaymentMethod) {
    if (isPaidState(config.order, config.paymentTransaction)) {
        return 'status';
    }

    if (config.paymentTransaction) {
        return 'status';
    }

    const currentStep = String(config.currentStep || config.checkoutSession?.current_step || 'identification');

    if (currentStep === 'delivery') {
        return config.checkoutLink?.request_address ? 'delivery' : (defaultPaymentMethod ? 'payment-details' : 'payment-method');
    }

    if (currentStep === 'payment') {
        return defaultPaymentMethod ? 'payment-details' : 'payment-method';
    }

    return 'identification';
}

function paymentStatusLabel(paymentTransaction) {
    const status = String(paymentTransaction?.internal_status || '').toLowerCase();

    if (['authorized', 'paid'].includes(status)) {
        return 'Confirmado';
    }

    if (status === 'failed') {
        return 'Falhou';
    }

    if (status === 'processing') {
        return 'Processando';
    }

    return 'Aguardando';
}

function resolvePaymentCode(paymentTransaction) {
    if (!paymentTransaction) {
        return '';
    }

    return paymentTransaction.payment_method === 'boleto'
        ? (paymentTransaction.boleto_digitable_line || paymentTransaction.boleto_barcode || paymentTransaction.pix_copy_paste || '')
        : (paymentTransaction.pix_copy_paste || paymentTransaction.pix_qr_code || '');
}

function resolvePixImage(paymentTransaction) {
    if (!paymentTransaction) {
        return '';
    }

    return paymentTransaction.pix_qr_code_image
        || paymentTransaction.response_payload?.api_qrcode?.qrcode
        || paymentTransaction.response_payload?.api_qrcode?.image
        || '';
}

function resolveBoletoUrl(paymentTransaction) {
    return paymentTransaction?.boleto_url || paymentTransaction?.response_payload?.api_boleto?.url || '';
}

function resolveBoletoLoading(paymentTransaction) {
    if (!paymentTransaction) {
        return false;
    }

    return Boolean(paymentTransaction.payment_method === 'boleto' && !resolveBoletoUrl(paymentTransaction));
}

function mapErrors(errors = {}) {
    const flattened = {};

    Object.entries(errors).forEach(([field, messages]) => {
        flattened[field] = Array.isArray(messages) ? messages[0] : messages;
    });

    return flattened;
}

function copyText(value) {
    const text = String(value || '').trim();

    if (!text) {
        return Promise.reject(new Error('Nada para copiar.'));
    }

    if (navigator.clipboard?.writeText) {
        return navigator.clipboard.writeText(text);
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    try {
        document.execCommand('copy');
        document.body.removeChild(textarea);
        return Promise.resolve();
    } catch (error) {
        document.body.removeChild(textarea);
        return Promise.reject(error);
    }
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
        || transaction?.transaction_id
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

function CheckoutSpaApp() {
    const [config] = useState(() => readCheckoutSpaData());
    const checkoutLink = config?.checkoutLink || {};
    const allowedMethods = resolvePaymentMethods(checkoutLink);
    const defaultPaymentMethod = config?.checkoutSession?.payment_method || resolveDefaultPaymentMethod(checkoutLink);
    const initialQuantity = normalizeQuantity(config?.checkoutSession?.quantity || checkoutLink.quantity || 1);
    const [session, setSession] = useState(config?.checkoutSession || {});
    const [order, setOrder] = useState(config?.order || null);
    const [paymentTransaction, setPaymentTransaction] = useState(config?.paymentTransaction || null);
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState(defaultPaymentMethod);
    const [personType, setPersonType] = useState(String(config?.checkoutSession?.customer_document_type || 'cpf').toLowerCase() === 'cnpj' ? 'pj' : 'pf');
    const [quantity, setQuantity] = useState(initialQuantity);
    const [step, setStep] = useState(() => resolveInitialStep(config || {}, defaultPaymentMethod));
    const [feedback, setFeedback] = useState({ type: 'info', message: '' });
    const [fieldErrors, setFieldErrors] = useState({});
    const [busyAction, setBusyAction] = useState('');
    const [zipcodeLookupState, setZipcodeLookupState] = useState('idle');
    const quantityTimerRef = useRef(null);
    const pollTimerRef = useRef(null);
    const zipcodeTimerRef = useRef(null);
    const zipcodeLookupIdRef = useRef(0);
    const cnpjTimerRef = useRef(null);
    const cnpjLookupIdRef = useRef(0);

    useEffect(() => {
        if (!selectedPaymentMethod && allowedMethods.length === 1) {
            setSelectedPaymentMethod(allowedMethods[0].value);
        }
    }, [allowedMethods, selectedPaymentMethod]);

    useEffect(() => {
        if (!config) {
            return;
        }

        if (isPaidState(order, paymentTransaction)) {
            window.location.assign(config.urls.thankYou);
        }
    }, [config, order, paymentTransaction]);

    useEffect(() => {
        if (step !== 'status' || !config?.urls?.status) {
            return undefined;
        }

        const pollStatus = async () => {
            try {
                const payload = await requestJson(config.urls.status, { method: 'GET' });

                setSession(payload.checkout_session || session);
                setOrder(payload.order || order);
                setPaymentTransaction(payload.payment_transaction || paymentTransaction);

                if (isPaidState(payload.order, payload.payment_transaction)) {
                    window.location.assign(payload.thank_you_url || config.urls.thankYou);
                }
            } catch (error) {
                setFeedback({
                    type: 'error',
                    message: error.payload?.message || error.message || 'Não foi possível atualizar o status do pagamento.',
                });
            }
        };

        void pollStatus();

        pollTimerRef.current = window.setInterval(() => {
            void pollStatus();
        }, 5000);

        return () => {
            if (pollTimerRef.current) {
                window.clearInterval(pollTimerRef.current);
            }
        };
    }, [config, order, paymentTransaction, session, step]);

    useEffect(() => {
        return () => {
            if (quantityTimerRef.current) {
                window.clearTimeout(quantityTimerRef.current);
            }

            if (pollTimerRef.current) {
                window.clearInterval(pollTimerRef.current);
            }

            if (zipcodeTimerRef.current) {
                window.clearTimeout(zipcodeTimerRef.current);
            }

            if (cnpjTimerRef.current) {
                window.clearTimeout(cnpjTimerRef.current);
            }
        };
    }, []);

    useEffect(() => {
        if (personType !== 'pj') {
            return undefined;
        }

        const cnpjDigits = normalizeDigits(session.customer_document || '');

        if (cnpjDigits.length !== 14 || !config?.urls?.cnpjLookupTemplate) {
            return undefined;
        }

        const timer = window.setTimeout(() => {
            const form = document.querySelector('[data-person-form="pj"]');

            if (form instanceof HTMLFormElement) {
                void syncCompanyDataByCnpj(form, cnpjDigits);
            }
        }, 0);

        return () => {
            window.clearTimeout(timer);
        };
    }, [config?.urls?.cnpjLookupTemplate, personType, session.customer_document]);

    if (!config) {
        return null;
    }

    const visualConfig = checkoutLink.visual_config || {};
    const summaryPricing = paymentTransaction
        ? {
            quantity: normalizeQuantity(session.quantity || quantity),
            unit_price: roundCurrency(session.unit_price || checkoutLink.unit_price || 0),
            subtotal: roundCurrency(session.subtotal || 0),
            discount_total: roundCurrency(session.discount_total || 0),
            shipping_total: roundCurrency(session.shipping_total || 0),
            total: roundCurrency(session.total || 0),
        }
        : calculatePricing(checkoutLink, quantity, selectedPaymentMethod);
    const paymentMethodLabel = paymentTransaction?.payment_method
        || selectedPaymentMethod
        || defaultPaymentMethod
        || '';
    const showDeliveryStep = checkoutLink.request_address;
    const showPaymentStep = step === 'payment-method' || step === 'payment-details' || step === 'status';
    const showPaymentSelector = step === 'payment-method';
    const showPaymentDetails = step === 'payment-details';
    const selectedMethod = allowedMethods.find((method) => method.value === paymentMethodLabel) || allowedMethods[0] || null;
    const currentStatusLabel = paymentStatusLabel(paymentTransaction);
    const canEditQuantity = !paymentTransaction && !order && step !== 'status';
    const rootStyle = {
        '--checkout-spa-primary': visualConfig.primary_color || '#1f1a17',
        '--checkout-spa-navbar': visualConfig.navbar_background_color || '#ffffff',
        '--checkout-spa-navbar-ink': visualConfig.navbar_text_color || '#1f2937',
        '--checkout-spa-button': visualConfig.primary_color || '#1f1a17',
        '--checkout-spa-button-ink': visualConfig.button_text_color || visualConfig.navbar_text_color || '#ffffff',
    };
    const summaryDescription = checkoutLink.product?.description
        || checkoutLink.product?.short_description
        || checkoutLink.seller?.name
        || 'Juntter';
    const paymentLogos = [
        { label: 'Mastercard', variant: 'mastercard', icon: 'sprite' },
        { label: 'Elo', variant: 'elo', icon: 'elo' },
        { label: 'Boleto', variant: 'boleto', icon: 'boleto' },
        { label: 'Pix', variant: 'pix', icon: 'pix' },
        { label: 'Visa', variant: 'visa', icon: 'sprite' },
        { label: 'Amex', variant: 'amex', icon: 'sprite' },
        { label: 'Diners', variant: 'diners', icon: 'sprite' },
        { label: 'Hiper', variant: 'hiper', icon: 'hiper' },
    ];
    const paymentLogoSpriteUrl = '/adminlte/plugins/fontawesome-free/sprites/brands.svg';

    function renderPaymentLogo(paymentLogo) {
        if (paymentLogo.icon === 'sprite') {
            const symbolId = paymentLogo.variant === 'mastercard'
                ? 'cc-mastercard'
                : paymentLogo.variant === 'visa'
                    ? 'cc-visa'
                    : paymentLogo.variant === 'amex'
                        ? 'cc-amex'
                        : 'cc-diners-club';

            return (
                <svg className="checkout-spa-payment-logo-svg" aria-hidden="true" viewBox="0 0 576 512">
                    <use href={`${paymentLogoSpriteUrl}#${symbolId}`} />
                </svg>
            );
        }

        if (paymentLogo.icon === 'elo') {
            return (
                <svg className="checkout-spa-payment-logo-svg checkout-spa-payment-logo-svg--elo" aria-hidden="true" viewBox="0 0 120 40">
                    <g fill="none" fillRule="evenodd">
                        <path d="M19 28h15" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
                        <path d="M19 21h11" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
                        <path d="M19 14h15" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
                        <circle cx="52" cy="20" r="7" fill="currentColor" />
                        <rect x="72" y="11" width="8" height="18" rx="4" fill="currentColor" />
                        <path d="M90 11v18h10" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                    </g>
                </svg>
            );
        }

        if (paymentLogo.icon === 'boleto') {
            return (
                <svg className="checkout-spa-payment-logo-svg checkout-spa-payment-logo-svg--boleto" aria-hidden="true" viewBox="0 0 120 40">
                    <rect x="4" y="6" width="112" height="28" rx="4" fill="none" stroke="currentColor" strokeWidth="2" />
                    <g fill="currentColor">
                        <rect x="14" y="10" width="3" height="20" />
                        <rect x="20" y="10" width="2" height="20" />
                        <rect x="25" y="10" width="5" height="20" />
                        <rect x="33" y="10" width="2" height="20" />
                        <rect x="39" y="10" width="4" height="20" />
                        <rect x="47" y="10" width="2" height="20" />
                        <rect x="53" y="10" width="6" height="20" />
                        <rect x="62" y="10" width="2" height="20" />
                        <rect x="67" y="10" width="4" height="20" />
                        <rect x="74" y="10" width="2" height="20" />
                        <rect x="79" y="10" width="5" height="20" />
                        <rect x="87" y="10" width="2" height="20" />
                        <rect x="92" y="10" width="4" height="20" />
                        <rect x="100" y="10" width="2" height="20" />
                    </g>
                </svg>
            );
        }

        if (paymentLogo.icon === 'pix') {
            return (
                <svg className="checkout-spa-payment-logo-svg checkout-spa-payment-logo-svg--pix" aria-hidden="true" viewBox="0 0 40 40">
                    <path d="M20 4 33 17 20 30 7 17Z" fill="none" stroke="currentColor" strokeWidth="3" strokeLinejoin="round" />
                    <path d="M14 14c2 0 3 1 6 4s4 4 6 4" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                    <path d="M14 26c2 0 3-1 6-4s4-4 6-4" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            );
        }

        return (
            <svg className="checkout-spa-payment-logo-svg checkout-spa-payment-logo-svg--hiper" aria-hidden="true" viewBox="0 0 120 40">
                <rect x="4" y="6" width="112" height="28" rx="6" fill="currentColor" />
                <path d="M26 11h10v18H26z" fill="#fff" opacity="0.96" />
                <path d="M42 11h10l9 18H51l-1.7-3.7H43L41.4 29H31z" fill="#fff" opacity="0.96" />
                <path d="M63 11h10c6 0 9 3 9 7 0 4-3 7-9 7h-4v4H63zm9 10c2 0 3-1 3-3 0-2-1-3-3-3h-4v6z" fill="#fff" opacity="0.96" />
                <path d="M88 11h9c7 0 11 3 11 9s-4 9-11 9h-9zm8 14c3 0 4-1 4-5 0-3-1-5-4-5h-2v10z" fill="#fff" opacity="0.96" />
            </svg>
        );
    }

    async function syncQuantity(nextQuantity, previousQuantity) {
        if (!config?.urls?.quantity || !canEditQuantity) {
            return;
        }

        try {
            const payload = await requestJson(config.urls.quantity, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ quantity: nextQuantity }),
            });

            setSession(payload.checkout_session || session);
            setQuantity(normalizeQuantity(payload.checkout_session?.quantity || nextQuantity));
            setFeedback({
                type: 'success',
                message: 'Quantidade atualizada com sucesso.',
            });
        } catch (error) {
            setQuantity(previousQuantity);

            setFeedback({
                type: 'error',
                message: error.payload?.message || error.message || 'Não foi possível atualizar a quantidade.',
            });
        }
    }

    function handleQuantityChange(event) {
        if (!canEditQuantity) {
            return;
        }

        const previousQuantity = quantity;
        const nextQuantity = normalizeQuantity(event.target.value);

        setQuantity(nextQuantity);

        if (quantityTimerRef.current) {
            window.clearTimeout(quantityTimerRef.current);
        }

        quantityTimerRef.current = window.setTimeout(() => {
            void syncQuantity(nextQuantity, previousQuantity);
        }, 350);
    }

    function handleQuantityBlur() {
        if (!canEditQuantity) {
            return;
        }

        if (quantityTimerRef.current) {
            window.clearTimeout(quantityTimerRef.current);
        }

        if (!quantity) {
            setQuantity(normalizeQuantity(session.quantity || checkoutLink.quantity || 1));
        }
    }

    async function handleIdentificationSubmit(event) {
        event.preventDefault();
        setFieldErrors({});
        setBusyAction('identification');

        try {
            if (personType === 'pj') {
                await syncCompanyDataByCnpj(event.currentTarget, event.currentTarget.querySelector('[name="customer_document"]')?.value || '');
            }

            const response = await requestJson(config.urls.identify, {
                method: 'POST',
                body: new FormData(event.currentTarget),
            });

            setSession(response.checkout_session || session);
            setFeedback({
                type: 'success',
                message: response.message || 'Identificação salva com sucesso.',
            });

            if (showDeliveryStep) {
                setStep('delivery');
                return;
            }

            if (selectedPaymentMethod || allowedMethods.length === 1) {
                setStep('payment-details');
                return;
            }

            setStep('payment-method');
        } catch (error) {
            if (error.status === 422 && error.payload?.errors) {
                setFieldErrors(mapErrors(error.payload.errors));
                setFeedback({
                    type: 'error',
                    message: 'Revise os campos destacados antes de continuar.',
                });
            } else {
                setFeedback({
                    type: 'error',
                    message: error.payload?.message || error.message || 'Não foi possível salvar a identificação.',
                });
            }
        } finally {
            setBusyAction('');
        }
    }

    async function handleDeliverySubmit(event) {
        event.preventDefault();
        setFieldErrors({});
        setBusyAction('delivery');

        try {
            const response = await requestJson(config.urls.delivery, {
                method: 'POST',
                body: new FormData(event.currentTarget),
            });

            setSession(response.checkout_session || session);
            setFeedback({
                type: 'success',
                message: response.message || 'Endereço salvo com sucesso.',
            });

            if (selectedPaymentMethod || allowedMethods.length === 1) {
                setStep('payment-details');
                return;
            }

            setStep('payment-method');
        } catch (error) {
            if (error.status === 422 && error.payload?.errors) {
                setFieldErrors(mapErrors(error.payload.errors));
                setFeedback({
                    type: 'error',
                    message: 'Revise os campos destacados antes de continuar.',
                });
            } else {
                setFeedback({
                    type: 'error',
                    message: error.payload?.message || error.message || 'Não foi possível salvar o endereço.',
                });
            }
        } finally {
            setBusyAction('');
        }
    }

    async function handleChoosePaymentMethod(paymentMethod) {
        if (!config?.urls?.choosePaymentMethod) {
            return;
        }

        setSelectedPaymentMethod(paymentMethod);
        setBusyAction('payment-method');

        try {
            const payload = new FormData();
            payload.append('payment_method', paymentMethod);

            const response = await requestJson(config.urls.choosePaymentMethod, {
                method: 'POST',
                body: payload,
            });

            setSession(response.checkout_session || session);
            setFeedback({
                type: 'success',
                message: response.message || 'Método de pagamento selecionado.',
            });
        } catch (error) {
            setFieldErrors(mapErrors(error.payload?.errors || {}));
            setFeedback({
                type: 'error',
                message: error.payload?.message || error.message || 'Não foi possível selecionar o método de pagamento.',
            });
        } finally {
            setBusyAction('');
        }
    }

    function handleGoToPreviousPaymentStep() {
        if (step === 'payment-details' && allowedMethods.length > 1 && config?.urls?.choosePaymentMethod) {
            setStep('payment-method');
            return;
        }

        setStep(showDeliveryStep ? 'delivery' : 'identification');
    }

    function handleContinueToPaymentDetails() {
        if (busyAction === 'payment-method') {
            return;
        }

        if (!selectedPaymentMethod && !defaultPaymentMethod && allowedMethods.length > 1) {
            setFeedback({
                type: 'error',
                message: 'Selecione uma forma de pagamento para continuar.',
            });
            return;
        }

        setStep('payment-details');
    }

    async function handlePaymentSubmit(event) {
        event.preventDefault();
        setFieldErrors({});
        setBusyAction('payment');

        try {
            const paymentForm = event.currentTarget;
            const formData = new FormData(paymentForm);

            if (!formData.get('payment_method')) {
                formData.set('payment_method', selectedPaymentMethod || defaultPaymentMethod || '');
            }

            const response = await requestJson(config.urls.startPayment, {
                method: 'POST',
                body: formData,
            });

            setSession(response.checkout_session || session);
            setOrder(response.order || order);
            setPaymentTransaction(response.payment_transaction || paymentTransaction);

            const paymentMethod = String(response.payment_transaction?.payment_method || formData.get('payment_method') || '').toLowerCase();
            const paymentStatus = String(response.payment_transaction?.internal_status || '').toLowerCase();
            const currentTransaction = response.payment_transaction || paymentTransaction;
            const paid = ['authorized', 'paid'].includes(paymentStatus) || isPaidState(response.order, currentTransaction);

            if (paymentMethod === 'credit_card' && response.requires_3ds) {
                setFeedback({
                    type: 'info',
                    message: 'Autenticação 3DS iniciada. Aguarde a confirmação do banco.',
                });

                try {
                    const authResponse = await confirmCreditCard3DS({
                        config,
                        session: response.checkout_session || session,
                        order: response.order || order,
                        paymentTransaction: currentTransaction,
                        threeDsEnv: config.threeDsEnv || 'PROD',
                    }, paymentForm, {
                        ...(currentTransaction || {}),
                        gateway_transaction_id: response.transaction_id || currentTransaction?.gateway_transaction_id,
                    });

                    setSession(authResponse.checkout_session || response.checkout_session || session);
                    setOrder(authResponse.order || response.order || order);
                    setPaymentTransaction(authResponse.payment_transaction || currentTransaction);

                    if (isPaidState(authResponse.order || response.order, authResponse.payment_transaction || currentTransaction)) {
                        window.location.assign(authResponse.thank_you_url || response.thank_you_url || config.urls.thankYou);
                        return;
                    }

                    setFeedback({
                        type: 'success',
                        message: authResponse.message || 'Autenticação 3DS concluída com sucesso.',
                    });
                    setStep('status');
                    return;
                } catch (error) {
                    setFeedback({
                        type: 'error',
                        message: error.payload?.message || error.message || 'Não foi possível concluir a autenticação 3DS.',
                    });
                    setStep('status');
                    return;
                }
            }

            if (paid) {
                window.location.assign(response.thank_you_url || config.urls.thankYou);
                return;
            }

            if (paymentMethod === 'pix' || paymentMethod === 'boleto') {
                setFeedback({
                    type: 'success',
                    message: response.message || 'Pagamento iniciado com sucesso.',
                });
                setStep('status');
                return;
            }

            setFeedback({
                type: 'success',
                message: response.message || 'Pagamento iniciado com sucesso.',
            });
            setStep('status');
        } catch (error) {
            if (error.status === 422 && error.payload?.errors) {
                setFieldErrors(mapErrors(error.payload.errors));
                setFeedback({
                    type: 'error',
                    message: 'Revise os campos destacados antes de continuar.',
                });
            } else {
                setFeedback({
                    type: 'error',
                    message: error.payload?.message || error.message || 'Não foi possível iniciar o pagamento.',
                });
            }
        } finally {
            setBusyAction('');
        }
    }

    function handleDocumentMask(event) {
        const target = event.target;

        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.name === 'customer_phone') {
            target.value = formatPhone(target.value);
        }

        if (target.name === 'customer_document') {
            target.value = formatDocument(target.value, personType);

            if (personType === 'pj') {
                scheduleCompanyLookup(target.form, target.value);
            }
        }

        if (target.name === 'customer_responsible_document') {
            target.value = formatCpf(target.value);
        }

        if (target.name === 'zipcode') {
            target.value = formatZipcode(target.value);
        }

        if (target.name === 'state') {
            target.value = target.value.toUpperCase().slice(0, 2);
        }

        if (target.name === 'card[holder_document]') {
            target.value = formatDocument(target.value, 'pf');
        }

        if (target.name === 'card[card_number]') {
            target.value = formatCardNumber(target.value);
        }
    }

    function clearCompanyLookupErrors() {
        setFieldErrors((current) => {
            const nextErrors = { ...current };

            delete nextErrors.customer_company_name;
            delete nextErrors.customer_email;
            delete nextErrors.customer_phone;
            delete nextErrors.customer_name;
            delete nextErrors.customer_responsible_document;

            return nextErrors;
        });
    }

    async function syncCompanyDataByCnpj(form, cnpjValue) {
        const lookupTemplate = config?.urls?.cnpjLookupTemplate || '';
        const cnpjDigits = normalizeDigits(cnpjValue);

        if (personType !== 'pj' || cnpjDigits.length !== 14 || !lookupTemplate || !form) {
            return null;
        }

        const lookupId = cnpjLookupIdRef.current + 1;
        cnpjLookupIdRef.current = lookupId;

        try {
            const payload = await lookupCompanyByCnpj(cnpjDigits, lookupTemplate);

            if (cnpjLookupIdRef.current !== lookupId || !payload) {
                return null;
            }

            applyCompanyLookupToForm(form, payload);
            clearCompanyLookupErrors();

            return payload;
        } catch (error) {
            return null;
        }
    }

    function scheduleCompanyLookup(form, cnpjValue) {
        const cnpjDigits = normalizeDigits(cnpjValue);

        if (cnpjTimerRef.current) {
            window.clearTimeout(cnpjTimerRef.current);
        }

        if (personType !== 'pj' || cnpjDigits.length !== 14 || !form) {
            return;
        }

        cnpjTimerRef.current = window.setTimeout(() => {
            void syncCompanyDataByCnpj(form, cnpjDigits);
        }, 350);
    }

    function handleDocumentBlur(event) {
        const target = event.target;

        if (!(target instanceof HTMLInputElement) || target.name !== 'customer_document' || personType !== 'pj') {
            return;
        }

        const form = target.form;

        if (!form) {
            return;
        }

        if (cnpjTimerRef.current) {
            window.clearTimeout(cnpjTimerRef.current);
        }

        void syncCompanyDataByCnpj(form, target.value);
    }

    function fillDeliveryField(form, selector, value) {
        const element = form?.querySelector(selector);

        if (!(element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement)) {
            return;
        }

        element.value = value ?? '';
    }

    function clearDeliveryLookupFields(form) {
        fillDeliveryField(form, '[name="street"]', '');
        fillDeliveryField(form, '[name="neighborhood"]', '');
        fillDeliveryField(form, '[name="city"]', '');
        fillDeliveryField(form, '[name="state"]', '');
    }

    function applyZipcodeLookupResult(form, address) {
        fillDeliveryField(form, '[name="street"]', address.logradouro || '');
        fillDeliveryField(form, '[name="neighborhood"]', address.bairro || '');
        fillDeliveryField(form, '[name="city"]', address.localidade || '');
        fillDeliveryField(form, '[name="state"]', address.uf || '');
    }

    async function syncDeliveryAddressByZipcode(form, zipcode) {
        const normalizedZipcode = normalizeDigits(zipcode);

        if (!isValidZipcode(normalizedZipcode)) {
            clearDeliveryLookupFields(form);
            setZipcodeLookupState('idle');
            return;
        }

        const lookupId = zipcodeLookupIdRef.current + 1;
        zipcodeLookupIdRef.current = lookupId;
        setZipcodeLookupState('loading');

        try {
            const address = await lookupAddressByZipcode(normalizedZipcode);

            if (zipcodeLookupIdRef.current !== lookupId) {
                return;
            }

            applyZipcodeLookupResult(form, address);
            setFieldErrors((current) => {
                if (!current.zipcode) {
                    return current;
                }

                const nextErrors = { ...current };
                delete nextErrors.zipcode;
                return nextErrors;
            });
            setZipcodeLookupState('idle');
        } catch (error) {
            if (zipcodeLookupIdRef.current !== lookupId) {
                return;
            }

            clearDeliveryLookupFields(form);
            setZipcodeLookupState('error');
            setFieldErrors({
                zipcode: [error.message || 'Não foi possível consultar o CEP.'],
            });
        }
    }

    function handleZipcodeLookup(event) {
        const target = event.target;

        if (!(target instanceof HTMLInputElement) || target.name !== 'zipcode') {
            return;
        }

        const form = target.form;

        if (!form) {
            return;
        }

        if (zipcodeTimerRef.current) {
            window.clearTimeout(zipcodeTimerRef.current);
        }

        const normalizedZipcode = normalizeDigits(target.value);

        if (!isValidZipcode(normalizedZipcode)) {
            clearDeliveryLookupFields(form);
            setZipcodeLookupState('idle');
            return;
        }

        zipcodeTimerRef.current = window.setTimeout(() => {
            void syncDeliveryAddressByZipcode(form, normalizedZipcode);
        }, 350);
    }

    function handleZipcodeBlur(event) {
        const target = event.target;

        if (!(target instanceof HTMLInputElement) || target.name !== 'zipcode') {
            return;
        }

        const form = target.form;

        if (!form) {
            return;
        }

        if (zipcodeTimerRef.current) {
            window.clearTimeout(zipcodeTimerRef.current);
        }

        setZipcodeLookupState('loading');
        void syncDeliveryAddressByZipcode(form, target.value);
    }

    function renderIdentificationStep() {
        const personIsCompany = personType === 'pj';

        return (
            <section className="checkout-spa-step-card checkout-spa-step-card--intro">
                <div
                    className="checkout-spa-section-head checkout-spa-section-head--compact"
                    style={{ marginBottom: 0, paddingBottom: 0, borderBottom: 0 }}
                >
                    <div>
                        <h2 className="checkout-spa-section-title">Identificação</h2>
                    </div>
                    <div className="checkout-spa-toggle-group" role="tablist" aria-label="Tipo de pessoa">
                        <button
                            type="button"
                            className={`checkout-spa-toggle-button ${personType === 'pf' ? 'is-active' : ''}`}
                            onClick={() => setPersonType('pf')}
                        >
                            Pessoa física
                        </button>
                        <button
                            type="button"
                            className={`checkout-spa-toggle-button ${personType === 'pj' ? 'is-active' : ''}`}
                            onClick={() => setPersonType('pj')}
                        >
                            Pessoa jurídica
                        </button>
                    </div>
                </div>

                <form className="checkout-spa-form" onSubmit={handleIdentificationSubmit}>
                    <input type="hidden" name="customer_document_type" value={personIsCompany ? 'cnpj' : 'cpf'} />

                    {personIsCompany ? (
                        <div className="checkout-spa-field-grid is-two-columns">
                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">CNPJ</span>
                                <input
                                    className="checkout-spa-input"
                                    name="customer_document"
                                    defaultValue={session.customer_document || ''}
                                    maxLength={18}
                                    placeholder="00.000.000/0000-00"
                                    inputMode="numeric"
                                    onInput={handleDocumentMask}
                                    onBlur={handleDocumentBlur}
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_document || ''}</p>
                            </label>

                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">E-mail</span>
                                <input
                                    className="checkout-spa-input"
                                    type="email"
                                    name="customer_email"
                                    defaultValue={session.customer_email || ''}
                                    placeholder="voce@exemplo.com"
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_email || ''}</p>
                            </label>

                            <label className="checkout-spa-field is-full">
                                <span className="checkout-spa-label">Nome da empresa</span>
                                <input
                                    className="checkout-spa-input"
                                    name="customer_company_name"
                                    defaultValue={session.customer_company_name || ''}
                                    placeholder="Razão social ou nome fantasia"
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_company_name || ''}</p>
                            </label>

                            <label className="checkout-spa-field is-full">
                                <span className="checkout-spa-label">Nome do responsável</span>
                                <input
                                    className="checkout-spa-input"
                                    name="customer_name"
                                    defaultValue={session.customer_name || ''}
                                    placeholder="Nome do responsável"
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_name || ''}</p>
                            </label>

                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">CPF do responsável</span>
                                <input
                                    className="checkout-spa-input"
                                    name="customer_responsible_document"
                                    defaultValue={session.customer_responsible_document || ''}
                                    maxLength={14}
                                    placeholder="000.000.000-00"
                                    inputMode="numeric"
                                    onInput={handleDocumentMask}
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_responsible_document || ''}</p>
                            </label>

                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">Nascimento do responsável</span>
                                <input
                                    className="checkout-spa-input"
                                    type="date"
                                    name="customer_responsible_birth_date"
                                    defaultValue={session.customer_responsible_birth_date || ''}
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_responsible_birth_date || ''}</p>
                            </label>

                            <label className="checkout-spa-field is-full">
                                <span className="checkout-spa-label">Celular</span>
                                <input
                                    className="checkout-spa-input"
                                    name="customer_phone"
                                    defaultValue={session.customer_phone || ''}
                                    maxLength={15}
                                    placeholder="(11) 99999-9999"
                                    inputMode="numeric"
                                    onInput={handleDocumentMask}
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_phone || ''}</p>
                            </label>
                        </div>
                    ) : (
                        <div className="checkout-spa-field-grid is-two-columns">
                            <label className="checkout-spa-field is-full">
                                <span className="checkout-spa-label">Nome completo</span>
                                <input
                                    className="checkout-spa-input"
                                    name="customer_name"
                                    defaultValue={session.customer_name || ''}
                                    placeholder="Nome completo"
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_name || ''}</p>
                            </label>

                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">CPF</span>
                                <input
                                    className="checkout-spa-input"
                                    name="customer_document"
                                    defaultValue={session.customer_document || ''}
                                    maxLength={14}
                                    placeholder="000.000.000-00"
                                    inputMode="numeric"
                                    onInput={handleDocumentMask}
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_document || ''}</p>
                            </label>

                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">E-mail</span>
                                <input
                                    className="checkout-spa-input"
                                    type="email"
                                    name="customer_email"
                                    defaultValue={session.customer_email || ''}
                                    placeholder="voce@exemplo.com"
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_email || ''}</p>
                            </label>

                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">Nascimento</span>
                                <input
                                    className="checkout-spa-input"
                                    type="date"
                                    name="customer_birth_date"
                                    defaultValue={session.customer_birth_date || ''}
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_birth_date || ''}</p>
                            </label>

                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">Celular</span>
                                <input
                                    className="checkout-spa-input"
                                    name="customer_phone"
                                    defaultValue={session.customer_phone || ''}
                                    maxLength={15}
                                    placeholder="(11) 99999-9999"
                                    inputMode="numeric"
                                    onInput={handleDocumentMask}
                                />
                                <p className="checkout-spa-error">{fieldErrors.customer_phone || ''}</p>
                            </label>
                        </div>
                    )}

                    <div className="checkout-spa-actions">
                        <button className="checkout-spa-button is-primary" type="submit" disabled={busyAction === 'identification'}>
                            {busyAction === 'identification' ? 'Salvando...' : (showDeliveryStep ? 'Continuar para endereço' : 'Continuar para pagamento')}
                        </button>
                    </div>
                </form>
            </section>
        );
    }

    function renderDeliveryStep() {
        if (!showDeliveryStep) {
            return null;
        }

        return (
            <section className="checkout-spa-step-card checkout-spa-step-card--delivery">
                <div className="checkout-spa-section-head">
                    <div>
                        <h2 className="checkout-spa-section-title">Endereço</h2>
                        <p className="checkout-spa-section-text">
                            Use o CEP para preencher os demais campos automaticamente.
                        </p>
                    </div>
                </div>

                <form className="checkout-spa-form" onSubmit={handleDeliverySubmit}>
                    <div className="checkout-spa-field-grid is-two-columns">
                        <label className="checkout-spa-field">
                            <span className="checkout-spa-label">CEP</span>
                            <input
                                className="checkout-spa-input"
                                name="zipcode"
                                defaultValue={session.zipcode || ''}
                                maxLength={9}
                                placeholder="00000-000"
                                inputMode="numeric"
                                onInput={(event) => {
                                    handleDocumentMask(event);
                                    handleZipcodeLookup(event);
                                }}
                                onBlur={handleZipcodeBlur}
                            />
                            {zipcodeLookupState === 'loading' ? (
                                <p className="checkout-spa-field-note" aria-live="polite">
                                    Consultando CEP...
                                </p>
                            ) : null}
                            <p className="checkout-spa-error">{fieldErrors.zipcode || ''}</p>
                        </label>

                        <label className="checkout-spa-field is-full">
                            <span className="checkout-spa-label">Endereço</span>
                            <input
                                className="checkout-spa-input"
                                name="street"
                                defaultValue={session.street || ''}
                                placeholder="Rua, avenida, travessa..."
                            />
                            <p className="checkout-spa-error">{fieldErrors.street || ''}</p>
                        </label>

                        <label className="checkout-spa-field">
                            <span className="checkout-spa-label">Número</span>
                            <input className="checkout-spa-input" name="number" defaultValue={session.number || ''} />
                            <p className="checkout-spa-error">{fieldErrors.number || ''}</p>
                        </label>

                        <label className="checkout-spa-field">
                            <span className="checkout-spa-label">Complemento</span>
                            <input className="checkout-spa-input" name="complement" defaultValue={session.complement || ''} />
                            <p className="checkout-spa-error">{fieldErrors.complement || ''}</p>
                        </label>

                        <label className="checkout-spa-field">
                            <span className="checkout-spa-label">Bairro</span>
                            <input className="checkout-spa-input" name="neighborhood" defaultValue={session.neighborhood || ''} />
                            <p className="checkout-spa-error">{fieldErrors.neighborhood || ''}</p>
                        </label>

                        <label className="checkout-spa-field">
                            <span className="checkout-spa-label">Cidade</span>
                            <input className="checkout-spa-input" name="city" defaultValue={session.city || ''} />
                            <p className="checkout-spa-error">{fieldErrors.city || ''}</p>
                        </label>

                        <label className="checkout-spa-field">
                            <span className="checkout-spa-label">UF</span>
                            <input
                                className="checkout-spa-input"
                                name="state"
                                defaultValue={session.state || ''}
                                maxLength={2}
                                onInput={handleDocumentMask}
                            />
                            <p className="checkout-spa-error">{fieldErrors.state || ''}</p>
                        </label>

                        <input type="hidden" name="recipient_name" defaultValue={session.recipient_name || session.customer_name || ''} />
                    </div>

                    <div className="checkout-spa-actions">
                        <button
                            className="checkout-spa-button is-secondary"
                            type="button"
                            onClick={() => setStep('identification')}
                            disabled={busyAction === 'delivery'}
                        >
                            Voltar para identificação
                        </button>

                        <button className="checkout-spa-button is-primary" type="submit" disabled={busyAction === 'delivery'}>
                            {busyAction === 'delivery' ? 'Salvando...' : 'Continuar para pagamento'}
                        </button>
                    </div>
                </form>
            </section>
        );
    }

    function renderPaymentSelection() {
        if (allowedMethods.length === 0) {
            return (
                <section className="checkout-spa-step-card">
                    <h3>Nenhum método disponível</h3>
                    <p>Este link não possui formas de pagamento habilitadas.</p>
                </section>
            );
        }

        return (
            <section className="checkout-spa-step-card checkout-spa-step-card--payment-details">
                <div className="checkout-spa-section-head">
                    <div>
                        <h2 className="checkout-spa-section-title">Pagamento</h2>
                        <p className="checkout-spa-section-text">
                            Escolha a forma de pagamento e prossiga com a compra.
                        </p>
                    </div>
                </div>

                <div className="checkout-spa-method-grid">
                    {allowedMethods.map((method) => (
                        <button
                            key={method.value}
                            type="button"
                            className={`checkout-spa-method-card ${selectedPaymentMethod === method.value ? 'is-selected' : ''}`}
                            onClick={() => {
                                void handleChoosePaymentMethod(method.value);
                            }}
                            disabled={busyAction === 'payment-method'}
                        >
                            <p className="checkout-spa-method-name">{method.label}</p>
                            <p className="checkout-spa-method-text">{method.description}</p>
                            {selectedPaymentMethod === method.value ? (
                                <span className="checkout-spa-pill is-highlight">Selecionado</span>
                            ) : null}
                        </button>
                    ))}
                </div>

                <div className="checkout-spa-actions checkout-spa-actions--split">
                    <button
                        className="checkout-spa-button is-secondary"
                        type="button"
                        onClick={handleGoToPreviousPaymentStep}
                    >
                        {showDeliveryStep ? 'Voltar para endereço' : 'Voltar'}
                    </button>

                    <button
                        className="checkout-spa-button is-primary"
                        type="button"
                        onClick={handleContinueToPaymentDetails}
                        disabled={busyAction === 'payment-method' || (!selectedPaymentMethod && !defaultPaymentMethod && allowedMethods.length > 1)}
                    >
                        Continuar para pagamento
                    </button>
                </div>
            </section>
        );
    }

    function renderPaymentDetails() {
        if (!showPaymentStep) {
            return null;
        }

        const method = selectedMethod?.value || defaultPaymentMethod;
        const cardMethod = method === 'credit_card';
        const boletoMethod = method === 'boleto';
        const pixMethod = method === 'pix';

        return (
            <section className="checkout-spa-step-card checkout-spa-step-card--payment-details">
                <div className="checkout-spa-section-head">
                    <div>
                        <h2 className="checkout-spa-section-title">
                            {cardMethod ? 'Cartão de crédito' : (boletoMethod ? 'Boleto' : 'Pix')}
                        </h2>
                        {!cardMethod ? (
                            <p className="checkout-spa-section-text">Revise os dados e finalize o pagamento.</p>
                        ) : null}
                    </div>
                </div>

                {cardMethod && paymentTransaction?.response_payload?.requires_3ds ? (
                    <div className="checkout-spa-feedback is-info is-visible" style={{ marginBottom: 16 }}>
                        A autenticação 3DS foi solicitada pelo gateway e será concluída nesta tela.
                    </div>
                ) : null}

                <form className="checkout-spa-form" onSubmit={handlePaymentSubmit}>
                    <input type="hidden" name="payment_method" value={method} />

                    {cardMethod ? (
                        <div className="checkout-spa-field-grid is-two-columns">
                            <label className="checkout-spa-field is-full">
                                <span className="checkout-spa-label">Nome no cartão</span>
                                <input className="checkout-spa-input" name="card[holder_name]" defaultValue={session.customer_name || ''} autoComplete="cc-name" />
                                <p className="checkout-spa-error">{fieldErrors['card.holder_name'] || ''}</p>
                            </label>

                            <label className="checkout-spa-field is-full">
                                <span className="checkout-spa-label">Documento do titular</span>
                                <input
                                    className="checkout-spa-input"
                                    name="card[holder_document]"
                                    defaultValue={session.customer_document || ''}
                                    maxLength={18}
                                    placeholder="CPF/CNPJ"
                                    inputMode="numeric"
                                    onInput={handleDocumentMask}
                                />
                                <p className="checkout-spa-error">{fieldErrors['card.holder_document'] || ''}</p>
                            </label>

                            <label className="checkout-spa-field is-full">
                                <span className="checkout-spa-label">Número do cartão</span>
                                <input
                                    className="checkout-spa-input"
                                    name="card[card_number]"
                                    defaultValue=""
                                    maxLength={23}
                                    placeholder="0000 0000 0000 0000"
                                    inputMode="numeric"
                                    autoComplete="cc-number"
                                    onInput={handleDocumentMask}
                                />
                                <p className="checkout-spa-error">{fieldErrors['card.card_number'] || ''}</p>
                            </label>

                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">Validade - mês</span>
                                <input className="checkout-spa-input" name="card[expiration_month]" type="number" min="1" max="12" inputMode="numeric" placeholder="MM" />
                                <p className="checkout-spa-error">{fieldErrors['card.expiration_month'] || ''}</p>
                            </label>

                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">Validade - ano</span>
                                <input className="checkout-spa-input" name="card[expiration_year]" type="number" min={new Date().getFullYear()} max="2099" inputMode="numeric" placeholder="AAAA" />
                                <p className="checkout-spa-error">{fieldErrors['card.expiration_year'] || ''}</p>
                            </label>

                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">CVV</span>
                                <input className="checkout-spa-input" name="card[security_code]" inputMode="numeric" maxLength={4} placeholder="123" autoComplete="cc-csc" />
                                <p className="checkout-spa-error">{fieldErrors['card.security_code'] || ''}</p>
                            </label>

                            <label className="checkout-spa-field">
                                <span className="checkout-spa-label">Parcelas</span>
                                <input className="checkout-spa-input" name="installments" type="number" min="1" max="18" defaultValue="1" />
                                <p className="checkout-spa-error">{fieldErrors.installments || ''}</p>
                            </label>
                        </div>
                    ) : null}

                {pixMethod || boletoMethod ? (
                    <div className="checkout-spa-step-card checkout-spa-step-card--payment-note">
                        <h3>{pixMethod ? 'Pix' : 'Boleto'}</h3>
                        <p>
                            {pixMethod
                                    ? 'O pagamento será processado com QR Code e copia e cola.'
                                    : 'O pagamento será processado com linha digitável e código de barras.'}
                            </p>
                    </div>
                ) : null}

                    <div className="checkout-spa-actions checkout-spa-actions--split">
                        <button
                            className="checkout-spa-button is-secondary"
                            type="button"
                            onClick={handleGoToPreviousPaymentStep}
                            disabled={busyAction === 'payment'}
                        >
                            {allowedMethods.length > 1 && config?.urls?.choosePaymentMethod ? 'Voltar aos métodos' : (showDeliveryStep ? 'Voltar para endereço' : 'Voltar')}
                        </button>

                        <button className="checkout-spa-button is-primary" type="submit" disabled={busyAction === 'payment'}>
                            {busyAction === 'payment' ? 'Processando...' : (pixMethod ? 'Gerar Pix' : (boletoMethod ? 'Gerar boleto' : 'Pagar'))}
                        </button>
                    </div>
                </form>
            </section>
        );
    }

    function renderStatusPanel() {
        const paymentMethod = paymentTransaction?.payment_method || paymentMethodLabel;
        const paymentCode = resolvePaymentCode(paymentTransaction);
        const pixImage = resolvePixImage(paymentTransaction);
        const boletoUrl = resolveBoletoUrl(paymentTransaction);
        const boletoLoading = resolveBoletoLoading(paymentTransaction);
        const isPix = paymentMethod === 'pix';
        const isBoleto = paymentMethod === 'boleto';

        return (
            <section className="checkout-spa-step-card">
                <div className="checkout-spa-section-head">
                    <div>
                        <h2 className="checkout-spa-section-title">Confirmação</h2>
                        <p className="checkout-spa-section-text">
                            {isPix || isBoleto
                                ? 'Acompanhe a confirmação do pagamento nesta tela.'
                                : 'Seu pagamento foi enviado ao gateway.'}
                        </p>
                    </div>
                    <span className={`checkout-spa-pill ${paymentStatusLabel(paymentTransaction) === 'Confirmado' ? 'is-highlight' : ''}`}>
                        {paymentStatusLabel(paymentTransaction)}
                    </span>
                </div>

                <div className="checkout-spa-status-card">
                    <div className="checkout-spa-status-top">
                        <div>
                            <h3 style={{ margin: '0 0 6px', fontSize: '18px' }}>Pagamento em andamento</h3>
                            <p style={{ margin: 0, color: '#6f6a60', lineHeight: 1.55 }}>
                                {isPix
                                    ? 'Escaneie o QR Code ou copie o código Pix.'
                                    : isBoleto
                                        ? 'Abra o boleto ou copie os dados de pagamento.'
                                        : paymentTransaction?.response_payload?.requires_3ds
                                            ? 'A autenticação 3DS está sendo processada.'
                                            : 'Acompanhe a autenticação do cartão.'}
                            </p>
                        </div>
                        <span className={`checkout-spa-status-badge ${paymentStatusLabel(paymentTransaction) === 'Confirmado' ? 'is-success' : 'is-pending'}`}>
                            {currentStatusLabel}
                        </span>
                    </div>

                    {isPix && (pixImage || paymentCode) ? (
                        <div className="checkout-spa-qrcode">
                            {pixImage ? <img src={pixImage} alt="QR Code Pix" /> : null}
                            <p className="checkout-spa-code">{paymentCode || 'O código Pix será exibido em instantes.'}</p>
                        </div>
                    ) : null}

                    {isBoleto ? (
                        <div className="checkout-spa-step-card">
                            {boletoLoading ? (
                                <div className="checkout-spa-loading">
                                    <span>Gerando boleto...</span>
                                </div>
                            ) : null}
                            {!boletoLoading && paymentCode ? (
                                <p className="checkout-spa-code">{paymentCode}</p>
                            ) : null}
                            {!boletoLoading && boletoUrl ? (
                                <div className="checkout-spa-actions">
                                    <a className="checkout-spa-button is-primary" href={boletoUrl} target="_blank" rel="noreferrer">
                                        Abrir boleto
                                    </a>
                                </div>
                            ) : null}
                        </div>
                    ) : null}

                    <div className="checkout-spa-copy-row">
                        {(isPix || isBoleto) && paymentCode ? (
                            <button
                                className="checkout-spa-button is-secondary"
                                type="button"
                                onClick={async () => {
                                    try {
                                        await copyText(paymentCode);
                                        setFeedback({ type: 'success', message: 'Código copiado para a área de transferência.' });
                                    } catch (error) {
                                        setFeedback({ type: 'error', message: error.message || 'Não foi possível copiar o código.' });
                                    }
                                }}
                            >
                                Copiar código
                            </button>
                        ) : null}

                        {isPix || isBoleto ? (
                            <button
                                className="checkout-spa-button is-ghost"
                                type="button"
                                onClick={async () => {
                                    try {
                                        const payload = await requestJson(config.urls.status, { method: 'GET' });
                                        setSession(payload.checkout_session || session);
                                        setOrder(payload.order || order);
                                        setPaymentTransaction(payload.payment_transaction || paymentTransaction);
                                        setFeedback({
                                            type: 'info',
                                            message: payload.message || 'Status atualizado.',
                                        });
                                    } catch (error) {
                                        setFeedback({
                                            type: 'error',
                                            message: error.payload?.message || error.message || 'Não foi possível atualizar o status.',
                                        });
                                    }
                                }}
                            >
                                Atualizar status
                            </button>
                        ) : null}
                    </div>
                </div>
            </section>
        );
    }

    const panelContent = (() => {
        if (step === 'status') {
            return renderStatusPanel();
        }

        if (step === 'payment-details') {
            return renderPaymentDetails();
        }

        if (step === 'payment-method') {
            return renderPaymentSelection();
        }

        if (step === 'delivery') {
            return renderDeliveryStep();
        }

        return renderIdentificationStep();
    })();

    return (
        <div className="checkout-spa-page" style={rootStyle}>
            <div className="checkout-spa-backdrop checkout-spa-backdrop--left" aria-hidden="true" />
            <div className="checkout-spa-backdrop checkout-spa-backdrop--right" aria-hidden="true" />

            <main className="checkout-spa-shell">
                <header className="checkout-spa-header">
                    <div className="checkout-spa-brand">
                        <img
                            className="checkout-spa-brand-logo"
                            src={config.sellerLogoUrl || '/img/logo/juntter_webp_640_174.webp'}
                            alt={checkoutLink.seller?.name || 'Juntter'}
                            onError={(event) => {
                                event.currentTarget.onerror = null;
                                event.currentTarget.src = '/img/logo/juntter_webp_640_174.webp';
                            }}
                        />
                    </div>

                    <nav className="checkout-spa-stepper" aria-label="Etapas do checkout">
                        <span className={`checkout-spa-step ${step === 'identification' ? 'is-active' : ''}`}>
                            <span className="checkout-spa-step-number">1</span>
                            Identificação
                        </span>
                        {showDeliveryStep ? (
                            <span className={`checkout-spa-step ${step === 'delivery' ? 'is-active' : ''}`}>
                                <span className="checkout-spa-step-number">2</span>
                                Entrega
                            </span>
                        ) : null}
                        <span className={`checkout-spa-step ${showPaymentStep ? 'is-active' : ''}`}>
                            <span className="checkout-spa-step-number">{showDeliveryStep ? 3 : 2}</span>
                            Pagamento
                        </span>
                    </nav>
                </header>

                {feedback.message ? (
                    <div className={`checkout-spa-feedback is-${feedback.type}`}>
                        {feedback.message}
                    </div>
                ) : null}

                <div className="checkout-spa-grid">
                    <section className="checkout-spa-panel">
                        {panelContent}
                    </section>

                    <div className="checkout-spa-sidebar">
                        <aside className="checkout-spa-summary" aria-label="Resumo do pedido">
                            <div className="checkout-spa-summary-head">
                                <div>
                                    <p className="checkout-spa-summary-kicker">Resumo do pedido</p>
                                    <h2 className="checkout-spa-summary-title">{checkoutLink.name || 'Checkout'}</h2>
                                    <p className="summary-note">
                                        {summaryDescription}
                                    </p>
                                </div>

                                {checkoutLink.product_image_url ? (
                                    <div className="checkout-spa-summary-image" aria-hidden="true">
                                        <img src={checkoutLink.product_image_url} alt="" />
                                    </div>
                                ) : null}
                            </div>

                            <div className="checkout-spa-summary-stack">
                                <div className="checkout-spa-summary-row">
                                    <span>Produto</span>
                                    <strong>{checkoutLink.product?.name || 'Produto'}</strong>
                                </div>

                                <div className="checkout-spa-summary-row">
                                    <span>Quantidade</span>
                                    <div className="checkout-spa-quantity-control">
                                        <button
                                            className="checkout-spa-quantity-button"
                                            type="button"
                                            onClick={() => {
                                                if (!canEditQuantity) {
                                                    return;
                                                }

                                                const nextQuantity = normalizeQuantity(quantity - 1);
                                                const previousQuantity = quantity;
                                                setQuantity(nextQuantity);

                                                if (quantityTimerRef.current) {
                                                    window.clearTimeout(quantityTimerRef.current);
                                                }

                                                quantityTimerRef.current = window.setTimeout(() => {
                                                    void syncQuantity(nextQuantity, previousQuantity);
                                                }, 350);
                                            }}
                                            disabled={!canEditQuantity || quantity <= 1}
                                        >
                                            -
                                        </button>

                                        <input
                                            className="checkout-spa-input checkout-spa-quantity-input"
                                            name="quantity"
                                            type="number"
                                            min="1"
                                            step="1"
                                            inputMode="numeric"
                                            value={quantity}
                                            onChange={handleQuantityChange}
                                            onBlur={handleQuantityBlur}
                                            disabled={!canEditQuantity}
                                        />

                                        <button
                                            className="checkout-spa-quantity-button"
                                            type="button"
                                            onClick={() => {
                                                if (!canEditQuantity) {
                                                    return;
                                                }

                                                const nextQuantity = normalizeQuantity(quantity + 1);
                                                const previousQuantity = quantity;
                                                setQuantity(nextQuantity);

                                                if (quantityTimerRef.current) {
                                                    window.clearTimeout(quantityTimerRef.current);
                                                }

                                                quantityTimerRef.current = window.setTimeout(() => {
                                                    void syncQuantity(nextQuantity, previousQuantity);
                                                }, 350);
                                            }}
                                            disabled={!canEditQuantity}
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>

                                <div className="checkout-spa-summary-row">
                                    <span>Subtotal</span>
                                    <strong>{formatCurrency(summaryPricing.subtotal)}</strong>
                                </div>

                                {summaryPricing.discount_total > 0 ? (
                                    <div className="checkout-spa-summary-row">
                                        <span>Desconto</span>
                                        <strong>{formatCurrency(summaryPricing.discount_total)}</strong>
                                    </div>
                                ) : null}

                                {summaryPricing.shipping_total > 0 ? (
                                    <div className="checkout-spa-summary-row">
                                        <span>Frete</span>
                                        <strong>{formatCurrency(summaryPricing.shipping_total)}</strong>
                                    </div>
                                ) : (
                                    <div className="checkout-spa-summary-row">
                                        <span>Frete</span>
                                        <strong>{checkoutLink.free_shipping ? 'Grátis' : 'A calcular'}</strong>
                                    </div>
                                )}

                                <div className="checkout-spa-summary-row checkout-spa-summary-total">
                                    <span>Total</span>
                                    <strong>{formatCurrency(summaryPricing.total)}</strong>
                                </div>
                            </div>

                            {visualConfig.footer_text ? (
                                <div className="checkout-spa-summary-footer">
                                    {visualConfig.footer_text}
                                </div>
                            ) : null}
                        </aside>

                        <section className="checkout-spa-payment-strip" aria-label="Formas de pagamento">
                            <p className="checkout-spa-payment-strip-title">Formas de pagamento</p>
                            <div className="checkout-spa-payment-strip-grid">
                                {paymentLogos.map((paymentLogo) => (
                                    <span
                                        key={paymentLogo.label}
                                        className={`checkout-spa-payment-mark checkout-spa-payment-mark--${paymentLogo.variant}`}
                                        aria-label={paymentLogo.label}
                                        title={paymentLogo.label}
                                    >
                                        {renderPaymentLogo(paymentLogo)}
                                    </span>
                                ))}
                            </div>
                        </section>
                    </div>
                </div>
            </main>
        </div>
    );
}

function initCheckoutSpa() {
    const root = document.getElementById('checkout-spa-root');

    if (!root) {
        return;
    }

    createRoot(root).render(<CheckoutSpaApp />);
}

initCheckoutSpa();

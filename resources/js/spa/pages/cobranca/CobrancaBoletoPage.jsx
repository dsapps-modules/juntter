import {
    BankOutlined,
    CheckCircleOutlined,
    CreditCardOutlined,
    CopyOutlined,
    EyeOutlined,
    ExclamationCircleOutlined,
    FileTextOutlined,
    SendOutlined,
    StopOutlined,
} from '@ant-design/icons';
import {
    Alert,
    Button,
    Card,
    Col,
    DatePicker,
    Divider,
    Empty,
    Form,
    Input,
    Row,
    Select,
    Skeleton,
    Space,
    Table,
    Tag,
    Typography,
    message,
} from 'antd';
import dayjs from 'dayjs';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import MoneyInputField from '../../components/form/MoneyInputField';
import { formatDocument, isValidCnpj, isValidDocument } from '../../documentValidation';

const stateOptions = [
    'AC',
    'AL',
    'AP',
    'AM',
    'BA',
    'CE',
    'DF',
    'ES',
    'GO',
    'MA',
    'MT',
    'MS',
    'MG',
    'PA',
    'PB',
    'PR',
    'PE',
    'PI',
    'RJ',
    'RN',
    'RS',
    'RO',
    'RR',
    'SC',
    'SP',
    'SE',
    'TO',
].map((value) => ({ value, label: value }));

const stateOptionsByValue = new Set(stateOptions.map((option) => option.value));

const stateAbbreviationsByName = {
    Acre: 'AC',
    Alagoas: 'AL',
    Amapá: 'AP',
    Amapa: 'AP',
    Amazonas: 'AM',
    Bahia: 'BA',
    Ceará: 'CE',
    Ceara: 'CE',
    'Distrito Federal': 'DF',
    'Espírito Santo': 'ES',
    'Espirito Santo': 'ES',
    'Goiás': 'GO',
    'Goias': 'GO',
    'Maranhão': 'MA',
    'Maranhao': 'MA',
    'Mato Grosso': 'MT',
    'Mato Grosso do Sul': 'MS',
    'Minas Gerais': 'MG',
    'Pará': 'PA',
    'Para': 'PA',
    'Paraíba': 'PB',
    'Paraiba': 'PB',
    'Paraná': 'PR',
    'Parana': 'PR',
    'Pernambuco': 'PE',
    'Piauí': 'PI',
    'Piaui': 'PI',
    'Rio de Janeiro': 'RJ',
    'Rio Grande do Norte': 'RN',
    'Rio Grande do Sul': 'RS',
    'Rondônia': 'RO',
    'Rondonia': 'RO',
    'Roraima': 'RR',
    'Santa Catarina': 'SC',
    'São Paulo': 'SP',
    'Sao Paulo': 'SP',
    'Sergipe': 'SE',
    'Tocantins': 'TO',
};

const defaultOverview = {
    summary: {
        total_billets: 0,
        paid_billets: 0,
        pending_billets: 0,
        failed_billets: 0,
        total_amount: 'R$ 0,00',
        total_fees: 'R$ 0,00',
    },
    rows: [],
    periods: [],
    selected_period: 'all',
    recent_boletos: [],
    actions: [],
};

const boletoInitialValues = {
    amount: '0,00',
    expiration: dayjs().add(1, 'day'),
    payment_limit_date: dayjs().add(2, 'day'),
    recharge: false,
    client: {
        first_name: '',
        last_name: '',
        document: '',
        phone: '',
        email: '',
        address: {
            street: '',
            number: '',
            complement: '',
            neighborhood: '',
            city: '',
            state: '',
            zip_code: '',
        },
    },
    instruction: {
        booklet: false,
        description: '',
        late_fee: {
            amount: '0,00',
        },
        interest: {
            amount: '0,00',
        },
        discount: {
            amount: '0,00',
            limit_date: dayjs(),
        },
    },
};

function documentValidator(_, value) {
    if (!value) {
        return Promise.resolve();
    }

    return isValidDocument(value)
        ? Promise.resolve()
        : Promise.reject(new Error('O documento informado é inválido.'));
}

function formatPhone(value) {
    const digits = String(value ?? '').replace(/\D+/g, '').slice(0, 11);

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

function normalizeDigits(value) {
    return String(value ?? '').replace(/\D+/g, '');
}

function formatZipcode(value) {
    const digits = normalizeDigits(value).slice(0, 8);

    if (digits.length <= 5) {
        return digits;
    }

    return `${digits.slice(0, 5)}-${digits.slice(5)}`;
}

function resolveStateValue(address) {
    const stateCode = String(address?.uf ?? '').trim().toUpperCase();

    if (stateOptionsByValue.has(stateCode)) {
        return stateCode;
    }

    const stateName = String(address?.estado ?? '').trim();

    return stateAbbreviationsByName[stateName] ?? undefined;
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

async function lookupCompanyByCnpj(cnpj) {
    const digits = normalizeDigits(cnpj);

    const response = await fetch(`/checkout/cnpj/${digits}`, {
        headers: {
            Accept: 'application/json',
        },
        credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(payload.message || 'Não foi possível consultar o CNPJ.');
    }

    return payload;
}

function getCurrentPeriod() {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

function formatPeriodLabel(period) {
    const [year, month] = period.split('-');

    return `${month}/${year}`;
}

function formatCurrency(valueInCents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((valueInCents ?? 0) / 100);
}

function parseCurrencyValue(value) {
    const normalizedValue = String(value ?? '')
        .replace(/[^\d,.-]/g, '')
        .replace(/\./g, '')
        .replace(',', '.');
    const parsedValue = Number(normalizedValue);

    return Number.isFinite(parsedValue) ? parsedValue : 0;
}

function formatStatus(status) {
    switch (status) {
        case 'PAID':
        case 'Pago':
            return 'Pago';
        case 'APPROVED':
        case 'Aprovado':
            return 'Aprovado';
        case 'PENDING':
        case 'Pendente':
            return 'Pendente';
        case 'PROCESSING':
        case 'Processando':
            return 'Processando';
        case 'FAILED':
        case 'Falha':
            return 'Falha';
        case 'CANCELED':
        case 'Cancelado':
            return 'Cancelado';
        case 'REFUNDED':
        case 'Estornado':
            return 'Estornado';
        default:
            return status ?? 'Sem status';
    }
}

function statusTone(status) {
    switch (formatStatus(status)) {
        case 'Pago':
        case 'Aprovado':
            return 'green';
        case 'Pendente':
        case 'Processando':
            return 'gold';
        case 'Falha':
        case 'Cancelado':
        case 'Estornado':
            return 'red';
        default:
            return 'blue';
    }
}

function getFirstValidationError(errors) {
    return Object.values(errors ?? {}).flat().find(Boolean) ?? '';
}

function syncBoletoDates(form, expiration) {
    if (!expiration || !dayjs(expiration).isValid()) {
        return;
    }

    const expirationDate = dayjs(expiration);
    const paymentLimitDate = form.getFieldValue('payment_limit_date');
    if (
        !paymentLimitDate ||
        !dayjs(paymentLimitDate).isValid() ||
        dayjs(paymentLimitDate).isSame(expirationDate) ||
        dayjs(paymentLimitDate).isBefore(expirationDate)
    ) {
        form.setFieldValue('payment_limit_date', expirationDate.add(1, 'day'));
    }

    form.setFieldValue(['instruction', 'discount', 'limit_date'], expirationDate.subtract(1, 'day'));
}

async function copyText(text) {
    if (!text) {
        return;
    }

    try {
        await navigator.clipboard.writeText(text);
        message.success('Link do boleto copiado.');
    } catch (_error) {
        message.error('Não foi possível copiar o link.');
    }
}

export default function CobrancaBoletoPage() {
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const tablePagination = {
        pageSize: 10,
        showSizeChanger: false,
        hideOnSinglePage: true,
    };
    const currentPeriod = getCurrentPeriod();

    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [formVisible, setFormVisible] = useState(false);
    const [feedback, setFeedback] = useState(null);
    const [selectedPeriod, setSelectedPeriod] = useState(currentPeriod);
    const [reloadToken, setReloadToken] = useState(0);
    const [overview, setOverview] = useState(defaultOverview);
    const [cancelingBoletoCode, setCancelingBoletoCode] = useState(null);

    useEffect(() => {
        const controller = new AbortController();

        async function loadOverview() {
            setLoading(true);

            try {
                const params = new URLSearchParams();
                params.set('period', selectedPeriod);

                const response = await fetch(`/api/spa/cobranca/boleto${params.toString() !== '' ? `?${params.toString()}` : ''}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar os boletos.');
                }

                const data = await response.json();
                setOverview((current) => ({
                    ...current,
                    ...data,
                    rows: data.rows ?? [],
                    recent_boletos: data.recent_boletos ?? [],
                    periods: data.periods ?? current.periods,
                    selected_period: data.selected_period ?? current.selected_period,
                }));
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setFeedback({
                        type: 'error',
                        message: fetchError.message || 'Falha ao carregar os boletos.',
                    });
                    scrollToTop();
                }
            } finally {
                setLoading(false);
            }
        }

        loadOverview();

        return () => controller.abort();
    }, [reloadToken, selectedPeriod]);

    useEffect(() => {
        syncBoletoDates(form, form.getFieldValue('expiration'));
    }, [form]);

    const periodOptions = useMemo(() => {
        const optionsByValue = new Map();

        optionsByValue.set('all', {
            label: 'Todos os meses',
            value: 'all',
        });

        optionsByValue.set(currentPeriod, {
            label: formatPeriodLabel(currentPeriod),
            value: currentPeriod,
        });

        (overview.periods ?? []).forEach((item) => {
            if (item?.value && !optionsByValue.has(item.value)) {
                optionsByValue.set(item.value, item);
            }
        });

        return Array.from(optionsByValue.values());
    }, [currentPeriod, overview.periods]);

    const boletoRows = useMemo(() => {
        return (overview.rows ?? [])
            .filter((row) => row.kind === 'boleto' || row.type === 'Boleto' || row.type === 'BOLETO' || row.raw_status)
            .sort((left, right) => (right.created_at_sort ?? 0) - (left.created_at_sort ?? 0));
    }, [overview.rows]);

    const boletoSummary = overview.summary ?? defaultOverview.summary;
    const recentBoletos = (overview.recent_boletos ?? boletoRows.slice(0, 3)).slice(0, 3);
    const tableTitle = selectedPeriod === 'all'
        ? 'Boletos de todos os meses'
        : `Boletos do mês ${formatPeriodLabel(selectedPeriod)}`;

    function refreshOverview(nextPeriod = selectedPeriod) {
        if (nextPeriod === selectedPeriod) {
            setReloadToken((current) => current + 1);
            return;
        }

        setSelectedPeriod(nextPeriod);
    }

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    }

    async function handleZipcodeBlur() {
        const zipcode = form.getFieldValue(['client', 'address', 'zip_code']);

        if (normalizeDigits(zipcode).length !== 8) {
            return;
        }

        try {
            const address = await lookupAddressByZipcode(zipcode);
            const stateValue = resolveStateValue(address);

            form.setFieldsValue({
                client: {
                    address: {
                        zip_code: zipcode,
                        street: address.logradouro || '',
                        neighborhood: address.bairro || '',
                        city: address.localidade || '',
                        state: stateValue,
                    },
                },
            });

            form.setFieldValue(['client', 'address', 'state'], stateValue);
        } catch (error) {
            message.error(error.message || 'Não foi possível consultar o CEP.');
        }
    }

    async function handleDocumentBlur() {
        const document = form.getFieldValue(['client', 'document']);
        const digits = normalizeDigits(document);

        if (digits.length !== 14 || !isValidCnpj(digits)) {
            return;
        }

        try {
            const company = await lookupCompanyByCnpj(digits);
            const companyAddress = company.address ?? {};

            form.setFieldsValue({
                client: {
                    first_name: company.company_name ?? form.getFieldValue(['client', 'first_name']) ?? '',
                    last_name: company.trade_name ?? form.getFieldValue(['client', 'last_name']) ?? '',
                    document: formatDocument(company.cnpj ?? digits),
                    phone: company.phone ? formatPhone(company.phone) : form.getFieldValue(['client', 'phone']) ?? '',
                    email: company.email ?? form.getFieldValue(['client', 'email']) ?? '',
                    address: {
                        street: companyAddress.street ?? form.getFieldValue(['client', 'address', 'street']) ?? '',
                        number: companyAddress.number ?? form.getFieldValue(['client', 'address', 'number']) ?? '',
                        complement: companyAddress.complement ?? form.getFieldValue(['client', 'address', 'complement']) ?? '',
                        neighborhood: companyAddress.neighborhood ?? form.getFieldValue(['client', 'address', 'neighborhood']) ?? '',
                        city: companyAddress.city ?? form.getFieldValue(['client', 'address', 'city']) ?? '',
                        state: companyAddress.state ?? form.getFieldValue(['client', 'address', 'state']) ?? '',
                        zip_code: companyAddress.zip_code ? formatZipcode(companyAddress.zip_code) : form.getFieldValue(['client', 'address', 'zip_code']) ?? '',
                    },
                },
            });
        } catch (error) {
            message.error(error.message || 'Não foi possível consultar o CNPJ.');
        }
    }

    function handleExpirationChange(value) {
        syncBoletoDates(form, value);
    }

    async function handleSubmit(values) {
        setSubmitting(true);
        setFeedback(null);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/cobranca/boleto', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    amount: values.amount,
                    expiration: values.expiration ? values.expiration.format('YYYY-MM-DD') : null,
                    payment_limit_date: values.payment_limit_date ? values.payment_limit_date.format('YYYY-MM-DD') : null,
                    recharge: Boolean(values.recharge),
                    client: {
                        first_name: values.client?.first_name ?? '',
                        last_name: values.client?.last_name ?? '',
                        document: values.client?.document ?? '',
                        phone: values.client?.phone ?? '',
                        email: values.client?.email ?? '',
                        address: {
                            street: values.client?.address?.street ?? '',
                            number: values.client?.address?.number ?? '',
                            complement: values.client?.address?.complement ?? '',
                            neighborhood: values.client?.address?.neighborhood ?? '',
                            city: values.client?.address?.city ?? '',
                            state: values.client?.address?.state ?? '',
                            zip_code: values.client?.address?.zip_code ?? '',
                        },
                    },
                    instruction: {
                        booklet: Boolean(values.instruction?.booklet),
                        description: values.instruction?.description ?? '',
                        late_fee: {
                            amount: values.instruction?.late_fee?.amount ?? '',
                        },
                        interest: {
                            amount: values.instruction?.interest?.amount ?? '',
                        },
                        discount: {
                            amount: values.instruction?.discount?.amount ?? '',
                            limit_date: values.instruction?.discount?.limit_date
                                ? values.instruction.discount.limit_date.format('YYYY-MM-DD')
                                : null,
                        },
                    },
                }),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.success === false) {
                throw new Error(
                    result.message ||
                        getFirstValidationError(result.errors) ||
                        'Não foi possível criar o boleto.',
                );
            }

            setFeedback({
                type: 'success',
                message: result.message ?? 'Boleto criado com sucesso.',
            });
            scrollToTop();

            window.setTimeout(() => {
                window.location.reload();
            }, 6000);
        } catch (error) {
            setFeedback({
                type: 'error',
                message: error.message || 'Falha ao criar o boleto.',
            });
            scrollToTop();
        } finally {
            setSubmitting(false);
        }
    }

    async function cancelBoleto(record) {
        try {
            setCancelingBoletoCode(record.code);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(`/api/spa/cobranca/boleto/${record.code}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Não foi possível cancelar o boleto.');
            }

            setFeedback({
                type: 'success',
                message: result.message ?? 'Boleto cancelado com sucesso.',
            });
            await refreshOverview();
        } catch (error) {
            setFeedback({
                type: 'error',
                message: error.message || 'Falha ao cancelar o boleto.',
            });
            scrollToTop();
        } finally {
            setCancelingBoletoCode(null);
        }
    }

    function openBoletoDetails(record) {
        navigate(`/cobranca/boleto/${record.code}`);
    }

    const columns = [
        {
            title: 'Cliente',
            dataIndex: 'title',
            width: 240,
            render: (value) => <Typography.Text strong className="spa-pix-row-title">{value}</Typography.Text>,
        },
        {
            title: 'Valor',
            dataIndex: 'amount',
            width: 160,
            render: (value, record) => (
                <Space direction="vertical" size={2} className="spa-pix-amount-cell">
                    <Typography.Text strong className="spa-pix-amount-value">
                        {value}
                    </Typography.Text>
                    <Typography.Text type="secondary" className="spa-pix-fee-value">
                        Taxa: {record.fee}
                    </Typography.Text>
                </Space>
            ),
        },
        {
            title: 'Data',
            dataIndex: 'created_at',
            width: 180,
        },
        {
            title: 'Status',
            dataIndex: 'status',
            width: 140,
            render: (value) => <Tag color={statusTone(value)}>{value}</Tag>,
        },
        {
            title: '',
            key: 'actions',
            width: 170,
            render: (_, record) => (
                <Space size={8} wrap>
                    <Button
                        size="middle"
                        icon={<EyeOutlined />}
                        className="spa-pix-action-button spa-pix-action-button-view"
                        onClick={() => openBoletoDetails(record)}
                        title="Ver detalhes"
                        aria-label="Ver detalhes"
                    />
                    <Button
                        size="middle"
                        icon={<CopyOutlined />}
                        className="spa-pix-action-button spa-pix-action-button-copy"
                        onClick={() => copyText(record.pdf_url || record.boleto_url || '')}
                        title="Copiar PDF do boleto"
                        aria-label="Copiar PDF do boleto"
                    />
                    {['Pago', 'Aprovado', 'Cancelado', 'Falha', 'Estornado'].includes(record.status) ? null : (
                        <Button
                            size="middle"
                            icon={<StopOutlined />}
                            className="spa-pix-action-button spa-pix-action-button-cancel"
                            loading={cancelingBoletoCode === record.code}
                            onClick={() => cancelBoleto(record)}
                            title="Cancelar"
                            aria-label="Cancelar"
                        />
                    )}
                </Space>
            ),
        },
    ];

    return (
        <Row gutter={[20, 20]} className="spa-board spa-pix-board">
            <Col xs={24} xl={24}>
                <Card className="spa-table-card spa-pix-card">
                    <Space direction="vertical" size={18} className="spa-pix-stack">
                        {feedback ? (
                            <Alert
                                showIcon
                                type={feedback.type}
                                message={feedback.message}
                                icon={feedback.type === 'success' ? <CheckCircleOutlined /> : <ExclamationCircleOutlined />}
                                closable
                                onClose={() => setFeedback(null)}
                            />
                        ) : null}

                        <div className="spa-pix-page-header">
                            <Button
                                htmlType="button"
                                onClick={() => setFormVisible((current) => !current)}
                                aria-expanded={formVisible}
                                className="spa-pix-collapse-label-badge spa-pix-page-toggle-button"
                            >
                                <FileTextOutlined />
                                <span>Gerar Boleto</span>
                            </Button>

                        </div>

                        {formVisible ? (
                            <div className="spa-pix-form-panel">
                                <Form
                                    form={form}
                                    layout="vertical"
                                    requiredMark={false}
                                    initialValues={boletoInitialValues}
                                    onFinish={handleSubmit}
                                    className="spa-pix-form"
                                >
                                    <Row gutter={[16, 16]}>
                                        <Col xs={24} md={8}>
                                            <Form.Item
                                                label="Valor do boleto"
                                                name="amount"
                                                rules={[{ required: true, message: 'Informe o valor do boleto.' }]}
                                            >
                                                <MoneyInputField size="large" placeholder="0,00" />
                                            </Form.Item>
                                        </Col>
                                        <Col xs={24} md={8}>
                                            <Form.Item
                                                label="Vencimento"
                                                name="expiration"
                                                rules={[{ required: true, message: 'Informe o vencimento.' }]}
                                            >
                                                <DatePicker
                                                    size="large"
                                                    style={{ width: '100%' }}
                                                    format="DD/MM/YYYY"
                                                    onChange={handleExpirationChange}
                                                />
                                            </Form.Item>
                                        </Col>
                                        <Col xs={24} md={8}>
                                            <Form.Item
                                                label="Data limite para pagamento"
                                                name="payment_limit_date"
                                            >
                                                <DatePicker size="large" style={{ width: '100%' }} format="DD/MM/YYYY" />
                                            </Form.Item>
                                        </Col>
                                    </Row>

                                    <Card className="spa-pix-subcard" bordered={false}>
                                        <Typography.Text className="spa-pix-section-label">
                                            Dados do cliente
                                        </Typography.Text>

                                        <Row gutter={[16, 16]}>
                                            <Col xs={24} md={12}>
                                                <Form.Item
                                                    label="CPF/CNPJ"
                                                    name={['client', 'document']}
                                                    normalize={formatDocument}
                                                    rules={[
                                                        { required: true, message: 'Informe o documento.' },
                                                        { validator: documentValidator },
                                                    ]}
                                                >
                                                    <Input
                                                        size="large"
                                                        placeholder="000.000.000-00"
                                                        maxLength={18}
                                                        inputMode="numeric"
                                                        onBlur={handleDocumentBlur}
                                                    />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={12}>
                                                <Form.Item
                                                    label="Telefone"
                                                    name={['client', 'phone']}
                                                    normalize={formatPhone}
                                                    rules={[{ required: true, message: 'Informe o telefone.' }]}
                                                >
                                                    <Input
                                                        size="large"
                                                        placeholder="(11) 99999-9999"
                                                        maxLength={15}
                                                        inputMode="numeric"
                                                    />
                                                </Form.Item>
                                            </Col>
                                        </Row>

                                        <Row gutter={[16, 16]}>
                                            <Col xs={24} md={12}>
                                                <Form.Item
                                                    label="Nome"
                                                    name={['client', 'first_name']}
                                                    rules={[{ required: true, message: 'Informe o nome.' }]}
                                                >
                                                    <Input size="large" placeholder="Nome" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={12}>
                                                <Form.Item
                                                    label="Sobrenome"
                                                    name={['client', 'last_name']}
                                                    rules={[{ required: true, message: 'Informe o sobrenome.' }]}
                                                >
                                                    <Input size="large" placeholder="Sobrenome" />
                                                </Form.Item>
                                            </Col>
                                        </Row>

                                        <Row gutter={[16, 16]}>
                                            <Col xs={24}>
                                                <Form.Item
                                                    label="Email"
                                                    name={['client', 'email']}
                                                    rules={[{ required: true, message: 'Informe o email.' }]}
                                                >
                                                    <Input size="large" placeholder="email@exemplo.com" />
                                                </Form.Item>
                                            </Col>
                                        </Row>

                                        <Row gutter={[16, 16]}>
                                            <Col xs={24} md={12}>
                                                <Form.Item
                                                    label="CEP"
                                                    name={['client', 'address', 'zip_code']}
                                                    rules={[{ required: true, message: 'Informe o CEP.' }]}
                                                    normalize={formatZipcode}
                                                >
                                                    <Input size="large" placeholder="00000-000" onBlur={handleZipcodeBlur} inputMode="numeric" maxLength={9} />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={12}>
                                                <Form.Item
                                                    label="Estado"
                                                    name={['client', 'address', 'state']}
                                                    rules={[{ required: true, message: 'Selecione o estado.' }]}
                                                >
                                                    <Select size="large" options={stateOptions} placeholder="UF" />
                                                </Form.Item>
                                            </Col>
                                        </Row>

                                        <Row gutter={[16, 16]}>
                                            <Col xs={24} md={18}>
                                                <Form.Item
                                                    label="Rua"
                                                    name={['client', 'address', 'street']}
                                                    rules={[{ required: true, message: 'Informe a rua.' }]}
                                                >
                                                    <Input size="large" placeholder="Nome da rua" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={6}>
                                                <Form.Item
                                                    label="Número"
                                                    name={['client', 'address', 'number']}
                                                    rules={[{ required: true, message: 'Informe o número.' }]}
                                                >
                                                    <Input size="large" placeholder="123" />
                                                </Form.Item>
                                            </Col>
                                        </Row>

                                        <Row gutter={[16, 16]}>
                                            <Col xs={24} md={8}>
                                                <Form.Item label="Complemento" name={['client', 'address', 'complement']}>
                                                    <Input size="large" placeholder="Apto 101" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={8}>
                                                <Form.Item
                                                    label="Bairro"
                                                    name={['client', 'address', 'neighborhood']}
                                                    rules={[{ required: true, message: 'Informe o bairro.' }]}
                                                >
                                                    <Input size="large" placeholder="Centro" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={8}>
                                                <Form.Item
                                                    label="Cidade"
                                                    name={['client', 'address', 'city']}
                                                    rules={[{ required: true, message: 'Informe a cidade.' }]}
                                                >
                                                    <Input size="large" placeholder="Nome da cidade" />
                                                </Form.Item>
                                            </Col>
                                        </Row>

                                    </Card>

                                    <Card className="spa-pix-subcard" bordered={false}>
                                        <Typography.Text className="spa-pix-section-label">
                                            Instruções do boleto
                                        </Typography.Text>

                                        <Row gutter={[16, 16]}>
                                            <Col xs={24} md={12}>
                                                <Form.Item
                                                    label="Descrição"
                                                    name={['instruction', 'description']}
                                                >
                                                    <Input size="large" placeholder="Descrição do boleto" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={12}>
                                                <Form.Item
                                                    label="Recarga"
                                                    name="recharge"
                                                    valuePropName="checked"
                                                >
                                                    <Select
                                                        size="large"
                                                        options={[
                                                            { label: 'Não', value: false },
                                                            { label: 'Sim', value: true },
                                                        ]}
                                                        placeholder="Não"
                                                    />
                                                </Form.Item>
                                            </Col>
                                        </Row>

                                        <Row gutter={[16, 16]}>
                                            <Col xs={24} md={8}>
                                                <Form.Item
                                                    label="Multa (%)"
                                                    name={['instruction', 'late_fee', 'amount']}
                                                    rules={[{ required: true, message: 'Informe a multa.' }]}
                                                >
                                                    <Input size="large" placeholder="2,00" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={8}>
                                                <Form.Item
                                                    label="Juros (%)"
                                                    name={['instruction', 'interest', 'amount']}
                                                    rules={[{ required: true, message: 'Informe os juros.' }]}
                                                >
                                                    <Input size="large" placeholder="1,00" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={8}>
                                                <Form.Item
                                                    label="Desconto (%)"
                                                    name={['instruction', 'discount', 'amount']}
                                                    rules={[{ required: true, message: 'Informe o desconto.' }]}
                                                >
                                                    <Input size="large" placeholder="5,00" />
                                                </Form.Item>
                                            </Col>
                                        </Row>

                                        <Row gutter={[16, 16]}>
                                            <Col xs={24} md={12}>
                                                <Form.Item
                                                    label="Limite do desconto"
                                                    name={['instruction', 'discount', 'limit_date']}
                                                    rules={[{ required: true, message: 'Informe o limite do desconto.' }]}
                                                >
                                                    <DatePicker size="large" style={{ width: '100%' }} format="DD/MM/YYYY" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={12}>
                                                <Form.Item
                                                    label="Boletim"
                                                    name={['instruction', 'booklet']}
                                                    valuePropName="checked"
                                                >
                                                    <Select
                                                        size="large"
                                                        options={[
                                                            { label: 'Não', value: false },
                                                            { label: 'Sim', value: true },
                                                        ]}
                                                        placeholder="Não"
                                                    />
                                                </Form.Item>
                                            </Col>
                                        </Row>

                                        <div className="spa-pix-actions">
                                            <Button onClick={() => setFormVisible(false)}>Fechar</Button>
                                            <Button
                                                type="primary"
                                                htmlType="submit"
                                                loading={submitting}
                                                icon={<SendOutlined />}
                                                className="spa-primary-button"
                                            >
                                                Criar boleto
                                            </Button>
                                        </div>
                                    </Card>
                                </Form>
                            </div>
                        ) : null}

                        <Card
                            className="spa-pix-table-card"
                            title={tableTitle}
                            extra={
                                <Select
                                    className="spa-period-select"
                                    value={selectedPeriod}
                                    options={periodOptions}
                                    onChange={(value) => refreshOverview(value)}
                                    size="middle"
                                    style={{ minWidth: 176, width: 'auto' }}
                                />
                            }
                            bordered={false}
                        >
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 6 }} />
                            ) : boletoRows.length === 0 ? (
                                <Empty description="Nenhum boleto encontrado" />
                            ) : (
                                <Table
                                    rowKey="code"
                                    columns={columns}
                                    dataSource={boletoRows}
                                    pagination={tablePagination}
                                    className="spa-table spa-pix-transactions-table"
                                    rowClassName={() => 'spa-pix-table-row'}
                                />
                            )}
                        </Card>
                    </Space>
                </Card>
            </Col>

        </Row>
    );
}

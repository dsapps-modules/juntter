import {
    BankOutlined,
    CheckCircleOutlined,
    CopyOutlined,
    DownloadOutlined,
    ExclamationCircleOutlined,
    FileTextOutlined,
    LinkOutlined,
} from '@ant-design/icons';
import {
    Alert,
    Button,
    Card,
    Col,
    Collapse,
    DatePicker,
    Divider,
    Empty,
    Form,
    Input,
    Modal,
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

const booleanOptions = [
    { label: 'Não', value: false },
    { label: 'Sim', value: true },
];

const defaultOverview = {
    rows: [],
    link_rows: [],
    periods: [],
    selected_period: 'all',
};

const boletoInitialValues = {
    amount: '0,00',
    expiration: null,
    payment_limit_date: null,
    recharge: false,
    client: {
        first_name: '',
        last_name: '',
        document: '',
        email: '',
        address: {
            zip_code: '',
            street: '',
            number: '',
            complement: '',
            neighborhood: '',
            city: '',
            state: '',
        },
    },
    instruction: {
        booklet: false,
        description: '',
        late_fee: {
            amount: '2,00',
        },
        interest: {
            amount: '1,00',
        },
        discount: {
            amount: '5,00',
            limit_date: null,
        },
    },
};

const linkInitialValues = {
    descricao: '',
    valor: '',
    juros: 'CLIENT',
    data_expiracao: null,
    data_vencimento: null,
    data_limite_pagamento: null,
    dados_cliente_preenchidos: {
        nome: '',
        sobrenome: '',
        email: '',
        telefone: '',
        documento: '',
        endereco: {
            rua: '',
            numero: '',
            bairro: '',
            cidade: '',
            estado: '',
            cep: '',
            complemento: '',
        },
    },
    instrucoes_boleto: {
        description: '',
        late_fee: { amount: '' },
        interest: { amount: '' },
        discount: { amount: '', limit_date: null },
    },
};

function getCurrentPeriod() {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

function formatPeriodLabel(period) {
    const [year, month] = period.split('-');

    return `${month}/${year}`;
}

function getFirstValidationError(errors) {
    return Object.values(errors ?? {}).flat().find(Boolean) ?? '';
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

function formatBoletoStatus(status) {
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
    switch (formatBoletoStatus(status)) {
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

function copyTextToClipboard(text, successMessage, errorMessage) {
    if (!text) {
        return Promise.resolve();
    }

    return navigator.clipboard.writeText(text).then(
        () => message.success(successMessage),
        () => message.error(errorMessage),
    );
}

export default function CobrancaBoletoPage() {
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const [linkForm] = Form.useForm();
    const currentPeriod = getCurrentPeriod();

    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [linkSubmitting, setLinkSubmitting] = useState(false);
    const [formVisible, setFormVisible] = useState(false);
    const [linkModalOpen, setLinkModalOpen] = useState(false);
    const [feedback, setFeedback] = useState(null);
    const [boletoResult, setBoletoResult] = useState(null);
    const [resultModalOpen, setResultModalOpen] = useState(false);
    const [selectedPeriod, setSelectedPeriod] = useState(currentPeriod);
    const [reloadToken, setReloadToken] = useState(0);
    const [overview, setOverview] = useState(defaultOverview);

    useEffect(() => {
        const controller = new AbortController();

        async function loadOverview() {
            setLoading(true);

            try {
                const params = new URLSearchParams();
                params.set('period', selectedPeriod);

                const response = await fetch(`/api/spa/cobranca${params.toString() !== '' ? `?${params.toString()}` : ''}`, {
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
                    link_rows: data.link_rows ?? [],
                    periods: data.periods ?? current.periods,
                    selected_period: data.selected_period ?? current.selected_period,
                }));
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setFeedback({
                        type: 'error',
                        message: fetchError.message || 'Falha ao carregar os boletos.',
                    });
                }
            } finally {
                setLoading(false);
            }
        }

        loadOverview();

        return () => controller.abort();
    }, [reloadToken, selectedPeriod]);

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
            .filter((row) => row.type === 'Boleto' || row.type === 'BOLETO')
            .sort((left, right) => (right.created_at_sort ?? 0) - (left.created_at_sort ?? 0));
    }, [overview.rows]);

    const boletoSummary = useMemo(() => {
        const totalAmount = boletoRows.reduce((sum, row) => sum + Math.round(parseCurrencyValue(row.amount) * 100), 0);

        return {
            total: boletoRows.length,
            paid: boletoRows.filter((row) => row.status === 'Pago').length,
            pending: boletoRows.filter((row) => ['Pendente', 'Processando'].includes(row.status)).length,
            failed: boletoRows.filter((row) => ['Falha', 'Cancelado', 'Estornado'].includes(row.status)).length,
            amount: formatCurrency(totalAmount),
        };
    }, [boletoRows]);

    const recentBoletos = boletoRows.slice(0, 5);
    const boletoTableTitle = selectedPeriod === 'all'
        ? 'Boletos de todos os meses'
        : `Boletos do mês ${formatPeriodLabel(selectedPeriod)}`;

    const columns = [
        {
            title: 'Boleto',
            dataIndex: 'title',
            render: (_, record) => (
                <Space align="start" size={10} className="spa-pix-title-cell">
                    <span
                        aria-hidden="true"
                        style={{
                            display: 'inline-block',
                            width: 8,
                            height: 8,
                            marginTop: 8,
                            borderRadius: 999,
                            flex: '0 0 auto',
                            backgroundColor: record.raw_status ? '#22c55e' : '#d1d5db',
                        }}
                    />
                    <Space direction="vertical" size={2}>
                        <Typography.Text strong className="spa-pix-row-title">
                            {record.title}
                        </Typography.Text>
                        <Typography.Text type="secondary" className="spa-pix-row-subtitle">
                            {record.description}
                        </Typography.Text>
                    </Space>
                </Space>
            ),
            width: 320,
        },
        {
            title: 'Valor',
            dataIndex: 'amount',
            render: (value) => <Typography.Text strong className="spa-pix-amount-value">{value}</Typography.Text>,
            width: 160,
        },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (value) => <Tag color={statusTone(value)}>{value}</Tag>,
            width: 140,
        },
        {
            title: 'Data',
            dataIndex: 'created_at',
            width: 180,
        },
    ];

    function refreshOverview(nextPeriod = selectedPeriod) {
        if (nextPeriod === selectedPeriod) {
            setReloadToken((current) => current + 1);
            return;
        }

        setSelectedPeriod(nextPeriod);
    }

    function resetBoletoForm() {
        form.resetFields();
        form.setFieldsValue(boletoInitialValues);
        setFeedback(null);
        setBoletoResult(null);
        setResultModalOpen(false);
    }

    function handleToggleForm() {
        setFormVisible((current) => !current);
    }

    function openLinkModal() {
        linkForm.resetFields();
        linkForm.setFieldsValue(linkInitialValues);
        setLinkModalOpen(true);
    }

    function closeLinkModal() {
        setLinkModalOpen(false);
        linkForm.resetFields();
    }

    function closeResultModal() {
        setResultModalOpen(false);
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
                        email: values.client?.email ?? '',
                        address: {
                            zip_code: values.client?.address?.zip_code ?? '',
                            street: values.client?.address?.street ?? '',
                            number: values.client?.address?.number ?? '',
                            complement: values.client?.address?.complement ?? '',
                            neighborhood: values.client?.address?.neighborhood ?? '',
                            city: values.client?.address?.city ?? '',
                            state: values.client?.address?.state ?? '',
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

            setBoletoResult(result.boleto_data ?? null);
            setFeedback({
                type: 'success',
                message: result.message ?? 'Boleto criado com sucesso.',
            });
            setResultModalOpen(Boolean(result.boleto_data));
            await refreshOverview();

            if (!result.boleto_data) {
                resetBoletoForm();
            }
        } catch (error) {
            setFeedback({
                type: 'error',
                message: error.message || 'Falha ao criar o boleto.',
            });
        } finally {
            setSubmitting(false);
        }
    }

    async function handleLinkSubmit(values) {
        setLinkSubmitting(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/links-pagamento-boleto', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    descricao: values.descricao,
                    valor: values.valor,
                    juros: values.juros,
                    data_expiracao: values.data_expiracao ? values.data_expiracao.format('YYYY-MM-DD') : null,
                    data_vencimento: values.data_vencimento ? values.data_vencimento.format('YYYY-MM-DD') : null,
                    data_limite_pagamento: values.data_limite_pagamento
                        ? values.data_limite_pagamento.format('YYYY-MM-DD')
                        : null,
                    dados_cliente_preenchidos: {
                        nome: values.dados_cliente_preenchidos?.nome ?? '',
                        sobrenome: values.dados_cliente_preenchidos?.sobrenome ?? '',
                        email: values.dados_cliente_preenchidos?.email ?? '',
                        telefone: values.dados_cliente_preenchidos?.telefone ?? '',
                        documento: values.dados_cliente_preenchidos?.documento ?? '',
                        endereco: {
                            rua: values.dados_cliente_preenchidos?.endereco?.rua ?? '',
                            numero: values.dados_cliente_preenchidos?.endereco?.numero ?? '',
                            bairro: values.dados_cliente_preenchidos?.endereco?.bairro ?? '',
                            cidade: values.dados_cliente_preenchidos?.endereco?.cidade ?? '',
                            estado: values.dados_cliente_preenchidos?.endereco?.estado ?? '',
                            cep: values.dados_cliente_preenchidos?.endereco?.cep ?? '',
                            complemento: values.dados_cliente_preenchidos?.endereco?.complemento ?? '',
                        },
                    },
                    instrucoes_boleto: {
                        description: values.instrucoes_boleto?.description ?? '',
                        late_fee: {
                            amount: values.instrucoes_boleto?.late_fee?.amount ?? '',
                        },
                        interest: {
                            amount: values.instrucoes_boleto?.interest?.amount ?? '',
                        },
                        discount: {
                            amount: values.instrucoes_boleto?.discount?.amount ?? '',
                            limit_date: values.instrucoes_boleto?.discount?.limit_date
                                ? values.instrucoes_boleto.discount.limit_date.format('YYYY-MM-DD')
                                : null,
                        },
                    },
                }),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = Object.values(result.errors ?? {}).flat().shift();
                throw new Error(firstError ?? result.message ?? 'Não foi possível salvar o link.');
            }

            message.success(result.message ?? 'Link de pagamento Boleto criado com sucesso.');
            closeLinkModal();
            await refreshOverview();
        } catch (error) {
            message.error(error.message || 'Falha ao salvar o link.');
        } finally {
            setLinkSubmitting(false);
        }
    }

    async function copyBoletoCode() {
        const boletoCode = boletoResult?.boleto_digitable_line || boletoResult?.boleto_barcode || '';

        if (!boletoCode) {
            return;
        }

        await copyTextToClipboard(boletoCode, 'Linha digitável copiada.', 'Não foi possível copiar a linha digitável.');
    }

    function openBoletoUrl() {
        if (!boletoResult?.boleto_url) {
            return;
        }

        window.open(boletoResult.boleto_url, '_blank', 'noopener,noreferrer');
    }

    return (
        <Row gutter={[20, 20]} className="spa-board spa-pix-board">
            <Col xs={24} xl={16}>
                <Card className="spa-table-card spa-pix-card">
                    <Space direction="vertical" size={18} className="spa-pix-stack">
                        {feedback ? (
                            <Alert
                                showIcon
                                type={feedback.type}
                                message={feedback.message}
                                icon={feedback.type === 'success' ? <CheckCircleOutlined /> : <ExclamationCircleOutlined />}
                            />
                        ) : null}

                        <div className="spa-pix-page-header">
                            <Button
                                htmlType="button"
                                onClick={handleToggleForm}
                                aria-expanded={formVisible}
                                className="spa-pix-collapse-label-badge spa-pix-page-toggle-button"
                            >
                                <FileTextOutlined />
                                <span>Gerar Boleto</span>
                            </Button>

                            <Button
                                htmlType="button"
                                onClick={openLinkModal}
                                className="spa-pix-collapse-label-badge spa-pix-page-link-button"
                            >
                                <LinkOutlined />
                                <span>Link de Pagamento</span>
                            </Button>
                        </div>

                        <Collapse
                            activeKey={formVisible ? ['boleto-form'] : []}
                            onChange={(keys) => setFormVisible(Array.isArray(keys) ? keys.includes('boleto-form') : keys === 'boleto-form')}
                            expandIcon={() => null}
                            ghost
                            className="spa-pix-collapse spa-boleto-collapse"
                            items={[
                                {
                                    key: 'boleto-form',
                                    label: <span className="spa-pix-collapse-label" />,
                                    children: (
                                        <div className="spa-pix-form-panel">
                                            <Form
                                                form={form}
                                                layout="vertical"
                                                requiredMark={false}
                                                initialValues={boletoInitialValues}
                                                onFinish={handleSubmit}
                                                className="spa-pix-form"
                                                onValuesChange={(_, allValues) => {
                                                    const expiration = allValues.expiration;
                                                    const paymentLimit = allValues.payment_limit_date;
                                                    const discountLimit = allValues.instruction?.discount?.limit_date;
                                                    const nextFields = {};

                                                    if (expiration) {
                                                        if (!paymentLimit || paymentLimit.valueOf() <= expiration.valueOf()) {
                                                            nextFields.payment_limit_date = expiration.add(1, 'day');
                                                        }

                                                        if (!discountLimit || discountLimit.valueOf() >= expiration.valueOf()) {
                                                            nextFields.instruction = {
                                                                ...(allValues.instruction ?? {}),
                                                                discount: {
                                                                    ...(allValues.instruction?.discount ?? {}),
                                                                    limit_date: expiration.subtract(1, 'day'),
                                                                },
                                                            };
                                                        }
                                                    }

                                                    if (Object.keys(nextFields).length > 0) {
                                                        form.setFieldsValue(nextFields);
                                                    }
                                                }}
                                            >
                                                <Row gutter={[16, 16]}>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item
                                                            label="Valor do boleto"
                                                            name="amount"
                                                            rules={[{ required: true, message: 'Informe o valor do boleto.' }]}
                                                        >
                                                            <Input size="large" placeholder="0,00" inputMode="decimal" />
                                                        </Form.Item>
                                                    </Col>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item
                                                            label="Data de vencimento"
                                                            name="expiration"
                                                            rules={[{ required: true, message: 'Informe a data de vencimento.' }]}
                                                        >
                                                            <DatePicker size="large" style={{ width: '100%' }} format="DD/MM/YYYY" placeholder="dd/mm/aaaa" />
                                                        </Form.Item>
                                                    </Col>
                                                </Row>

                                                <Row gutter={[16, 16]}>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item label="Data limite para pagamento" name="payment_limit_date">
                                                            <DatePicker size="large" style={{ width: '100%' }} format="DD/MM/YYYY" placeholder="dd/mm/aaaa" />
                                                        </Form.Item>
                                                        <Typography.Text type="secondary">
                                                            Opcional - Data limite após o vencimento
                                                        </Typography.Text>
                                                    </Col>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item
                                                            label="É para recarga?"
                                                            name="recharge"
                                                            rules={[{ required: true, message: 'Informe se o boleto é para recarga.' }]}
                                                        >
                                                            <Select size="large" options={booleanOptions} placeholder="Não" />
                                                        </Form.Item>
                                                        <Typography.Text type="secondary">
                                                            Opcional - Para carteiras digitais
                                                        </Typography.Text>
                                                    </Col>
                                                </Row>

                                                <Card className="spa-pix-subcard" bordered={false}>
                                                    <Typography.Text className="spa-pix-section-label">
                                                        Dados do cliente <span style={{ color: '#ef4444' }}>(obrigatório)</span>
                                                    </Typography.Text>

                                                    <Row gutter={[16, 16]}>
                                                        <Col xs={24} md={12}>
                                                            <Form.Item
                                                                label="Nome do cliente"
                                                                name={['client', 'first_name']}
                                                                rules={[{ required: true, message: 'Informe o nome do cliente.' }]}
                                                            >
                                                                <Input size="large" placeholder="Nome completo" />
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
                                                        <Col xs={24} md={12}>
                                                            <Form.Item
                                                                label="CPF/CNPJ"
                                                                name={['client', 'document']}
                                                                rules={[{ required: true, message: 'Informe o CPF ou CNPJ.' }]}
                                                            >
                                                                <Input size="large" placeholder="000.000.000-00" />
                                                            </Form.Item>
                                                        </Col>
                                                        <Col xs={24} md={12}>
                                                            <Form.Item
                                                                label="Email"
                                                                name={['client', 'email']}
                                                                rules={[
                                                                    { required: true, message: 'Informe o email do cliente.' },
                                                                    { type: 'email', message: 'Informe um email válido.' },
                                                                ]}
                                                            >
                                                                <Input size="large" placeholder="email@exemplo.com" />
                                                            </Form.Item>
                                                        </Col>
                                                    </Row>
                                                </Card>

                                                <Card className="spa-pix-subcard" bordered={false}>
                                                    <Typography.Text className="spa-pix-section-label">
                                                        Endereço do cliente <span style={{ color: '#ef4444' }}>(obrigatório)</span>
                                                    </Typography.Text>

                                                    <Row gutter={[16, 16]}>
                                                        <Col xs={24} md={6}>
                                                            <Form.Item
                                                                label="CEP"
                                                                name={['client', 'address', 'zip_code']}
                                                                rules={[{ required: true, message: 'Informe o CEP.' }]}
                                                            >
                                                                <Input size="large" placeholder="00000-000" />
                                                            </Form.Item>
                                                        </Col>
                                                        <Col xs={24} md={18}>
                                                            <Form.Item
                                                                label="Rua"
                                                                name={['client', 'address', 'street']}
                                                                rules={[{ required: true, message: 'Informe a rua.' }]}
                                                            >
                                                                <Input size="large" placeholder="Nome da rua" />
                                                            </Form.Item>
                                                        </Col>
                                                    </Row>

                                                    <Row gutter={[16, 16]}>
                                                        <Col xs={24} md={8}>
                                                            <Form.Item
                                                                label="Número"
                                                                name={['client', 'address', 'number']}
                                                                rules={[{ required: true, message: 'Informe o número.' }]}
                                                            >
                                                                <Input size="large" placeholder="123" />
                                                            </Form.Item>
                                                        </Col>
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
                                                    </Row>

                                                    <Row gutter={[16, 16]}>
                                                        <Col xs={24} md={16}>
                                                            <Form.Item
                                                                label="Cidade"
                                                                name={['client', 'address', 'city']}
                                                                rules={[{ required: true, message: 'Informe a cidade.' }]}
                                                            >
                                                                <Input size="large" placeholder="Nome da cidade" />
                                                            </Form.Item>
                                                        </Col>
                                                        <Col xs={24} md={8}>
                                                            <Form.Item
                                                                label="Estado"
                                                                name={['client', 'address', 'state']}
                                                                rules={[{ required: true, message: 'Selecione o estado.' }]}
                                                            >
                                                                <Select size="large" options={stateOptions} placeholder="Selecione..." />
                                                            </Form.Item>
                                                        </Col>
                                                    </Row>
                                                </Card>

                                                <Card className="spa-pix-subcard" bordered={false}>
                                                    <Typography.Text className="spa-pix-section-label">
                                                        Instruções do boleto <span style={{ color: '#ef4444' }}>(obrigatório)</span>
                                                    </Typography.Text>

                                                    <Row gutter={[16, 16]}>
                                                        <Col xs={24} md={12}>
                                                            <Form.Item
                                                                label="É carnê?"
                                                                name={['instruction', 'booklet']}
                                                                rules={[{ required: true, message: 'Informe se o boleto é carnê.' }]}
                                                            >
                                                                <Select size="large" options={booleanOptions} placeholder="Não" />
                                                            </Form.Item>
                                                        </Col>
                                                        <Col xs={24} md={12}>
                                                            <Form.Item
                                                                label="Descrição"
                                                                name={['instruction', 'description']}
                                                                rules={[{ required: true, message: 'Informe a descrição do boleto.' }]}
                                                            >
                                                                <Input size="large" placeholder="Descrição do boleto" />
                                                            </Form.Item>
                                                            <Typography.Text type="secondary">Descrição exibida no boleto</Typography.Text>
                                                        </Col>
                                                    </Row>

                                                    <Row gutter={[16, 16]}>
                                                        <Col xs={24} md={8}>
                                                            <Form.Item
                                                                label="Multa por atraso"
                                                                name={['instruction', 'late_fee', 'amount']}
                                                                rules={[{ required: true, message: 'Informe a multa por atraso.' }]}
                                                            >
                                                                <Input size="large" placeholder="2,00" addonAfter="%" />
                                                            </Form.Item>
                                                            <Typography.Text type="secondary">Ex: 2,00 para 2%</Typography.Text>
                                                        </Col>
                                                        <Col xs={24} md={8}>
                                                            <Form.Item
                                                                label="Juros ao mês"
                                                                name={['instruction', 'interest', 'amount']}
                                                                rules={[{ required: true, message: 'Informe os juros ao mês.' }]}
                                                            >
                                                                <Input size="large" placeholder="1,00" addonAfter="%" />
                                                            </Form.Item>
                                                            <Typography.Text type="secondary">Ex: 1,00 para 1%</Typography.Text>
                                                        </Col>
                                                        <Col xs={24} md={8}>
                                                            <Form.Item
                                                                label="Desconto"
                                                                name={['instruction', 'discount', 'amount']}
                                                                rules={[{ required: true, message: 'Informe o desconto.' }]}
                                                            >
                                                                <Input size="large" placeholder="5,00" addonAfter="%" />
                                                            </Form.Item>
                                                            <Typography.Text type="secondary">Ex: 5,00 para 5%</Typography.Text>
                                                        </Col>
                                                    </Row>

                                                    <Row gutter={[16, 16]}>
                                                        <Col xs={24}>
                                                            <Form.Item
                                                                label="Data limite para desconto"
                                                                name={['instruction', 'discount', 'limit_date']}
                                                                rules={[{ required: true, message: 'Informe a data limite para desconto.' }]}
                                                            >
                                                                <DatePicker
                                                                    size="large"
                                                                    style={{ width: '100%' }}
                                                                    format="DD/MM/YYYY"
                                                                    placeholder="dd/mm/aaaa"
                                                                />
                                                            </Form.Item>
                                                        </Col>
                                                    </Row>

                                                    <div className="spa-pix-actions">
                                                        <Button onClick={navigate.bind(null, '/cobranca')} className="spa-secondary-button">
                                                            Fechar
                                                        </Button>
                                                        <Button
                                                            type="primary"
                                                            htmlType="submit"
                                                            loading={submitting}
                                                            icon={<FileTextOutlined />}
                                                            className="spa-primary-button"
                                                        >
                                                            Criar boleto
                                                        </Button>
                                                    </div>
                                                </Card>
                                            </Form>
                                        </div>
                                    ),
                                },
                            ]}
                        />

                        <Card
                            className="spa-pix-table-card"
                            title={boletoTableTitle}
                            extra={
                                <Select
                                    value={selectedPeriod}
                                    options={periodOptions}
                                    onChange={(value) => setSelectedPeriod(value)}
                                    className="spa-period-select"
                                    style={{ width: 240 }}
                                    aria-label="Filtrar por mês e ano"
                                />
                            }
                        >
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 6 }} />
                            ) : boletoRows.length === 0 ? (
                                <Empty description="Nenhum boleto encontrado" />
                            ) : (
                                <Table
                                    rowKey="id"
                                    columns={columns}
                                    dataSource={boletoRows}
                                    pagination={false}
                                    className="spa-table spa-pix-transactions-table"
                                    rowClassName={() => 'spa-pix-table-row'}
                                />
                            )}
                        </Card>
                    </Space>
                </Card>
            </Col>

            <Col xs={24} xl={8}>
                <Space direction="vertical" size={20} style={{ width: '100%' }}>
                    <Card
                        className="spa-quick-view-card spa-pix-sidebar-card"
                        title={(
                            <Space align="center" size={10} className="spa-pix-sidebar-title">
                                <BankOutlined className="spa-pix-sidebar-title-icon" />
                                <span>Visão rápida</span>
                            </Space>
                        )}
                        bordered={false}
                    >
                        <Space direction="vertical" size={16} style={{ width: '100%' }}>

                            <Row gutter={[12, 12]}>
                                {[
                                    ['Boletos', boletoSummary.total],
                                    ['Pagos', boletoSummary.paid],
                                    ['Pendentes', boletoSummary.pending],
                                    ['Falhas', boletoSummary.failed],
                                ].map(([label, value]) => (
                                    <Col xs={12} sm={12} key={label}>
                                        <Card size="small" bordered={false} className="spa-pix-mini-stat-card">
                                            <Typography.Text type="secondary">{label}</Typography.Text>
                                            <div>
                                                <Typography.Title level={3} style={{ marginBottom: 0 }}>
                                                    {value}
                                                </Typography.Title>
                                            </div>
                                        </Card>
                                    </Col>
                                ))}
                            </Row>

                            <Card size="small" title="Atalhos" bordered={false}>
                                <Space direction="vertical" size={10} style={{ width: '100%' }}>
                                    <Button type="primary" block onClick={handleToggleForm} className="spa-primary-button">
                                        Gerar boleto
                                    </Button>
                                    <Button block onClick={openLinkModal}>
                                        Criar link de pagamento
                                    </Button>
                                    <Button block onClick={() => navigate('/links-pagamento')}>
                                        Ver links
                                    </Button>
                                    <Button block onClick={() => refreshOverview()}>
                                        Atualizar painel
                                    </Button>
                                </Space>
                            </Card>

                            <Card size="small" title="Últimos boletos" bordered={false}>
                                {loading ? (
                                    <Skeleton active paragraph={{ rows: 3 }} />
                                ) : recentBoletos.length === 0 ? (
                                    <Empty description="Nenhum boleto recente encontrado." />
                                ) : (
                                    <Space direction="vertical" size={12} style={{ width: '100%' }}>
                                        {recentBoletos.map((item) => (
                                            <div key={item.id} className="spa-pix-side-link-item">
                                                <Space direction="vertical" size={2} style={{ width: '100%' }}>
                                                    <Typography.Text strong>{item.title}</Typography.Text>
                                                    <Typography.Text type="secondary">{item.description}</Typography.Text>
                                                </Space>
                                                <Space wrap>
                                                    <Tag color="green">{item.amount}</Tag>
                                                    <Tag color={statusTone(item.status)}>{item.status}</Tag>
                                                </Space>
                                                <Typography.Text type="secondary">{item.created_at}</Typography.Text>
                                            </div>
                                        ))}
                                    </Space>
                                )}
                            </Card>
                        </Space>
                    </Card>
                </Space>
            </Col>

            <Modal
                open={linkModalOpen}
                onCancel={closeLinkModal}
                title={null}
                footer={null}
                width={1120}
                className="spa-pix-modal"
                destroyOnClose
            >
                <Form
                    form={linkForm}
                    layout="vertical"
                    requiredMark={false}
                    initialValues={linkInitialValues}
                    onFinish={handleLinkSubmit}
                    className="spa-pix-link-form"
                >
                    <div className="spa-pix-link-modal-header">
                        <div>
                            <Typography.Title level={3} className="spa-pix-link-modal-title">
                                Link de Pagamento - Boleto
                            </Typography.Title>
                            <Typography.Text type="secondary">
                                Configure um link para seus clientes realizarem pagamentos por boleto.
                            </Typography.Text>
                        </div>
                    </div>

                    <Divider />

                    <Row gutter={[16, 16]}>
                        <Col xs={24} md={12}>
                            <Form.Item label="Descrição" name="descricao">
                                <Input size="large" placeholder="Descreva o que o cliente está pagando" />
                            </Form.Item>
                        </Col>
                        <Col xs={24} md={12}>
                            <Form.Item
                                label="Valor"
                                name="valor"
                                rules={[{ required: true, message: 'Informe o valor do link.' }]}
                            >
                                <Input size="large" placeholder="R$ 5,55" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={[16, 16]}>
                        <Col xs={24} md={12}>
                            <Form.Item
                                label="Quem paga as taxas"
                                name="juros"
                                rules={[{ required: true, message: 'Selecione quem paga as taxas.' }]}
                            >
                                <Select
                                    size="large"
                                    options={[
                                        { label: 'Cliente', value: 'CLIENT' },
                                        { label: 'Estabelecimento', value: 'ESTABLISHMENT' },
                                    ]}
                                    placeholder="Cliente"
                                />
                            </Form.Item>
                        </Col>
                        <Col xs={24} md={12}>
                            <Form.Item label="Data de expiração do link" name="data_expiracao">
                                <DatePicker
                                    size="large"
                                    style={{ width: '100%' }}
                                    placeholder="26/05/2026"
                                    format="DD/MM/YYYY"
                                />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={[16, 16]}>
                        <Col xs={24} md={12}>
                            <Form.Item label="Data de vencimento" name="data_vencimento" rules={[{ required: true, message: 'Informe o vencimento.' }]}>
                                <DatePicker size="large" style={{ width: '100%' }} placeholder="26/05/2026" format="DD/MM/YYYY" />
                            </Form.Item>
                        </Col>
                        <Col xs={24} md={12}>
                            <Form.Item label="Data limite para pagamento" name="data_limite_pagamento">
                                <DatePicker size="large" style={{ width: '100%' }} placeholder="27/05/2026" format="DD/MM/YYYY" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Card className="spa-pix-subcard" bordered={false}>
                        <Typography.Text className="spa-pix-section-label">Dados do cliente</Typography.Text>

                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={12}>
                                <Form.Item label="Nome do cliente" name={['dados_cliente_preenchidos', 'nome']} rules={[{ required: true, message: 'Informe o nome.' }]}>
                                    <Input size="large" placeholder="Nome completo" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item label="Sobrenome" name={['dados_cliente_preenchidos', 'sobrenome']} rules={[{ required: true, message: 'Informe o sobrenome.' }]}>
                                    <Input size="large" placeholder="Sobrenome" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={12}>
                                <Form.Item label="CPF/CNPJ" name={['dados_cliente_preenchidos', 'documento']} rules={[{ required: true, message: 'Informe o documento.' }]}>
                                    <Input size="large" placeholder="000.000.000-00" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item label="Email" name={['dados_cliente_preenchidos', 'email']} rules={[{ required: true, type: 'email', message: 'Informe um email válido.' }]}>
                                    <Input size="large" placeholder="email@exemplo.com" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={12}>
                                <Form.Item label="Telefone" name={['dados_cliente_preenchidos', 'telefone']} rules={[{ required: true, message: 'Informe o telefone.' }]}>
                                    <Input size="large" placeholder="(00) 00000-0000" />
                                </Form.Item>
                            </Col>
                        </Row>
                    </Card>

                    <Card className="spa-pix-subcard" bordered={false}>
                        <Typography.Text className="spa-pix-section-label">Endereço do cliente</Typography.Text>

                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={6}>
                                <Form.Item label="CEP" name={['dados_cliente_preenchidos', 'endereco', 'cep']} rules={[{ required: true, message: 'Informe o CEP.' }]}>
                                    <Input size="large" placeholder="00000-000" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={18}>
                                <Form.Item label="Rua" name={['dados_cliente_preenchidos', 'endereco', 'rua']} rules={[{ required: true, message: 'Informe a rua.' }]}>
                                    <Input size="large" placeholder="Nome da rua" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={8}>
                                <Form.Item label="Número" name={['dados_cliente_preenchidos', 'endereco', 'numero']} rules={[{ required: true, message: 'Informe o número.' }]}>
                                    <Input size="large" placeholder="123" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item label="Complemento" name={['dados_cliente_preenchidos', 'endereco', 'complemento']}>
                                    <Input size="large" placeholder="Apto 101" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item label="Bairro" name={['dados_cliente_preenchidos', 'endereco', 'bairro']} rules={[{ required: true, message: 'Informe o bairro.' }]}>
                                    <Input size="large" placeholder="Centro" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={16}>
                                <Form.Item label="Cidade" name={['dados_cliente_preenchidos', 'endereco', 'cidade']} rules={[{ required: true, message: 'Informe a cidade.' }]}>
                                    <Input size="large" placeholder="Nome da cidade" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item label="Estado" name={['dados_cliente_preenchidos', 'endereco', 'estado']} rules={[{ required: true, message: 'Selecione o estado.' }]}>
                                    <Select size="large" options={stateOptions} placeholder="Selecione..." />
                                </Form.Item>
                            </Col>
                        </Row>
                    </Card>

                    <Card className="spa-pix-subcard" bordered={false}>
                        <Typography.Text className="spa-pix-section-label">Instruções do boleto</Typography.Text>

                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={24}>
                                <Form.Item label="Descrição" name={['instrucoes_boleto', 'description']}>
                                    <Input size="large" placeholder="Descrição do boleto" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={8}>
                                <Form.Item label="Multa por atraso" name={['instrucoes_boleto', 'late_fee', 'amount']} rules={[{ required: true, message: 'Informe a multa.' }]}>
                                    <Input size="large" placeholder="2,00" addonAfter="%" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item label="Juros ao mês" name={['instrucoes_boleto', 'interest', 'amount']} rules={[{ required: true, message: 'Informe os juros.' }]}>
                                    <Input size="large" placeholder="1,00" addonAfter="%" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item label="Desconto" name={['instrucoes_boleto', 'discount', 'amount']} rules={[{ required: true, message: 'Informe o desconto.' }]}>
                                    <Input size="large" placeholder="5,00" addonAfter="%" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={[16, 16]}>
                            <Col xs={24}>
                                <Form.Item label="Data limite para desconto" name={['instrucoes_boleto', 'discount', 'limit_date']} rules={[{ required: true, message: 'Informe a data limite.' }]}>
                                    <DatePicker size="large" style={{ width: '100%' }} format="DD/MM/YYYY" placeholder="dd/mm/aaaa" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <div className="spa-pix-link-actions">
                            <Button onClick={closeLinkModal}>Cancelar</Button>
                            <Button
                                type="primary"
                                htmlType="submit"
                                loading={linkSubmitting}
                                icon={<LinkOutlined />}
                                className="spa-primary-button"
                            >
                                Criar link de pagamento
                            </Button>
                        </div>
                    </Card>
                </Form>
            </Modal>

            <Modal
                open={resultModalOpen && Boolean(boletoResult)}
                onCancel={closeResultModal}
                title="Boleto criado"
                footer={[
                    <Button key="close" onClick={closeResultModal}>
                        Fechar
                    </Button>,
                ]}
                width={920}
                className="spa-pix-modal"
                destroyOnClose={false}
            >
                {boletoResult ? (
                    <Space direction="vertical" size={12} className="spa-pix-result-stack">
                        <Row gutter={[24, 24]} align="top">
                            <Col xs={24} md={10}>
                                <Typography.Text className="spa-pix-result-column-title">Resumo</Typography.Text>
                                <div className="spa-pix-qr-placeholder">
                                    <BankOutlined />
                                </div>
                                <div className="spa-pix-qr-download">
                                    {boletoResult.boleto_url ? (
                                        <Button icon={<DownloadOutlined />} onClick={openBoletoUrl}>
                                            Abrir boleto
                                        </Button>
                                    ) : null}
                                </div>
                                <Space direction="vertical" size={8} style={{ width: '100%', marginTop: 16 }}>
                                    <Tag color={statusTone(boletoResult.status)}>{formatBoletoStatus(boletoResult.status)}</Tag>
                                    <Typography.Text strong>
                                        {boletoResult.amount ? formatCurrency(boletoResult.amount) : 'R$ 0,00'}
                                    </Typography.Text>
                                </Space>
                            </Col>
                            <Col xs={24} md={14}>
                                <div className="spa-pix-code-header">
                                    <Typography.Text className="spa-pix-result-column-title">Linha digitável</Typography.Text>
                                    <Button
                                        icon={<CopyOutlined />}
                                        onClick={copyBoletoCode}
                                        aria-label="Copiar linha digitável"
                                        title="Copiar linha digitável"
                                    />
                                </div>
                                <Input
                                    value={boletoResult.boleto_digitable_line || boletoResult.boleto_barcode || ''}
                                    readOnly
                                    size="large"
                                    onFocus={copyBoletoCode}
                                />
                                <div className="spa-pix-instructions">
                                    <Typography.Text className="spa-pix-instructions-title">Como pagar:</Typography.Text>
                                    <ol className="spa-pix-instructions-list">
                                        <li>Abra o internet banking ou app do banco</li>
                                        <li>Escolha a opção de pagamento por boleto</li>
                                        <li>Escaneie ou copie a linha digitável</li>
                                    </ol>
                                </div>
                                <Divider />
                                <Space direction="vertical" size={8} style={{ width: '100%' }}>
                                    <Typography.Text type="secondary">Código de barras</Typography.Text>
                                    <Input value={boletoResult.boleto_barcode || ''} readOnly size="large" />
                                    <Typography.Text type="secondary">
                                        URL do boleto {boletoResult.boleto_url ? 'disponível' : 'não disponível'}
                                    </Typography.Text>
                                </Space>
                            </Col>
                        </Row>
                    </Space>
                ) : null}
            </Modal>
        </Row>
    );
}


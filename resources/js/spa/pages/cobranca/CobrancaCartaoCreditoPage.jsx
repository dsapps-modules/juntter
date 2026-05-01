import {
    BankOutlined,
    CheckCircleOutlined,
    CreditCardOutlined,
    EyeOutlined,
    LinkOutlined,
    ReloadOutlined,
    SendOutlined,
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
    InputNumber,
    Modal,
    Row,
    Select,
    Skeleton,
    Space,
    Table,
    Tag,
    Typography,
    Switch,
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

const interestOptions = [
    { label: 'Cliente', value: 'CLIENT' },
    { label: 'Estabelecimento', value: 'ESTABLISHMENT' },
];

const monthOptions = Array.from({ length: 12 }, (_, index) => {
    const month = String(index + 1).padStart(2, '0');

    return {
        label: month,
        value: month,
    };
});

const yearOptions = Array.from({ length: 6 }, (_, index) => {
    const year = new Date().getFullYear() + index;

    return {
        label: String(year),
        value: String(year),
    };
});

const defaultOverview = {
    rows: [],
    link_rows: [],
    periods: [],
    selected_period: 'all',
    recent_links: [],
};

const initialTransactionValues = {
    amount: '0,00',
    installments: 1,
    interest: 'CLIENT',
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
    card: {
        holder_name: '',
        holder_document: '',
        card_number: '',
        expiration_month: undefined,
        expiration_year: undefined,
        security_code: '',
    },
};

const linkInitialValues = {
    descricao: '',
    valor: '',
    parcelas: 1,
    juros: 'CLIENT',
    data_expiracao: null,
    url_retorno: '',
    url_webhook: '',
    dados_cliente_preenchidos_habilitado: false,
    dados_cliente_preenchidos: {
        nome: '',
        sobrenome: '',
        email: '',
        telefone: '',
        documento: '',
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

function statusDotColor(status) {
    switch (formatStatus(status)) {
        case 'Pago':
        case 'Aprovado':
            return '#22c55e';
        case 'Pendente':
        case 'Processando':
            return '#f59e0b';
        case 'Falha':
        case 'Cancelado':
        case 'Estornado':
            return '#ef4444';
        default:
            return '#d1d5db';
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

export default function CobrancaCartaoCreditoPage() {
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const [linkForm] = Form.useForm();
    const currentPeriod = getCurrentPeriod();
    const linkCustomerEnabled = Form.useWatch('dados_cliente_preenchidos_habilitado', linkForm);

    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [linkSubmitting, setLinkSubmitting] = useState(false);
    const [formVisible, setFormVisible] = useState(false);
    const [linkModalOpen, setLinkModalOpen] = useState(false);
    const [feedback, setFeedback] = useState(null);
    const [transactionResult, setTransactionResult] = useState(null);
    const [resultModalOpen, setResultModalOpen] = useState(false);
    const [selectedTransaction, setSelectedTransaction] = useState(null);
    const [detailsModalOpen, setDetailsModalOpen] = useState(false);
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
                    throw new Error('Não foi possível carregar as transações de cartão.');
                }

                const data = await response.json();
                setOverview((current) => ({
                    ...current,
                    ...data,
                    rows: data.rows ?? [],
                    link_rows: data.link_rows ?? [],
                    periods: data.periods ?? current.periods,
                    selected_period: data.selected_period ?? current.selected_period,
                    recent_links: data.recent_links ?? current.recent_links,
                }));
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setFeedback({
                        type: 'error',
                        message: fetchError.message || 'Falha ao carregar as transações de cartão.',
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

    const creditRows = useMemo(() => {
        return (overview.rows ?? [])
            .filter((row) => row.type === 'CREDIT' || row.type === 'Credito')
            .sort((left, right) => (right.created_at_sort ?? 0) - (left.created_at_sort ?? 0));
    }, [overview.rows]);

    const summary = overview.summary ?? {};
    const recentLinks = useMemo(() => {
        return (overview.recent_links ?? []).filter((item) => item.type === 'Cartao' || item.type === 'Cartão');
    }, [overview.recent_links]);

    const tableTitle = selectedPeriod === 'all'
        ? 'Transações de cartão de todos os meses'
        : `Transações de cartão de ${formatPeriodLabel(selectedPeriod)}`;

    function refreshOverview(nextPeriod = selectedPeriod) {
        if (nextPeriod === selectedPeriod) {
            setReloadToken((current) => current + 1);
            return;
        }

        setSelectedPeriod(nextPeriod);
    }

    function openTransactionDetails(record) {
        setSelectedTransaction(record);
        setDetailsModalOpen(true);
    }

    function closeTransactionDetails() {
        setDetailsModalOpen(false);
    }

    function resetTransactionForm() {
        form.resetFields();
        form.setFieldsValue(initialTransactionValues);
        setTransactionResult(null);
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

            const response = await fetch('/cobranca/transacao/credito', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    payment_type: 'CREDIT',
                    amount: values.amount,
                    installments: values.installments,
                    interest: values.interest,
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
                    card: {
                        holder_name: values.card?.holder_name ?? '',
                        holder_document: values.card?.holder_document ?? '',
                        card_number: values.card?.card_number ?? '',
                        expiration_month: values.card?.expiration_month ?? null,
                        expiration_year: values.card?.expiration_year ?? null,
                        security_code: values.card?.security_code ?? '',
                    },
                }),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.success === false) {
                throw new Error(
                    result.message ||
                        getFirstValidationError(result.errors) ||
                        'Não foi possível criar a cobrança de cartão.',
                );
            }

            setTransactionResult(result);
            setFeedback({
                type: 'success',
                message: result.message ?? 'Transação de cartão criada com sucesso.',
            });

            if (result.requires_3ds) {
                setResultModalOpen(true);
            }

            await refreshOverview();

            if (!result.requires_3ds) {
                resetTransactionForm();
            }
        } catch (error) {
            setFeedback({
                type: 'error',
                message: error.message || 'Falha ao criar a transação de cartão.',
            });
        } finally {
            setSubmitting(false);
        }
    }

    async function handleLinkSubmit(values) {
        setLinkSubmitting(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/links-pagamento', {
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
                    parcelas: values.parcelas,
                    juros: values.juros,
                    data_expiracao: values.data_expiracao ? values.data_expiracao.format('YYYY-MM-DD') : null,
                    url_retorno: values.url_retorno || null,
                    url_webhook: values.url_webhook || null,
                    dados_cliente_preenchidos: values.dados_cliente_preenchidos_habilitado
                        ? {
                              nome: values.dados_cliente_preenchidos?.nome ?? '',
                              sobrenome: values.dados_cliente_preenchidos?.sobrenome ?? '',
                              email: values.dados_cliente_preenchidos?.email ?? '',
                              telefone: values.dados_cliente_preenchidos?.telefone ?? '',
                              documento: values.dados_cliente_preenchidos?.documento ?? '',
                          }
                        : null,
                }),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = Object.values(result.errors ?? {}).flat().shift();
                throw new Error(firstError ?? result.message ?? 'Não foi possível salvar o link.');
            }

            message.success(result.message ?? 'Link de pagamento criado com sucesso.');
            closeLinkModal();
            await refreshOverview();
        } catch (error) {
            message.error(error.message || 'Falha ao salvar o link.');
        } finally {
            setLinkSubmitting(false);
        }
    }

    function copyTransactionId() {
        copyTextToClipboard(
            transactionResult?.transaction_id ?? '',
            'ID da transação copiado.',
            'Não foi possível copiar o ID da transação.',
        );
    }

    function closeForm() {
        setFormVisible(false);
    }

    const columns = [
        {
            title: 'Transacao',
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
                            backgroundColor: statusDotColor(record.status),
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
            render: (value, record) => (
                <Space direction="vertical" size={2} className="spa-pix-amount-cell">
                    <Typography.Text strong className="spa-pix-amount-value">
                        {value}
                    </Typography.Text>
                    <Typography.Text type="secondary" className="spa-pix-fee-value">
                        {record.fee ? `Taxa: ${record.fee}` : 'Taxa: R$ 0,00'}
                    </Typography.Text>
                </Space>
            ),
            width: 160,
        },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (value) => <Tag color={statusTone(value)}>{formatStatus(value)}</Tag>,
            width: 140,
        },
        {
            title: 'Data',
            dataIndex: 'created_at',
            width: 180,
        },
        {
            title: '',
            key: 'actions',
            render: (_, record) => (
                <Space size={8} wrap>
                    <Button
                        size="middle"
                        icon={<EyeOutlined />}
                        className="spa-pix-action-button spa-pix-action-button-view"
                        onClick={() => openTransactionDetails(record)}
                        title="Ver detalhes"
                        aria-label="Ver detalhes"
                    />
                </Space>
            ),
            width: 84,
        },
    ];

    return (
        <Row gutter={[20, 20]} className="spa-board spa-pix-board">
            <Col xs={24} xl={16}>
                <Card className="spa-table-card spa-pix-card">
                    <Space direction="vertical" size={18} className="spa-pix-stack">
                        {feedback ? (
                            <Alert
                                type={feedback.type}
                                showIcon
                                message={feedback.message}
                                closable
                                onClose={() => setFeedback(null)}
                            />
                        ) : null}

                        <div className="spa-pix-page-header">
                            <Button
                                type="primary"
                                icon={<CreditCardOutlined />}
                                className="spa-pix-collapse-label-badge spa-pix-page-toggle-button"
                                onClick={handleToggleForm}
                            >
                                Gerar Cobrança
                            </Button>
                            <Button
                                icon={<LinkOutlined />}
                                className="spa-pix-collapse-label-badge spa-pix-page-link-button"
                                onClick={openLinkModal}
                            >
                                Link de Pagamento
                            </Button>
                        </div>

                        <Collapse
                            className="spa-pix-collapse spa-cartao-credito-collapse"
                            bordered={false}
                            activeKey={formVisible ? ['cartao-form'] : []}
                            expandIcon={() => null}
                            onChange={(keys) => {
                                const active = Array.isArray(keys) ? keys.includes('cartao-form') : keys === 'cartao-form';
                                setFormVisible(active);
                            }}
                            items={[
                                {
                                    key: 'cartao-form',
                                    label: <span className="spa-pix-collapse-label" />,
                                    children: (
                                        <Form
                                            form={form}
                                            layout="vertical"
                                            requiredMark={false}
                                            initialValues={initialTransactionValues}
                                            onFinish={handleSubmit}
                                            className="spa-pix-form"
                                        >
                                            <Row gutter={[16, 16]}>
                                                <Col xs={24} md={8}>
                                                    <Form.Item
                                                        label="Valor da cobrança"
                                                        name="amount"
                                                        rules={[{ required: true, message: 'Informe o valor da cobrança.' }]}
                                                    >
                                                        <Input size="large" placeholder="0,00" />
                                                    </Form.Item>
                                                </Col>
                                                <Col xs={24} md={8}>
                                                    <Form.Item
                                                        label="Parcelas"
                                                        name="installments"
                                                        rules={[{ required: true, message: 'Informe as parcelas.' }]}
                                                    >
                                                        <InputNumber min={1} max={18} style={{ width: '100%' }} size="large" />
                                                    </Form.Item>
                                                </Col>
                                                <Col xs={24} md={8}>
                                                    <Form.Item
                                                        label="Quem paga as taxas"
                                                        name="interest"
                                                        rules={[{ required: true, message: 'Selecione quem paga as taxas.' }]}
                                                    >
                                                        <Select size="large" options={interestOptions} placeholder="Cliente" />
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
                                                            label="Nome"
                                                            name={['client', 'first_name']}
                                                            rules={[{ required: true, message: 'Informe o nome.' }]}
                                                        >
                                                            <Input size="large" placeholder="Nome" />
                                                        </Form.Item>
                                                    </Col>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item label="Sobrenome" name={['client', 'last_name']}>
                                                            <Input size="large" placeholder="Sobrenome" />
                                                        </Form.Item>
                                                    </Col>
                                                </Row>

                                                <Row gutter={[16, 16]}>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item
                                                            label="Documento"
                                                            name={['client', 'document']}
                                                            rules={[{ required: true, message: 'Informe o documento.' }]}
                                                        >
                                                            <Input size="large" placeholder="000.000.000-00" />
                                                        </Form.Item>
                                                    </Col>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item
                                                            label="Telefone"
                                                            name={['client', 'phone']}
                                                            rules={[{ required: true, message: 'Informe o telefone.' }]}
                                                        >
                                                            <Input size="large" placeholder="(00) 00000-0000" />
                                                        </Form.Item>
                                                    </Col>
                                                </Row>

                                                <Row gutter={[16, 16]}>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item
                                                            label="E-mail"
                                                            name={['client', 'email']}
                                                            rules={[{ required: true, type: 'email', message: 'Informe um e-mail válido.' }]}
                                                        >
                                                            <Input size="large" placeholder="email@exemplo.com" />
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
                                                        <Form.Item label="Número" name={['client', 'address', 'number']}>
                                                            <Input size="large" placeholder="123" />
                                                        </Form.Item>
                                                    </Col>
                                                </Row>

                                                <Row gutter={[16, 16]}>
                                                    <Col xs={24} md={10}>
                                                        <Form.Item
                                                            label="Bairro"
                                                            name={['client', 'address', 'neighborhood']}
                                                            rules={[{ required: true, message: 'Informe o bairro.' }]}
                                                        >
                                                            <Input size="large" placeholder="Centro" />
                                                        </Form.Item>
                                                    </Col>
                                                    <Col xs={24} md={10}>
                                                        <Form.Item
                                                            label="Cidade"
                                                            name={['client', 'address', 'city']}
                                                            rules={[{ required: true, message: 'Informe a cidade.' }]}
                                                        >
                                                            <Input size="large" placeholder="Nome da cidade" />
                                                        </Form.Item>
                                                    </Col>
                                                    <Col xs={24} md={4}>
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
                                                    <Col xs={24} md={8}>
                                                        <Form.Item
                                                            label="CEP"
                                                            name={['client', 'address', 'zip_code']}
                                                            rules={[{ required: true, message: 'Informe o CEP.' }]}
                                                        >
                                                            <Input size="large" placeholder="00000-000" />
                                                        </Form.Item>
                                                    </Col>
                                                    <Col xs={24} md={16}>
                                                        <Form.Item label="Complemento" name={['client', 'address', 'complement']}>
                                                            <Input size="large" placeholder="Apto 101" />
                                                        </Form.Item>
                                                    </Col>
                                                </Row>
                                            </Card>

                                            <Card className="spa-pix-subcard" bordered={false}>
                                                <Typography.Text className="spa-pix-section-label">
                                                    Dados do cartão
                                                </Typography.Text>

                                                <Row gutter={[16, 16]}>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item
                                                            label="Nome no cartão"
                                                            name={['card', 'holder_name']}
                                                            rules={[{ required: true, message: 'Informe o nome no cartão.' }]}
                                                        >
                                                            <Input size="large" placeholder="Nome impresso no cartão" />
                                                        </Form.Item>
                                                    </Col>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item label="Documento do portador" name={['card', 'holder_document']}>
                                                            <Input size="large" placeholder="CPF/CNPJ do portador" />
                                                        </Form.Item>
                                                    </Col>
                                                </Row>

                                                <Row gutter={[16, 16]}>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item
                                                            label="Número do cartão"
                                                            name={['card', 'card_number']}
                                                            rules={[{ required: true, message: 'Informe o número do cartão.' }]}
                                                        >
                                                            <Input size="large" placeholder="0000 0000 0000 0000" />
                                                        </Form.Item>
                                                    </Col>
                                                    <Col xs={24} md={12}>
                                                        <Form.Item
                                                            label="Código de segurança"
                                                            name={['card', 'security_code']}
                                                            rules={[{ required: true, message: 'Informe o código de segurança.' }]}
                                                        >
                                                            <Input size="large" placeholder="123" />
                                                        </Form.Item>
                                                    </Col>
                                                </Row>

                                                <Row gutter={[16, 16]}>
                                                    <Col xs={24} md={8}>
                                                        <Form.Item
                                                            label="Mês de expiração"
                                                            name={['card', 'expiration_month']}
                                                            rules={[{ required: true, message: 'Selecione o mês de expiração.' }]}
                                                        >
                                                            <Select size="large" options={monthOptions} placeholder="MM" />
                                                        </Form.Item>
                                                    </Col>
                                                    <Col xs={24} md={8}>
                                                        <Form.Item
                                                            label="Ano de expiração"
                                                            name={['card', 'expiration_year']}
                                                            rules={[{ required: true, message: 'Selecione o ano de expiração.' }]}
                                                        >
                                                            <Select size="large" options={yearOptions} placeholder="AAAA" />
                                                        </Form.Item>
                                                    </Col>
                                                </Row>

                                                <div className="spa-pix-link-actions">
                                                    <Button onClick={closeForm}>Fechar</Button>
                                                    <Button
                                                        type="primary"
                                                        htmlType="submit"
                                                        loading={submitting}
                                                        icon={<SendOutlined />}
                                                        className="spa-primary-button"
                                                    >
                                                        Criar cobrança
                                                    </Button>
                                                </div>
                                            </Card>
                                        </Form>
                                    ),
                                },
                            ]}
                        />

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
                                />
                            }
                            bordered={false}
                        >
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 6 }} />
                            ) : creditRows.length === 0 ? (
                                <Empty description="Nenhuma transação de cartão encontrada" />
                            ) : (
                                <Table
                                    rowKey="id"
                                    columns={columns}
                                    dataSource={creditRows}
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
                    <Card className="spa-quick-view-card spa-pix-sidebar-card" title="Painel lateral" bordered={false}>
                        <Space direction="vertical" size={16} style={{ width: '100%' }}>
                            <div className="spa-pix-detail-side-hero">
                                <BankOutlined className="spa-pix-detail-side-icon" />
                                <Typography.Title level={4} className="spa-pix-detail-side-title">
                                    Visão rápida
                                </Typography.Title>
                            </div>

                            <Row gutter={[12, 12]}>
                                {[
                                    ['Transações', summary.credit_transactions ?? 0],
                                    ['Aprovadas', summary.paid_transactions ?? 0],
                                    ['Pendentes', summary.pending_transactions ?? 0],
                                    ['Links ativos', summary.active_links ?? 0],
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
                                    <Button type="primary" block onClick={openLinkModal} className="spa-primary-button">
                                        Criar link de cartão
                                    </Button>
                                    <Button block onClick={() => navigate('/links-pagamento')}>
                                        Ver links
                                    </Button>
                                    <Button block onClick={() => refreshOverview()}>
                                        Atualizar painel
                                    </Button>
                                </Space>
                            </Card>

                            <Card size="small" title="Últimos links" bordered={false}>
                                {recentLinks.length === 0 ? (
                                    <Empty description="Nenhum link recente encontrado." />
                                ) : (
                                    <Space direction="vertical" size={12} style={{ width: '100%' }}>
                                        {recentLinks.slice(0, 5).map((item) => (
                                            <div key={item.id} className="spa-pix-side-link-item">
                                                <Space direction="vertical" size={2} style={{ width: '100%' }}>
                                                    <Typography.Text strong>{item.title}</Typography.Text>
                                                    <Typography.Text type="secondary">{item.code}</Typography.Text>
                                                </Space>
                                                <Space wrap>
                                                    <Tag color="green">{item.amount}</Tag>
                                                    <Tag color={item.status === 'Ativo' ? 'green' : 'gold'}>{item.status}</Tag>
                                                </Space>
                                                <Typography.Text type="secondary">{item.expires_at}</Typography.Text>
                                                <Button size="small" onClick={() => navigate(`/links-pagamento/${item.id}/editar`)}>
                                                    Abrir
                                                </Button>
                                            </div>
                                        ))}
                                    </Space>
                                )}
                            </Card>

                            <Card size="small" title="Dica rápida" bordered={false}>
                                <Space direction="vertical" size={8} style={{ width: '100%' }}>
                                    <Typography.Text>
                                        Use o botão Gerar Cobrança para abrir o accordion e emitir uma transação com cartão.
                                    </Typography.Text>
                                    <Typography.Text>
                                        O seletor de mês controla a lista de transações exibida abaixo.
                                    </Typography.Text>
                                </Space>
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
                                Link de Pagamento - Cartão de Crédito
                            </Typography.Title>
                            <Typography.Text type="secondary">
                                Configure um link para pagamento com cartão de crédito.
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
                                <Input size="large" placeholder="R$ 25,00" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Row gutter={[16, 16]}>
                        <Col xs={24} md={8}>
                            <Form.Item
                                label="Parcelas"
                                name="parcelas"
                                rules={[{ required: true, message: 'Informe as parcelas.' }]}
                            >
                                <InputNumber min={1} max={18} style={{ width: '100%' }} size="large" />
                            </Form.Item>
                        </Col>
                        <Col xs={24} md={8}>
                            <Form.Item
                                label="Quem paga as taxas"
                                name="juros"
                                rules={[{ required: true, message: 'Selecione quem paga as taxas.' }]}
                            >
                                <Select size="large" options={interestOptions} placeholder="Cliente" />
                            </Form.Item>
                        </Col>
                        <Col xs={24} md={8}>
                            <Form.Item label="Data de expiracao do link" name="data_expiracao">
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
                            <Form.Item label="URL de retorno" name="url_retorno">
                                <Input size="large" placeholder="https://exemplo.com/obrigado" />
                            </Form.Item>
                        </Col>
                        <Col xs={24} md={12}>
                            <Form.Item label="Webhook" name="url_webhook">
                                <Input size="large" placeholder="https://exemplo.com/webhook" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <div className="spa-pix-link-switch-row">
                        <Typography.Title level={4} className="spa-pix-link-section-title">
                            Dados do cliente
                        </Typography.Title>
                        <Form.Item
                            name="dados_cliente_preenchidos_habilitado"
                            valuePropName="checked"
                            className="spa-pix-link-switch-item"
                        >
                            <Switch />
                        </Form.Item>
                        <Typography.Text strong>Preencher dados do cliente</Typography.Text>
                    </div>

                    {linkCustomerEnabled ? (
                        <Row gutter={[16, 16]} className="spa-pix-link-section-grid">
                            <Col xs={24} md={8}>
                                <Form.Item label="Nome" name={['dados_cliente_preenchidos', 'nome']}>
                                    <Input size="large" placeholder="Nome" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item label="Sobrenome" name={['dados_cliente_preenchidos', 'sobrenome']}>
                                    <Input size="large" placeholder="Sobrenome" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item label="Documento" name={['dados_cliente_preenchidos', 'documento']}>
                                    <Input size="large" placeholder="000.000.000-00" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item label="E-mail" name={['dados_cliente_preenchidos', 'email']}>
                                    <Input size="large" placeholder="email@exemplo.com" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item label="Telefone" name={['dados_cliente_preenchidos', 'telefone']}>
                                    <Input size="large" placeholder="(00) 00000-0000" />
                                </Form.Item>
                            </Col>
                        </Row>
                    ) : null}

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
                </Form>
            </Modal>

            <Modal
                open={resultModalOpen && Boolean(transactionResult)}
                onCancel={closeResultModal}
                title="Cobrança de cartão"
                footer={[
                    <Button key="close" onClick={closeResultModal}>
                        Fechar
                    </Button>,
                ]}
                width={840}
                className="spa-pix-modal"
                destroyOnClose={false}
            >
                {transactionResult ? (
                    <Space direction="vertical" size={16} className="spa-pix-result-stack">
                        <Alert
                            type={transactionResult.requires_3ds ? 'warning' : 'success'}
                            showIcon
                            icon={transactionResult.requires_3ds ? <ReloadOutlined /> : <CheckCircleOutlined />}
                            message={transactionResult.message ?? 'Transação criada com sucesso.'}
                        />

                        {transactionResult.requires_3ds ? (
                            <>
                                <Row gutter={[16, 16]}>
                                    <Col xs={24} md={12}>
                                        <Typography.Text className="spa-pix-result-column-title">
                                            Session ID
                                        </Typography.Text>
                                        <Input
                                            value={transactionResult.session_id ?? ''}
                                            readOnly
                                            size="large"
                                            onFocus={copyTransactionId}
                                        />
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Typography.Text className="spa-pix-result-column-title">
                                            Transaction ID
                                        </Typography.Text>
                                        <Input
                                            value={transactionResult.transaction_id ?? ''}
                                            readOnly
                                            size="large"
                                            onFocus={copyTransactionId}
                                        />
                                    </Col>
                                </Row>

                                <Typography.Text type="secondary">
                                    A transação aguarda autenticação 3DS antes da conclusão.
                                </Typography.Text>
                            </>
                        ) : (
                            <Typography.Text type="secondary">
                                A transação foi enviada para processamento.
                            </Typography.Text>
                        )}
                    </Space>
                ) : null}
            </Modal>

            <Modal
                open={detailsModalOpen && Boolean(selectedTransaction)}
                onCancel={closeTransactionDetails}
                title="Detalhes da transação"
                footer={[
                    <Button key="close" onClick={closeTransactionDetails}>
                        Fechar
                    </Button>,
                ]}
                width={760}
                className="spa-pix-modal"
            >
                {selectedTransaction ? (
                    <Row gutter={[16, 16]}>
                        <Col xs={24} md={12}>
                            <Typography.Text type="secondary">ID</Typography.Text>
                            <div>
                                <Typography.Text strong>{selectedTransaction.id}</Typography.Text>
                            </div>
                        </Col>
                        <Col xs={24} md={12}>
                            <Typography.Text type="secondary">Status</Typography.Text>
                            <div>
                                <Tag color={statusTone(selectedTransaction.status)}>{formatStatus(selectedTransaction.status)}</Tag>
                            </div>
                        </Col>
                        <Col xs={24} md={12}>
                            <Typography.Text type="secondary">Tipo</Typography.Text>
                            <div>
                                <Tag color="cyan">{selectedTransaction.type}</Tag>
                            </div>
                        </Col>
                        <Col xs={24} md={12}>
                            <Typography.Text type="secondary">Data</Typography.Text>
                            <div>
                                <Typography.Text strong>{selectedTransaction.created_at}</Typography.Text>
                            </div>
                        </Col>
                        <Col xs={24} md={12}>
                            <Typography.Text type="secondary">Valor</Typography.Text>
                            <div>
                                <Typography.Text strong>{selectedTransaction.amount}</Typography.Text>
                            </div>
                        </Col>
                        <Col xs={24} md={12}>
                            <Typography.Text type="secondary">Taxa</Typography.Text>
                            <div>
                                <Typography.Text strong>{selectedTransaction.fee}</Typography.Text>
                            </div>
                        </Col>
                        <Col xs={24}>
                            <Typography.Text type="secondary">Cliente</Typography.Text>
                            <div>
                                <Typography.Text strong>{selectedTransaction.customer ?? selectedTransaction.title}</Typography.Text>
                            </div>
                        </Col>
                        <Col xs={24}>
                            <Typography.Text type="secondary">Estabelecimento</Typography.Text>
                            <div>
                                <Typography.Text strong>{selectedTransaction.establishment}</Typography.Text>
                            </div>
                        </Col>
                    </Row>
                ) : null}
            </Modal>
        </Row>
    );
}

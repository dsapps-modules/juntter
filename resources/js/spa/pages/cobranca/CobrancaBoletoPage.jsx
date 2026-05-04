import {
    BankOutlined,
    CheckCircleOutlined,
    CreditCardOutlined,
    CopyOutlined,
    EyeOutlined,
    ExclamationCircleOutlined,
    FileTextOutlined,
    ReloadOutlined,
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
    expiration: null,
    payment_limit_date: null,
    recharge: false,
    client: {
        first_name: '',
        last_name: '',
        document: '',
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
    const currentPeriod = getCurrentPeriod();

    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [formVisible, setFormVisible] = useState(false);
    const [feedback, setFeedback] = useState(null);
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
            .filter((row) => row.kind === 'boleto' || row.type === 'Boleto' || row.type === 'BOLETO' || row.raw_status)
            .sort((left, right) => (right.created_at_sort ?? 0) - (left.created_at_sort ?? 0));
    }, [overview.rows]);

    const boletoSummary = overview.summary ?? defaultOverview.summary;
    const recentBoletos = (overview.recent_boletos ?? boletoRows.slice(0, 5)).slice(0, 5);
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

            setBoletoResult(result.boleto_data ?? null);
            setFeedback({
                type: 'success',
                message: result.message ?? 'Boleto criado com sucesso.',
            });
            form.resetFields();
            await refreshOverview();
        } catch (error) {
            setFeedback({
                type: 'error',
                message: error.message || 'Falha ao criar o boleto.',
            });
        } finally {
            setSubmitting(false);
        }
    }

    async function cancelBoleto(record) {
        try {
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
        }
    }

    function openBoletoDetails(record) {
        navigate(`/cobranca/boleto/${record.code}`);
    }

    const columns = [
        {
            title: 'ID',
            dataIndex: 'code',
            width: 240,
            render: (value, record) => (
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
                backgroundColor: record.raw_status === 'PAID' || record.raw_status === 'APPROVED' ? '#22c55e' : '#f59e0b',
                        }}
                    />
                    <Space direction="vertical" size={2}>
                        <Typography.Text strong className="spa-pix-row-title">
                            {record.title}
                        </Typography.Text>
                        <Typography.Text type="secondary" className="spa-pix-row-subtitle">
                            {value}
                            {record.raw_status ? ` • ${record.raw_status}` : ''}
                        </Typography.Text>
                    </Space>
                </Space>
            ),
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
            <Col xs={24} xl={16}>
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

                            <Button
                                htmlType="button"
                                onClick={() => refreshOverview()}
                                className="spa-pix-collapse-label-badge spa-pix-page-link-button"
                            >
                                <ReloadOutlined />
                                Atualizar painel
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
                                                <Input size="large" placeholder="0,00" inputMode="decimal" />
                                            </Form.Item>
                                        </Col>
                                        <Col xs={24} md={8}>
                                            <Form.Item
                                                label="Data de expiração"
                                                name="expiration"
                                                rules={[{ required: true, message: 'Informe a data de expiração.' }]}
                                            >
                                                <DatePicker size="large" style={{ width: '100%' }} format="DD/MM/YYYY" />
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
                                            <Col xs={24} md={8}>
                                                <Form.Item
                                                    label="CPF/CNPJ"
                                                    name={['client', 'document']}
                                                    rules={[{ required: true, message: 'Informe o documento.' }]}
                                                >
                                                    <Input size="large" placeholder="000.000.000-00" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={16}>
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
                                                    label="Multa"
                                                    name={['instruction', 'late_fee', 'amount']}
                                                    rules={[{ required: true, message: 'Informe a multa.' }]}
                                                >
                                                    <Input size="large" placeholder="2,00" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={8}>
                                                <Form.Item
                                                    label="Juros"
                                                    name={['instruction', 'interest', 'amount']}
                                                    rules={[{ required: true, message: 'Informe os juros.' }]}
                                                >
                                                    <Input size="large" placeholder="1,00" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={8}>
                                                <Form.Item
                                                    label="Desconto"
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
                                    ['Boletos', boletoSummary.total_billets ?? 0],
                                    ['Pagos', boletoSummary.paid_billets ?? 0],
                                    ['Pendentes', boletoSummary.pending_billets ?? 0],
                                    ['Falhas', boletoSummary.failed_billets ?? 0],
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
                                    <Button type="primary" block onClick={() => setFormVisible(true)} className="spa-primary-button">
                                        Criar boleto
                                    </Button>
                                    <Button block onClick={() => navigate('/cobranca')}>
                                        Ver histórico
                                    </Button>
                                    <Button block onClick={() => refreshOverview()}>
                                        Atualizar painel
                                    </Button>
                                </Space>
                            </Card>

                            <Card size="small" title="Últimos boletos" bordered={false}>
                                {recentBoletos.length === 0 ? (
                                    <Empty description="Nenhum boleto recente encontrado." />
                                ) : (
                                    <Space direction="vertical" size={12} style={{ width: '100%' }}>
                                        {recentBoletos.map((item) => (
                                            <div key={item.code} className="spa-pix-side-link-item">
                                                <Space direction="vertical" size={2} style={{ width: '100%' }}>
                                                    <Typography.Text strong>{item.title}</Typography.Text>
                                                    <Typography.Text type="secondary">{item.code}</Typography.Text>
                                                </Space>
                                                <Space wrap>
                                                    <Tag color="gold">{item.amount}</Tag>
                                                    <Tag color={item.status === 'Pago' ? 'green' : 'gold'}>{item.status}</Tag>
                                                </Space>
                                                <Typography.Text type="secondary">{item.created_at}</Typography.Text>
                                                <Button size="small" onClick={() => openBoletoDetails(item)}>
                                                    Abrir
                                                </Button>
                                            </div>
                                        ))}
                                    </Space>
                                )}
                            </Card>
                        </Space>
                    </Card>
                </Space>
            </Col>

        </Row>
    );
}


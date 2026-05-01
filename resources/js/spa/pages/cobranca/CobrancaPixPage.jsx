import {
    ArrowLeftOutlined,
    DeleteOutlined,
    EyeOutlined,
    CheckCircleOutlined,
    CopyOutlined,
    DownloadOutlined,
    ExclamationCircleOutlined,
    QrcodeOutlined,
    StopOutlined,
    SendOutlined,
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
import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';

const interestOptions = [
    { label: 'Cliente', value: 'CLIENT' },
    { label: 'Estabelecimento', value: 'ESTABLISHMENT' },
];

const defaultOverview = {
    rows: [],
    link_rows: [],
    periods: [],
    selected_period: 'all',
};

const initialValues = {
    amount: '',
    interest: undefined,
    client: {
        first_name: '',
        last_name: '',
        document: '',
        phone: '',
        email: '',
    },
    info_additional: '',
};

const linkInitialValues = {
    descricao: '',
    valor: '',
    juros: 'CLIENT',
    data_expiracao: null,
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

export default function CobrancaPixPage() {
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
    const [pixResult, setPixResult] = useState(null);
    const [resultModalOpen, setResultModalOpen] = useState(false);
    const [detailsModalOpen, setDetailsModalOpen] = useState(false);
    const [selectedTransaction, setSelectedTransaction] = useState(null);
    const [selectedPeriod, setSelectedPeriod] = useState(currentPeriod);
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
                    throw new Error('Não foi possível carregar as transações Pix.');
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
                        message: fetchError.message || 'Falha ao carregar as transações Pix.',
                    });
                }
            } finally {
                setLoading(false);
            }
        }

        loadOverview();

        return () => controller.abort();
    }, [selectedPeriod]);

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

    const pixRows = useMemo(() => {
        const transactionRows = (overview.rows ?? []).filter((row) => row.type === 'PIX').map((row) => ({
            ...row,
            kind: row.kind ?? 'transaction',
        }));
        const linkRows = (overview.link_rows ?? []).map((row) => ({
            ...row,
            kind: 'link',
        }));

        return [...transactionRows, ...linkRows].sort(
            (left, right) => (right.created_at_sort ?? 0) - (left.created_at_sort ?? 0),
        );
    }, [overview.link_rows, overview.rows]);

    const summary = overview.summary ?? {};
    const recentLinks = overview.recent_links ?? [];
    const linkCustomerEnabled = Form.useWatch('dados_cliente_preenchidos_habilitado', linkForm);

    function getStatusColor(status) {
        switch (status) {
            case 'Ativo':
            case 'Pago':
            case 'Aprovado':
                return 'green';
            case 'Inativo':
            case 'Cancelado':
            case 'Falha':
            case 'Estornado':
                return 'volcano';
            default:
                return 'gold';
        }
    }

    function getTransactionStatusTone(status) {
        switch (status) {
            case 'Ativo':
            case 'Pago':
            case 'Aprovado':
                return 'green';
            case 'Inativo':
            case 'Cancelado':
            case 'Falha':
            case 'Estornado':
                return 'red';
            default:
                return 'gray';
        }
    }

    function getTransactionStatusColor(record) {
        const rawStatus = String(record?.raw_status ?? record?.status ?? '').toLowerCase();

        if (rawStatus.includes('ativo') || rawStatus.includes('paid') || rawStatus.includes('aprov')) {
            return '#22c55e';
        }

        if (rawStatus.includes('inativo') || rawStatus.includes('cancel') || rawStatus.includes('falh') || rawStatus.includes('estorn')) {
            return '#ef4444';
        }

        return '#d1d5db';
    }

    function openTransactionDetails(record) {
        setSelectedTransaction(record);
        setDetailsModalOpen(true);
    }

    function closeTransactionDetails() {
        setDetailsModalOpen(false);
    }

    function openLinkDetails(record) {
        navigate(record.detail_href || `/links-pagamento-pix/${record.id}`);
    }

    async function deleteLink(record) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(record.delete_href || `/links-pagamento-pix/${record.id}`, {
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
                throw new Error(result.message || 'Não foi possível excluir o link.');
            }

            setFeedback({
                type: 'success',
                message: result.message ?? 'Link de pagamento excluído com sucesso.',
            });
            await refreshOverview();
        } catch (error) {
            setFeedback({
                type: 'error',
                message: error.message || 'Falha ao excluir o link.',
            });
        }
    }

    const columns = [
        {
            title: 'Transação',
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
                            backgroundColor: getTransactionStatusColor(record),
                        }}
                    />
                    <Space direction="vertical" size={2}>
                        <Typography.Text strong className="spa-pix-row-title">
                            {record.title}
                        </Typography.Text>
                        <Typography.Text type="secondary" className="spa-pix-row-subtitle">
                            {record.kind === 'link' ? record.code : record.description}
                        </Typography.Text>
                    </Space>
                </Space>
            ),
            width: 300,
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
                        {record.kind === 'link' ? 'Link de pagamento' : `Taxa: ${record.fee}`}
                    </Typography.Text>
                </Space>
            ),
            width: 160,
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
                    {record.kind === 'link' ? (
                        <>
                            <Button
                                size="middle"
                                icon={<EyeOutlined />}
                                className="spa-pix-action-button spa-pix-action-button-view"
                                onClick={() => openLinkDetails(record)}
                                title="Ver detalhes"
                                aria-label="Ver detalhes"
                            />
                            <Button
                                size="middle"
                                icon={<DeleteOutlined />}
                                className="spa-pix-action-button spa-pix-action-button-cancel"
                                onClick={() => deleteLink(record)}
                                title="Excluir"
                                aria-label="Excluir"
                            />
                        </>
                    ) : (
                        <>
                            <Button
                                size="middle"
                                icon={<EyeOutlined />}
                                className="spa-pix-action-button spa-pix-action-button-view"
                                onClick={() => openTransactionDetails(record)}
                                title="Ver detalhes"
                                aria-label="Ver detalhes"
                            />
                            <Button
                                size="middle"
                                icon={<StopOutlined />}
                                className="spa-pix-action-button spa-pix-action-button-cancel"
                                onClick={() => cancelTransaction(record)}
                                title="Cancelar"
                                aria-label="Cancelar"
                            />
                        </>
                    )}
                </Space>
            ),
            width: 124,
        },
    ];

    async function handleSubmit(values) {
        setSubmitting(true);
        setFeedback(null);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/cobranca/transacao/pix', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    payment_type: 'PIX',
                    ...values,
                }),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.success === false) {
                throw new Error(
                    result.message ||
                        getFirstValidationError(result.errors) ||
                        'Não foi possível criar a cobrança Pix.',
                );
            }

            setPixResult(result.pix_data ?? null);
            setFeedback({
                type: 'success',
                message: result.message ?? 'Transação Pix criada com sucesso.',
            });
            setResultModalOpen(Boolean(result.pix_data));
            setFormVisible(false);
            await refreshOverview();

            if (!result.pix_data) {
                form.resetFields();
            }
        } catch (error) {
            setFeedback({
                type: 'error',
                message: error.message || 'Falha ao criar a cobrança Pix.',
            });
        } finally {
            setSubmitting(false);
        }
    }

    async function refreshOverview(nextPeriod = selectedPeriod) {
        const params = new URLSearchParams();
        params.set('period', nextPeriod);

        const response = await fetch(`/api/spa/cobranca${params.toString() !== '' ? `?${params.toString()}` : ''}`, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
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
    }

    async function copyPixCode() {
        const pixCode = pixResult?.pix_code || pixResult?.qr_code?.emv || '';

        if (!pixCode) {
            return;
        }

        try {
            await navigator.clipboard.writeText(pixCode);
            message.success('Código Pix copiado.');
        } catch (error) {
            message.error('Não foi possível copiar o código Pix.');
        }
    }

    async function cancelTransaction(record) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(`/cobranca/transacao/${record.id}/estornar`, {
                method: 'POST',
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
                throw new Error(result.message || 'Não foi possível cancelar a transação.');
            }

            setFeedback({
                type: 'success',
                message: result.message ?? 'Transação cancelada com sucesso.',
            });
            await refreshOverview();
        } catch (error) {
            setFeedback({
                type: 'error',
                message: error.message || 'Falha ao cancelar a transação.',
            });
        }
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

    async function handleLinkSubmit(values) {
        setLinkSubmitting(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/links-pagamento-pix', {
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

            message.success(result.message ?? 'Link de pagamento PIX criado com sucesso.');
            closeLinkModal();
            await refreshOverview();
        } catch (submitError) {
            message.error(submitError.message || 'Falha ao salvar o link.');
        } finally {
            setLinkSubmitting(false);
        }
    }

    function downloadQrCode() {
        const qrCode = pixResult?.qr_code?.qrcode;

        if (!qrCode) {
            return;
        }

        const link = document.createElement('a');
        link.href = qrCode;
        link.download = 'qrcode-pix.png';
        link.click();
    }

    function closeResultModal() {
        setResultModalOpen(false);
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
                                onClick={() => setFormVisible((current) => !current)}
                                aria-expanded={formVisible}
                                className="spa-pix-collapse-label-badge spa-pix-page-toggle-button"
                            >
                                <QrcodeOutlined />
                                <span>Gerar QR Code</span>
                            </Button>

                            <Button
                                htmlType="button"
                                onClick={openLinkModal}
                                className="spa-pix-collapse-label-badge spa-pix-page-link-button"
                            >
                                Link de Pagamento
                            </Button>
                        </div>

                        {formVisible ? (
                            <div className="spa-pix-form-panel">
                                <Form
                                    form={form}
                                    layout="vertical"
                                    requiredMark={false}
                                    initialValues={initialValues}
                                    onFinish={handleSubmit}
                                    className="spa-pix-form"
                                >
                                    <Row gutter={[16, 16]}>
                                        <Col xs={24} md={12}>
                                            <Form.Item
                                                label="Valor da transação"
                                                name="amount"
                                                rules={[{ required: true, message: 'Informe o valor da transação.' }]}
                                            >
                                                <Input size="large" placeholder="0,00" inputMode="decimal" />
                                            </Form.Item>
                                        </Col>
                                        <Col xs={24} md={12}>
                                            <Form.Item
                                                label="Quem paga as taxas"
                                                name="interest"
                                                rules={[{ required: true, message: 'Selecione quem paga as taxas.' }]}
                                            >
                                                <Select
                                                    size="large"
                                                    placeholder="Selecione..."
                                                    options={interestOptions}
                                                />
                                            </Form.Item>
                                        </Col>
                                    </Row>

                                    <Card className="spa-pix-subcard" bordered={false}>
                                        <Typography.Text className="spa-pix-section-label">
                                            Dados do cliente (opcional)
                                        </Typography.Text>

                                        <Row gutter={[16, 16]}>
                                            <Col xs={24} md={12}>
                                                <Form.Item label="Nome do cliente" name={['client', 'first_name']}>
                                                    <Input size="large" placeholder="Nome completo" />
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
                                                <Form.Item label="CPF/CNPJ" name={['client', 'document']}>
                                                    <Input size="large" placeholder="000.000.000-00" />
                                                </Form.Item>
                                            </Col>
                                            <Col xs={24} md={12}>
                                                <Form.Item label="Telefone" name={['client', 'phone']}>
                                                    <Input size="large" placeholder="(00) 00000-0000" />
                                                </Form.Item>
                                            </Col>
                                        </Row>

                                        <Row gutter={[16, 0]}>
                                            <Col span={24}>
                                                <Form.Item
                                                    label="Email"
                                                    name={['client', 'email']}
                                                    className="spa-pix-email-field"
                                                >
                                                    <Input size="large" placeholder="email@exemplo.com" />
                                                </Form.Item>
                                            </Col>
                                        </Row>
                                    </Card>

                                    <Card className="spa-pix-subcard spa-pix-observations-card" bordered={false}>
                                        <Form.Item
                                            label="Observações"
                                            name="info_additional"
                                            className="spa-pix-observations-item"
                                        >
                                            <Input.TextArea
                                                rows={2}
                                                placeholder="Informações extras sobre a transação"
                                            />
                                        </Form.Item>
                                        <Typography.Text type="secondary">
                                            Informações extras sobre a transação
                                        </Typography.Text>
                                    </Card>

                                    <div className="spa-pix-actions">
                                        <Button icon={<ArrowLeftOutlined />} onClick={() => navigate('/cobranca')}>
                                            Fechar
                                        </Button>
                                        <Button
                                            type="primary"
                                            htmlType="submit"
                                            loading={submitting}
                                            icon={<SendOutlined />}
                                            className="spa-primary-button"
                                        >
                                            Criar Transação Pix
                                        </Button>
                                    </div>
                                </Form>
                            </div>
                        ) : null}

                        <Card
                            className="spa-pix-table-card"
                            title={`Transações Pix ${selectedPeriod === 'all' ? 'de todos os meses' : formatPeriodLabel(selectedPeriod)}`}
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
                            ) : pixRows.length === 0 ? (
                                <Empty description="Nenhuma transação Pix encontrada" />
                            ) : (
                                <Table
                                    rowKey="id"
                                    columns={columns}
                                    dataSource={pixRows}
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
                                <QrcodeOutlined className="spa-pix-detail-side-icon" />
                                <Typography.Title level={4} className="spa-pix-detail-side-title">
                                    Visão rápida
                                </Typography.Title>
                            </div>

                            <Row gutter={[12, 12]}>
                                {[
                                    ['Transações', summary.total_transactions ?? 0],
                                    ['Pagas', summary.paid_transactions ?? 0],
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
                                        Criar link PIX
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
                                                <Button size="small" onClick={() => navigate(`/links-pagamento-pix/${item.id}`)}>
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
                                        Use o botão de link de pagamento para criar uma cobrança imediata.
                                    </Typography.Text>
                                    <Typography.Text>
                                        O painel lateral ajuda a acompanhar o volume e os links mais recentes.
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
                                Link de Pagamento - PIX
                            </Typography.Title>
                            <Typography.Text type="secondary">
                                Configure um link para seus clientes realizarem pagamentos PIX instantâneos.
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
                                <Select size="large" options={interestOptions} placeholder="Cliente" />
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
                            <Typography.Text type="secondary">
                                Opcional - Deixe em branco para link sem expiração
                            </Typography.Text>
                        </Col>
                    </Row>

                    <div className="spa-pix-link-switch-row">
                        <Typography.Title level={4} className="spa-pix-link-section-title">
                            Dados do Cliente (Opcionais)
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

                    <Alert
                        type="info"
                        showIcon
                        message="Para PIX: Os dados do cliente são opcionais. Se preenchidos, serão exibidos no formulário de pagamento."
                    />

                    {linkCustomerEnabled ? (
                        <>
                            <Row gutter={[16, 16]} className="spa-pix-link-section-grid">
                                <Col xs={24} md={8}>
                                    <Form.Item label="Nome do cliente" name={['dados_cliente_preenchidos', 'nome']}>
                                        <Input size="large" placeholder="Nome completo" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Sobrenome" name={['dados_cliente_preenchidos', 'sobrenome']}>
                                        <Input size="large" placeholder="Sobrenome" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="CPF/CNPJ" name={['dados_cliente_preenchidos', 'documento']}>
                                        <Input size="large" placeholder="000.000.000-00" />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={[16, 16]}>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Email" name={['dados_cliente_preenchidos', 'email']}>
                                        <Input size="large" placeholder="email@exemplo.com" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Telefone" name={['dados_cliente_preenchidos', 'telefone']}>
                                        <Input size="large" placeholder="(00) 00000-0000" />
                                    </Form.Item>
                                </Col>
                            </Row>
                        </>
                    ) : null}

                    <div className="spa-pix-link-actions">
                        <Button onClick={closeLinkModal}>Cancelar</Button>
                        <Button
                            type="primary"
                            htmlType="submit"
                            loading={linkSubmitting}
                            icon={<SendOutlined />}
                            className="spa-primary-button"
                        >
                            Criar Link PIX
                        </Button>
                    </div>
                </Form>
            </Modal>

            <Modal
                open={resultModalOpen && Boolean(pixResult)}
                onCancel={closeResultModal}
                title="Pagamento PIX"
                footer={[
                    <Button key="close" onClick={closeResultModal}>
                        Fechar
                    </Button>,
                ]}
                width={960}
                className="spa-pix-modal"
                destroyOnClose={false}
            >
                {pixResult ? (
                    <Space direction="vertical" size={12} className="spa-pix-result-stack">
                        <Row gutter={[24, 24]} align="top">
                            <Col xs={24} md={10}>
                                <Typography.Text className="spa-pix-result-column-title">
                                    QR Code
                                </Typography.Text>
                                {pixResult.qr_code?.qrcode ? (
                                    <div className="spa-pix-qr-preview">
                                        <img
                                            src={pixResult.qr_code.qrcode}
                                            alt="QR Code Pix"
                                            className="spa-pix-qr-image"
                                        />
                                    </div>
                                ) : (
                                    <div className="spa-pix-qr-placeholder">
                                        <QrcodeOutlined />
                                    </div>
                                )}
                                <div className="spa-pix-qr-download">
                                    <Button icon={<DownloadOutlined />} onClick={downloadQrCode}>
                                        Baixar QR Code
                                    </Button>
                                </div>
                            </Col>
                            <Col xs={24} md={14}>
                                <div className="spa-pix-code-header">
                                    <Typography.Text className="spa-pix-result-column-title">
                                        Código Pix
                                    </Typography.Text>
                                    <Button
                                        icon={<CopyOutlined />}
                                        onClick={copyPixCode}
                                        aria-label="Copiar código Pix"
                                        title="Copiar código Pix"
                                    />
                                </div>
                                <Input
                                    value={pixResult.pix_code || pixResult.qr_code?.emv || ''}
                                    readOnly
                                    size="large"
                                    onFocus={copyPixCode}
                                />
                                <div className="spa-pix-instructions">
                                    <Typography.Text className="spa-pix-instructions-title">
                                        Como pagar:
                                    </Typography.Text>
                                    <ol className="spa-pix-instructions-list">
                                        <li>Abra o app do seu banco</li>
                                        <li>Escolha "PIX" ou "Pagar"</li>
                                        <li>Escaneie o QR Code ou cole o código</li>
                                    </ol>
                                </div>
                            </Col>
                        </Row>
                    </Space>
                ) : null}
            </Modal>

            <Modal
                open={detailsModalOpen && Boolean(selectedTransaction)}
                onCancel={closeTransactionDetails}
                title="Detalhes da transação Pix"
                footer={[
                    <Button key="close" onClick={closeTransactionDetails}>
                        Fechar
                    </Button>,
                ]}
                width={720}
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
                                <Tag color={getStatusColor(selectedTransaction.status)}>{selectedTransaction.status}</Tag>
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
                    </Row>
                ) : null}
            </Modal>
        </Row>
    );
}

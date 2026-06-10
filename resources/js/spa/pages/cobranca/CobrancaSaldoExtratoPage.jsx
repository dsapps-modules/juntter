import {
    ArrowLeftOutlined,
    BankOutlined,
    LineChartOutlined,
    SwapOutlined,
    WalletOutlined,
} from '@ant-design/icons';
import { Alert, Button, Card, Col, DatePicker, Empty, Row, Skeleton, Space, Statistic, Table, Tag, Typography } from 'antd';
import dayjs from 'dayjs';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';

const defaultPayload = {
    seller_name: 'Vendedor',
    establishment: null,
    balance: {
        available: 0,
        available_label: 'R$ 0,00',
        blocked: 0,
        blocked_label: 'R$ 0,00',
        total: 0,
        total_label: 'R$ 0,00',
    },
    summary: {
        movements: 0,
        incoming_total: 0,
        incoming_total_label: 'R$ 0,00',
        outgoing_total: 0,
        outgoing_total_label: 'R$ 0,00',
        balance_total_label: 'R$ 0,00',
    },
    rows: [],
    message: '',
};

function resolveTypeColor(type) {
    switch (type) {
        case 'PIX':
            return 'blue';
        case 'P2P':
            return 'gold';
        case 'FEES':
            return 'red';
        case 'TED':
            return 'green';
        case 'BILLET':
            return 'purple';
        default:
            return 'default';
    }
}

function SummaryTile({ label, value, description, icon, tone }) {
    return (
        <Card size="small" bordered={false} className="spa-saldoextrato-mini-stat-card">
            <Space direction="vertical" size={4} style={{ width: '100%' }}>
                <Space size={8} align="center">
                    {icon}
                    <Typography.Text type="secondary">{label}</Typography.Text>
                </Space>
                <Statistic value={value} valueStyle={{ fontSize: 24 }} />
                <Typography.Text type="secondary">{description}</Typography.Text>
            </Space>
        </Card>
    );
}

function getCurrentPeriod() {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

export default function CobrancaSaldoExtratoPage() {
    const navigate = useNavigate();
    const currentPeriod = getCurrentPeriod();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState(defaultPayload);
    const [selectedPeriod, setSelectedPeriod] = useState(currentPeriod);

    useEffect(() => {
        const controller = new AbortController();

        async function loadSaldoExtrato() {
            setLoading(true);
            setError('');

            try {
                const params = new URLSearchParams();
                params.set('period', selectedPeriod);

                const response = await fetch(`/api/spa/cobranca/saldoextrato${params.toString() !== '' ? `?${params.toString()}` : ''}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'Não foi possível carregar saldo e extrato.');
                }

                setPayload({
                    seller_name: data.seller_name ?? defaultPayload.seller_name,
                    establishment: data.establishment ?? null,
                    balance: data.balance ?? defaultPayload.balance,
                    summary: data.summary ?? defaultPayload.summary,
                    rows: data.rows ?? [],
                    message: data.message ?? '',
                });
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar saldo e extrato.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadSaldoExtrato();

        return () => controller.abort();
    }, [selectedPeriod]);

    const columns = useMemo(() => [
        {
            title: 'Data',
            dataIndex: 'date',
            width: 170,
            render: (value) => <Typography.Text>{value}</Typography.Text>,
        },
        {
            title: 'Tipo',
            dataIndex: 'type_label',
            width: 140,
            render: (value, record) => <Tag color={resolveTypeColor(record.type)}>{value}</Tag>,
        },
        {
            title: 'Descrição',
            dataIndex: 'description',
            render: (value) => <Typography.Text strong>{value}</Typography.Text>,
        },
        {
            title: 'Valor',
            dataIndex: 'amount_signed_label',
            width: 160,
            align: 'right',
            render: (value, record) => (
                <Typography.Text strong type={record.modality === 'IN' ? 'success' : 'danger'}>
                    {value}
                </Typography.Text>
            ),
        },
        {
            title: 'Saldo anterior',
            dataIndex: 'old_balance_label',
            width: 160,
            align: 'right',
        },
        {
            title: 'Saldo atual',
            dataIndex: 'current_balance_label',
            width: 160,
            align: 'right',
        },
        {
            title: 'Status',
            dataIndex: 'status_label',
            width: 140,
            render: (value, record) => <Tag color={record.status === 'FAILED' ? 'red' : 'blue'}>{value}</Tag>,
        },
    ], []);

    const balance = payload.balance;
    const summary = payload.summary;

    const movementCount = payload.rows.length;
    const periodLabel = payload.establishment
        ? `Estabelecimento ${payload.establishment.id}`
        : 'Estabelecimento não vinculado';
    const selectedPeriodValue = dayjs(`${selectedPeriod}-01`);

    function handlePeriodChange(value) {
        if (!value) {
            return;
        }

        setSelectedPeriod(value.format('YYYY-MM'));
    }

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card className="spa-toolbar-card">
                    <Space direction="vertical" size={14} style={{ width: '100%' }}>
                        <Row gutter={[16, 16]} align="middle" justify="space-between">
                            <Col xs={24} md={16}>
                                <Typography.Text className="spa-brand-kicker">Saldo e extrato</Typography.Text>
                                <Typography.Title level={2} style={{ margin: '6px 0 0' }}>
                                    {payload.seller_name}
                                </Typography.Title>
                                <Typography.Paragraph type="secondary" style={{ marginBottom: 0 }}>
                                    {periodLabel}
                                </Typography.Paragraph>
                            </Col>

                            <Col xs={24} md={8}>
                                <Space wrap style={{ width: '100%', justifyContent: 'flex-end' }}>
                                    <DatePicker
                                        picker="month"
                                        size="middle"
                                        allowClear={false}
                                        value={selectedPeriodValue}
                                        format="MM/YYYY"
                                        onChange={handlePeriodChange}
                                        style={{ minWidth: 176, width: 'auto' }}
                                    />
                                    <Button
                                        type="primary"
                                        icon={<ArrowLeftOutlined />}
                                        onClick={() => navigate('/cobranca')}
                                    >
                                        Voltar
                                    </Button>
                                </Space>
                            </Col>
                        </Row>
                    </Space>
                </Card>
            </Col>

            <Col xs={24}>
                {loading ? (
                    <Card className="spa-table-card">
                        <Skeleton active paragraph={{ rows: 8 }} />
                    </Card>
                ) : error ? (
                    <Card className="spa-table-card">
                        <Alert type="error" message="Falha ao carregar dados" description={error} showIcon />
                    </Card>
                ) : (
                    <Space direction="vertical" size={20} style={{ width: '100%' }}>
                        {payload.message ? (
                            <Alert type="warning" message="Atenção" description={payload.message} showIcon />
                        ) : null}

                        <Row gutter={[16, 16]}>
                            <Col xs={24} sm={12} lg={6}>
                                <SummaryTile
                                    label="Saldo disponível"
                                    value={balance.available_label}
                                    description="Valor liberado para saque"
                                    icon={<WalletOutlined />}
                                    tone="green"
                                />
                            </Col>
                            <Col xs={24} sm={12} lg={6}>
                                <SummaryTile
                                    label="Saldo bloqueado"
                                    value={balance.blocked_label}
                                    description="Valores retidos ou em processamento"
                                    icon={<BankOutlined />}
                                    tone="gold"
                                />
                            </Col>
                            <Col xs={24} sm={12} lg={6}>
                                <SummaryTile
                                    label="Saldo total"
                                    value={balance.total_label}
                                    description="Somatório do extrato de conta"
                                    icon={<LineChartOutlined />}
                                    tone="blue"
                                />
                            </Col>
                            <Col xs={24} sm={12} lg={6}>
                                <SummaryTile
                                    label="Movimentações"
                                    value={movementCount.toString()}
                                    description="Lançamentos listados no extrato"
                                    icon={<SwapOutlined />}
                                    tone="purple"
                                />
                            </Col>
                        </Row>

                        <Row gutter={[20, 20]}>
                            <Col xs={24} xl={16}>
                                <Card
                                    className="spa-table-card spa-saldoextrato-table-card"
                                    title="Extrato do estabelecimento"
                                >
                                    <Table
                                        rowKey={(record) => record.id || `${record.date}-${record.description}`}
                                        columns={columns}
                                        dataSource={payload.rows}
                                        pagination={false}
                                        className="spa-table spa-saldoextrato-table"
                                        locale={{
                                            emptyText: <Empty description="Nenhuma movimentação encontrada no extrato." />,
                                        }}
                                        scroll={{ x: 1180 }}
                                    />
                                </Card>
                            </Col>

                            <Col xs={24} xl={8}>
                                <Card className="spa-quick-view-card spa-saldoextrato-sidebar-card" title="Resumo financeiro">
                                    <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                        <Card size="small" bordered={false} className="spa-saldoextrato-mini-stat-card">
                                            <Space direction="vertical" size={4} style={{ width: '100%' }}>
                                                <Typography.Text type="secondary">Entradas no extrato</Typography.Text>
                                                <Statistic value={summary.incoming_total_label} valueStyle={{ fontSize: 24 }} />
                                            </Space>
                                        </Card>

                                        <Card size="small" bordered={false} className="spa-saldoextrato-mini-stat-card">
                                            <Space direction="vertical" size={4} style={{ width: '100%' }}>
                                                <Typography.Text type="secondary">Saídas no extrato</Typography.Text>
                                                <Statistic value={summary.outgoing_total_label} valueStyle={{ fontSize: 24 }} />
                                            </Space>
                                        </Card>

                                        <Card size="small" bordered={false} className="spa-saldoextrato-mini-stat-card">
                                            <Space direction="vertical" size={4} style={{ width: '100%' }}>
                                                <Typography.Text type="secondary">Saldo total do parceiro</Typography.Text>
                                                <Statistic value={balance.total_label} valueStyle={{ fontSize: 24 }} />
                                            </Space>
                                        </Card>

                                        <Card size="small" bordered={false} className="spa-saldoextrato-mini-stat-card">
                                            <Space direction="vertical" size={4} style={{ width: '100%' }}>
                                                <Typography.Text type="secondary">Estabelecimento</Typography.Text>
                                                <Typography.Title level={4} style={{ margin: 0 }}>
                                                    {payload.establishment?.name ?? 'Não vinculado'}
                                                </Typography.Title>
                                                <Typography.Text type="secondary">
                                                    {payload.establishment?.id ? `ID ${payload.establishment.id}` : 'Sem ID de estabelecimento'}
                                                </Typography.Text>
                                            </Space>
                                        </Card>
                                    </Space>
                                </Card>
                            </Col>
                        </Row>
                    </Space>
                )}
            </Col>
        </Row>
    );
}

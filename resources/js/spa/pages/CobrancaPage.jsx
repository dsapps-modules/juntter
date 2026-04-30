import {
    BankOutlined,
    CreditCardOutlined,
    EllipsisOutlined,
    QrcodeOutlined,
    ReloadOutlined,
} from '@ant-design/icons';
import {
    Alert,
    Button,
    Card,
    Col,
    Divider,
    Empty,
    Input,
    List,
    Row,
    Segmented,
    Select,
    Skeleton,
    Space,
    Statistic,
    Table,
    Tag,
    Timeline,
    Typography,
} from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

const defaultPayload = {
    summary: {
        total_transactions: 0,
        today_transactions: 0,
        paid_transactions: 0,
        pending_transactions: 0,
        pix_transactions: 0,
        credit_transactions: 0,
        billet_transactions: 0,
        total_amount: 'R$ 0,00',
        total_fees: 'R$ 0,00',
        active_links: 0,
        expired_links: 0,
    },
    rows: [],
    selected: null,
    recent_links: [],
    periods: [],
    actions: [],
    seller_name: 'Vendedor',
    selected_period: 'all',
};

const filters = ['Todos', 'Pagas', 'Pendentes', 'Falhas'];

function getCurrentPeriod() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

function formatPeriodLabel(period) {
    const [year, month] = period.split('-');
    return `${month}/${year}`;
}

const filterMatcher = {
    Todos: () => true,
    Pagas: (item) => ['Pago', 'Aprovado'].includes(item.status),
    Pendentes: (item) => ['Pendente', 'Processando'].includes(item.status),
    Falhas: (item) => ['Falha', 'Cancelado', 'Estornado'].includes(item.status),
};

export default function CobrancaPage() {
    const currentPeriod = getCurrentPeriod();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState(defaultPayload);
    const [filter, setFilter] = useState('Todos');
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedPeriod, setSelectedPeriod] = useState(currentPeriod);

    useEffect(() => {
        const controller = new AbortController();

        async function loadOverview() {
            setLoading(true);
            setError('');

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
                    throw new Error('Não foi possível carregar a cobrança.');
                }

                const data = await response.json();
                setPayload((current) => ({
                    ...current,
                    ...data,
                    summary: data.summary ?? current.summary,
                    rows: data.rows ?? [],
                    selected: data.selected ?? data.rows?.[0] ?? null,
                    recent_links: data.recent_links ?? [],
                    periods: data.periods ?? current.periods,
                    actions: data.actions ?? [],
                    selected_period: data.selected_period ?? current.selected_period,
                }));
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar a cobrança.');
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

        (payload.periods ?? []).forEach((item) => {
            if (item?.value && !optionsByValue.has(item.value)) {
                optionsByValue.set(item.value, item);
            }
        });

        return Array.from(optionsByValue.values());
    }, [currentPeriod, payload.periods]);

    const visibleRows = useMemo(() => {
        return payload.rows.filter((item) => {
            const text = `${item.customer} ${item.establishment} ${item.type} ${item.status}`.toLowerCase();
            const matchesSearch = text.includes(searchTerm.toLowerCase());
            const matchesFilter = (filterMatcher[filter] ?? filterMatcher.Todos)(item);

            return matchesSearch && matchesFilter;
        });
    }, [filter, payload.rows, searchTerm]);

    const selectedRow = visibleRows.find((item) => item.id === payload.selected?.id) ?? visibleRows[0] ?? payload.selected;

    const columns = [
        {
            title: 'Cliente',
            dataIndex: 'customer',
            render: (_, record) => (
                <div>
                    <Typography.Text strong>{record.customer}</Typography.Text>
                    <div>
                        <Typography.Text type="secondary">{record.establishment}</Typography.Text>
                    </div>
                </div>
            ),
        },
        {
            title: 'Tipo',
            dataIndex: 'type',
            render: (value) => <Tag color="gold">{value}</Tag>,
        },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (value) => <Tag color={value === 'Pago' ? 'green' : value === 'Falha' ? 'volcano' : 'gold'}>{value}</Tag>,
        },
        {
            title: 'Valor',
            dataIndex: 'amount',
        },
        {
            title: 'Criado em',
            dataIndex: 'created_at',
        },
    ];

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} xl={16}>
                <Row gutter={[20, 20]}>
                    <Col span={24}>
                        <Card className="spa-toolbar-card">
                            <Space direction="vertical" size={16} className="spa-toolbar-stack">
                                <Row gutter={[16, 16]} align="middle" style={{ width: '100%' }}>
                                    <Col xs={24} md={6}>
                                        <Statistic title="Total" value={payload.summary.total_transactions} />
                                    </Col>
                                    <Col xs={24} md={6}>
                                        <Statistic title="Hoje" value={payload.summary.today_transactions} />
                                    </Col>
                                    <Col xs={24} md={6}>
                                        <Statistic title="Pagas" value={payload.summary.paid_transactions} />
                                    </Col>
                                    <Col xs={24} md={6} className="spa-period-column">
                                        <Select
                                            value={selectedPeriod}
                                            options={periodOptions}
                                            onChange={setSelectedPeriod}
                                            className="spa-period-select"
                                            aria-label="Filtrar por mês e ano"
                                        />
                                    </Col>
                                </Row>

                                <div className="spa-filter-row">
                                    <Segmented value={filter} options={filters} onChange={setFilter} className="spa-segmented" />

                                    <div className="spa-search-group">
                                        <Input
                                            allowClear
                                            prefix={<CreditCardOutlined />}
                                            className="spa-search-input"
                                            placeholder="Buscar cliente, estabelecimento ou status"
                                            value={searchTerm}
                                            onChange={(event) => setSearchTerm(event.target.value)}
                                        />
                                        <Button
                                            htmlType="button"
                                            icon={<ReloadOutlined />}
                                            className="spa-secondary-button"
                                            aria-label="Atualizar dados"
                                            title="Atualizar dados"
                                        />
                                    </div>
                                </div>
                            </Space>
                        </Card>
                    </Col>

                    <Col span={24}>
                        <Card className="spa-table-card">
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 6 }} />
                            ) : error ? (
                                <Alert type="error" message="Falha ao carregar dados" description={error} showIcon />
                            ) : visibleRows.length === 0 ? (
                                <Empty description="Nenhuma transação encontrada" />
                            ) : (
                                <Table
                                    rowKey="id"
                                    columns={columns}
                                    dataSource={visibleRows}
                                    pagination={false}
                                    className="spa-table"
                                    onRow={(record) => ({
                                        onClick: () => setPayload((current) => ({ ...current, selected: record })),
                                    })}
                                    rowClassName={(record) =>
                                        record.id === selectedRow?.id ? 'spa-table-row-selected' : ''
                                    }
                                />
                            )}
                        </Card>
                    </Col>
                </Row>
            </Col>

            <Col xs={24} xl={8}>
                <Card
                    className="spa-quick-view-card"
                    title={payload.seller_name || 'Vendedor'}
                    extra={<EllipsisOutlined />}
                >
                    {!selectedRow ? (
                        <Empty description="Selecione uma transação para ver detalhes" />
                    ) : (
                        <>
                            <Row gutter={16}>
                                <Col span={12}>
                                    <Statistic title="Valor" value={selectedRow.amount} />
                                </Col>
                                <Col span={12}>
                                    <Statistic title="Taxa" value={selectedRow.fee} />
                                </Col>
                            </Row>

                            <Divider />

                            <Space direction="vertical" size={10} className="spa-detail-stack">
                                <Space wrap>
                                    <Tag color="gold">{selectedRow.type}</Tag>
                                    <Tag color={selectedRow.status === 'Pago' ? 'green' : 'volcano'}>{selectedRow.status}</Tag>
                                </Space>
                                <Typography.Text strong>{selectedRow.customer}</Typography.Text>
                                <Typography.Text type="secondary">{selectedRow.establishment}</Typography.Text>
                                <Typography.Text type="secondary">{selectedRow.created_at}</Typography.Text>
                            </Space>

                            <Divider />

                            <Typography.Title level={4} className="spa-section-title">
                                Links recentes
                            </Typography.Title>

                            <List
                                dataSource={payload.recent_links}
                                renderItem={(item) => (
                                    <List.Item className="spa-quick-link-item">
                                        <Link to="/links-pagamento" className="spa-quick-link">
                                            <Space align="start" size={14}>
                                                <div className="spa-quick-link-icon">
                                                    {item.type === 'PIX' ? <QrcodeOutlined /> : item.type === 'Boleto' ? <BankOutlined /> : <CreditCardOutlined />}
                                                </div>
                                                <div>
                                                    <Typography.Text strong>{item.title}</Typography.Text>
                                                    <div>
                                                        <Typography.Text type="secondary">
                                                            {item.amount} • {item.status}
                                                        </Typography.Text>
                                                    </div>
                                                </div>
                                            </Space>
                                        </Link>
                                    </List.Item>
                                )}
                            />

                            <Divider />

                            <Typography.Title level={4} className="spa-section-title">
                                Atividade
                            </Typography.Title>

                            <Timeline
                                items={visibleRows.slice(0, 4).map((item) => ({
                                    color: item.status === 'Pago' ? 'green' : 'gold',
                                    children: (
                                        <div>
                                            <div>{item.customer}</div>
                                            <Typography.Text type="secondary">
                                                {item.type} • {item.created_at}
                                            </Typography.Text>
                                        </div>
                                    ),
                                }))}
                                className="spa-timeline"
                            />
                        </>
                    )}
                </Card>
            </Col>
        </Row>
    );
}

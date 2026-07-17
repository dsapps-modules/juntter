import {
    BankOutlined,
    CreditCardOutlined,
    EyeOutlined,
    LinkOutlined,
    QrcodeOutlined,
    ReloadOutlined,
} from '@ant-design/icons';
import {
    Alert,
    Button,
    Card,
    Col,
    Empty,
    Input,
    Row,
    Segmented,
    Select,
    Skeleton,
    Space,
    Statistic,
    Table,
    Tag,
    Typography,
} from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';

const defaultPayload = {
    summary: {
        total_links: 0,
        active_links: 0,
        inactive_links: 0,
        expired_links: 0,
        paid_links: 0,
        card_links: 0,
        pix_links: 0,
        boleto_links: 0,
        total_value: 'R$ 0,00',
    },
    periods: [],
    selected_period: 'all',
    rows: [],
};

const filters = ['Todos', 'Ativos', 'Pagos', 'Expirados'];

function getCurrentPeriod() {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
}

function formatPeriodLabel(period) {
    const [year, month] = period.split('-');

    return `${month}/${year}`;
}

function formatType(type) {
    switch (type) {
        case 'PIX':
            return 'PIX';
        case 'BOLETO':
            return 'Boleto';
        default:
            return 'Cartão';
    }
}

function getTypeIcon(type) {
    switch (type) {
        case 'PIX':
            return <QrcodeOutlined />;
        case 'BOLETO':
            return <BankOutlined />;
        default:
            return <CreditCardOutlined />;
    }
}

function getStatusColor(status) {
    switch (status) {
        case 'Ativo':
            return 'green';
        case 'Pago':
            return 'gold';
        case 'Expirado':
            return 'volcano';
        case 'Inativo':
            return 'red';
        default:
            return 'default';
    }
}

function matchesFilter(filter, item) {
    switch (filter) {
        case 'Ativos':
            return item.status === 'Ativo';
        case 'Pagos':
            return item.status === 'Pago';
        case 'Expirados':
            return item.status === 'Expirado';
        default:
            return true;
    }
}

export default function LinksPagamentoPage() {
    const navigate = useNavigate();
    const currentPeriod = getCurrentPeriod();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState(defaultPayload);
    const [filter, setFilter] = useState('Todos');
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedPeriod, setSelectedPeriod] = useState(currentPeriod);
    const [reloadToken, setReloadToken] = useState(0);

    useEffect(() => {
        const controller = new AbortController();

        async function loadOverview() {
            setLoading(true);
            setError('');

            try {
                const params = new URLSearchParams();
                params.set('period', selectedPeriod);

                const response = await fetch(`/api/spa/links-pagamento?${params.toString()}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar os links de pagamento.');
                }

                const data = await response.json();
                setPayload((current) => ({
                    ...current,
                    ...data,
                    summary: data.summary ?? current.summary,
                    rows: data.rows ?? [],
                    periods: data.periods ?? current.periods,
                    selected_period: data.selected_period ?? current.selected_period,
                }));
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar os links de pagamento.');
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

        (payload.periods ?? []).forEach((item) => {
            if (item?.value && !optionsByValue.has(item.value)) {
                optionsByValue.set(item.value, item);
            }
        });

        return Array.from(optionsByValue.values());
    }, [currentPeriod, payload.periods]);

    const visibleRows = useMemo(() => {
        return (payload.rows ?? []).filter((item) => {
            const text = `${item.title} ${item.description} ${item.type} ${item.status}`.toLowerCase();
            const matchesSearch = text.includes(searchTerm.toLowerCase());
            const matchesSelectedFilter = matchesFilter(filter, item);

            return matchesSearch && matchesSelectedFilter;
        });
    }, [filter, payload.rows, searchTerm]);

    const columns = [
        {
            title: 'Link',
            dataIndex: 'title',
            render: (_, record) => (
                <Space direction="vertical" size={2}>
                    <Typography.Text strong>{record.title}</Typography.Text>
                    <Typography.Text type="secondary">{record.description}</Typography.Text>
                </Space>
            ),
        },
        {
            title: 'Tipo',
            dataIndex: 'type',
            render: (value) => (
                <Tag color="blue">
                    <Space size={6}>
                        {getTypeIcon(value)}
                        <span>{value}</span>
                    </Space>
                </Tag>
            ),
            width: 140,
        },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (value) => <Tag color={getStatusColor(value)}>{value}</Tag>,
            width: 130,
        },
        {
            title: 'Valor',
            dataIndex: 'amount',
            width: 140,
        },
        {
            title: 'Criado em',
            dataIndex: 'created_at',
            width: 160,
        },
        {
            title: '',
            key: 'actions',
            width: 88,
            render: (_, record) => (
                <Button
                    icon={<EyeOutlined />}
                    onClick={() => navigate(record.detail_href || `/links-pagamento/${record.id}`)}
                    title="Ver detalhes"
                    aria-label="Ver detalhes"
                />
            ),
        },
    ];

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24}>
                <Card
                    className="spa-table-card spa-pix-page-card"
                    title="Links de pagamento do mês"
                    extra={(
                        <Select
                            className="spa-period-select"
                            value={selectedPeriod}
                            options={periodOptions}
                            onChange={setSelectedPeriod}
                            size="middle"
                            style={{ minWidth: 176, width: 'auto' }}
                        />
                    )}
                >
                    <Space direction="vertical" size={18} style={{ width: '100%' }}>
                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={6}>
                                <Statistic title="Total" value={payload.summary.total_links} />
                            </Col>
                            <Col xs={24} md={6}>
                                <Statistic title="Ativos" value={payload.summary.active_links} />
                            </Col>
                            <Col xs={24} md={6}>
                                <Statistic title="Pagos" value={payload.summary.paid_links} />
                            </Col>
                            <Col xs={24} md={6}>
                                <Statistic title="Valor total" value={payload.summary.total_value} />
                            </Col>
                        </Row>

                        <div className="spa-filter-row">
                            <Segmented value={filter} options={filters} onChange={setFilter} className="spa-segmented" />

                            <div className="spa-search-group">
                                <Input
                                    allowClear
                                    prefix={<LinkOutlined />}
                                    className="spa-search-input"
                                    placeholder="Buscar título, descrição, tipo ou status"
                                    value={searchTerm}
                                    onChange={(event) => setSearchTerm(event.target.value)}
                                />
                                <Button
                                    htmlType="button"
                                    icon={<ReloadOutlined />}
                                    className="spa-secondary-button"
                                    onClick={() => setReloadToken((current) => current + 1)}
                                    aria-label="Atualizar dados"
                                    title="Atualizar dados"
                                />
                            </div>
                        </div>

                        {error ? <Alert type="error" message="Falha ao carregar dados" description={error} showIcon /> : null}

                        {loading ? (
                            <Skeleton active paragraph={{ rows: 6 }} />
                        ) : visibleRows.length === 0 ? (
                            <Empty description="Nenhum link de pagamento encontrado neste mês" />
                        ) : (
                            <Table
                                rowKey="id"
                                columns={columns}
                                dataSource={visibleRows}
                                pagination={{
                                    pageSize: 10,
                                    showSizeChanger: false,
                                    hideOnSinglePage: true,
                                }}
                                className="spa-table spa-pix-transactions-table"
                                rowClassName={() => 'spa-pix-table-row'}
                                onRow={(record) => ({
                                    onClick: () => navigate(record.detail_href || `/links-pagamento/${record.id}`),
                                    style: { cursor: 'pointer' },
                                })}
                            />
                        )}
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

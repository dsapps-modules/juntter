import { BankOutlined, CreditCardOutlined, EllipsisOutlined, QrcodeOutlined, ThunderboltOutlined } from '@ant-design/icons';
import { Alert, Avatar, Button, Card, Col, Divider, Empty, Input, List, Row, Segmented, Skeleton, Space, Statistic, Table, Tag, Timeline, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
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
    rows: [],
    selected: null,
    recent_links: [],
    actions: [],
};

const filters = ['Todos', 'Ativos', 'Expirados', 'Pagos'];

const filterMatcher = {
    Todos: () => true,
    Ativos: (item) => item.status === 'Ativo',
    Expirados: (item) => item.status === 'Expirado',
    Pagos: (item) => item.status === 'Pago',
};

export default function LinksPagamentoPage() {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState(defaultPayload);
    const [filter, setFilter] = useState('Todos');
    const [searchTerm, setSearchTerm] = useState('');

    useEffect(() => {
        const controller = new AbortController();

        async function loadOverview() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch('/api/spa/links-pagamento', {
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
                    selected: data.selected ?? data.rows?.[0] ?? null,
                    recent_links: data.recent_links ?? [],
                    actions: data.actions ?? [],
                }));
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar os links.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadOverview();

        return () => controller.abort();
    }, []);

    const visibleRows = useMemo(() => {
        return payload.rows.filter((item) => {
            const text = `${item.title} ${item.description} ${item.type} ${item.status}`.toLowerCase();
            return text.includes(searchTerm.toLowerCase()) && (filterMatcher[filter] ?? filterMatcher.Todos)(item);
        });
    }, [filter, payload.rows, searchTerm]);

    const selectedRow = visibleRows.find((item) => item.id === payload.selected?.id) ?? visibleRows[0] ?? payload.selected;

    const columns = [
        {
            title: 'Título',
            dataIndex: 'title',
            render: (_, record) => (
                <Space size={14}>
                    <Avatar className="spa-row-avatar">
                        {record.type?.slice(0, 2)?.toUpperCase() ?? 'LK'}
                    </Avatar>
                    <div>
                        <Typography.Text strong>{record.title}</Typography.Text>
                        <div>
                            <Typography.Text type="secondary">{record.description}</Typography.Text>
                        </div>
                    </div>
                </Space>
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
            render: (value) => <Tag color={value === 'Ativo' ? 'green' : value === 'Expirado' ? 'volcano' : 'gold'}>{value}</Tag>,
        },
        {
            title: 'Valor',
            dataIndex: 'amount',
        },
        {
            title: 'Parcelas',
            dataIndex: 'max_installments',
            render: (value) => `${value}x`,
        },
    ];

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} xl={16}>
                <Row gutter={[20, 20]}>
                    <Col span={24}>
                        <Card className="spa-toolbar-card">
                            <Space direction="vertical" size={16} className="spa-toolbar-stack">
                                <Row gutter={[16, 16]} style={{ width: '100%' }}>
                                    <Col xs={24} md={8}>
                                        <Statistic title="Links" value={payload.summary.total_links} />
                                    </Col>
                                    <Col xs={24} md={8}>
                                        <Statistic title="Ativos" value={payload.summary.active_links} />
                                    </Col>
                                    <Col xs={24} md={8}>
                                        <Statistic title="Valor total" value={payload.summary.total_value} />
                                    </Col>
                                </Row>

                                <Segmented value={filter} options={filters} onChange={setFilter} className="spa-segmented" />

                                <Space wrap className="spa-filter-row">
                                    <Input
                                        allowClear
                                        prefix={<CreditCardOutlined />}
                                        className="spa-search-input"
                                        placeholder="Buscar título, descrição ou status"
                                        value={searchTerm}
                                        onChange={(event) => setSearchTerm(event.target.value)}
                                    />
                                    <Button icon={<ThunderboltOutlined />} className="spa-secondary-button">
                                        Atualizar
                                    </Button>
                                </Space>
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
                                <Empty description="Nenhum link encontrado" />
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
                <Card className="spa-quick-view-card" title={selectedRow ? `Quick View: ${selectedRow.title}` : 'Quick View'} extra={<EllipsisOutlined />}>
                    {!selectedRow ? (
                        <Empty description="Selecione um link para ver detalhes" />
                    ) : (
                        <>
                            <Row gutter={16}>
                                <Col span={12}>
                                    <Statistic title="Valor" value={selectedRow.amount} />
                                </Col>
                                <Col span={12}>
                                    <Statistic title="Parcelas" value={`${selectedRow.max_installments}x`} />
                                </Col>
                            </Row>

                            <Divider />

                            <Space direction="vertical" size={10} className="spa-detail-stack">
                                <Space wrap>
                                    <Tag color="gold">{selectedRow.type}</Tag>
                                    <Tag color={selectedRow.status === 'Ativo' ? 'green' : 'volcano'}>{selectedRow.status}</Tag>
                                </Space>
                                <Typography.Text strong>{selectedRow.title}</Typography.Text>
                                <Typography.Text type="secondary">{selectedRow.description}</Typography.Text>
                                <Typography.Text type="secondary">{selectedRow.created_at}</Typography.Text>
                                <Typography.Text type="secondary">Expira em {selectedRow.expires_at}</Typography.Text>
                            </Space>

                            <Divider />

                            <Typography.Title level={4} className="spa-section-title">
                                Ações rápidas
                            </Typography.Title>

                            <Space wrap>
                                <Button type="primary" icon={<CreditCardOutlined />} onClick={() => navigate('/links-pagamento/novo')}>
                                    Novo link
                                </Button>
                                <Button onClick={() => navigate(`/links-pagamento/${selectedRow.id}/editar`)}>
                                    Editar
                                </Button>
                            </Space>

                            <List
                                dataSource={payload.actions}
                                renderItem={(item) => (
                                    <List.Item className="spa-quick-link-item">
                                        <Link to={item.href} className="spa-quick-link">
                                            <Space align="start" size={14}>
                                                <div className="spa-quick-link-icon">
                                                    {item.title.includes('PIX') ? <QrcodeOutlined /> : item.title.includes('boleto') ? <BankOutlined /> : <CreditCardOutlined />}
                                                </div>
                                                <div>
                                                    <Typography.Text strong>{item.title}</Typography.Text>
                                                    <div>
                                                        <Typography.Text type="secondary">{item.description}</Typography.Text>
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
                                    color: item.status === 'Ativo' ? 'green' : 'gold',
                                    children: (
                                        <div>
                                            <div>{item.title}</div>
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

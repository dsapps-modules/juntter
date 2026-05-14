import {
    CreditCardOutlined,
    EllipsisOutlined,
    EyeOutlined,
    ThunderboltOutlined,
} from '@ant-design/icons';
import {
    Alert,
    Avatar,
    Button,
    Card,
    Col,
    Divider,
    Empty,
    Input,
    List,
    Row,
    Segmented,
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
    rows: [],
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

function buildDetailHref(record) {
    return record.detail_href || `/links-pagamento/${record.id}`;
}

export default function LinksPagamentoPage() {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState(defaultPayload);
    const [filter, setFilter] = useState('Todos');
    const [searchTerm, setSearchTerm] = useState('');
    const [reloadToken, setReloadToken] = useState(0);

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
    }, [reloadToken]);

    const visibleRows = useMemo(() => {
        const term = searchTerm.toLowerCase();

        return payload.rows.filter((item) => {
            const text = `${item.title} ${item.description} ${item.type} ${item.status}`.toLowerCase();

            return text.includes(term) && (filterMatcher[filter] ?? filterMatcher.Todos)(item);
        });
    }, [filter, payload.rows, searchTerm]);

    const columns = [
        {
            title: 'Título',
            dataIndex: 'title',
            render: (_, record) => (
                <Space size={14}>
                    <Avatar className="spa-row-avatar">{record.type?.slice(0, 2)?.toUpperCase() ?? 'LK'}</Avatar>
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
                                    <Button
                                        icon={<ThunderboltOutlined />}
                                        className="spa-secondary-button"
                                        onClick={() => setReloadToken((current) => current + 1)}
                                    >
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
                                        onClick: () => navigate(buildDetailHref(record)),
                                        style: { cursor: 'pointer' },
                                    })}
                                />
                            )}
                        </Card>
                    </Col>
                </Row>
            </Col>

            <Col xs={24} xl={8}>
                <Card
                    className="spa-quick-view-card"
                    title={(
                        <Space align="center" size={10}>
                            <EllipsisOutlined />
                            <span>Ações e atalhos</span>
                        </Space>
                    )}
                    bordered={false}
                >
                    <Space direction="vertical" size={16} style={{ width: '100%' }}>
                        <Row gutter={[12, 12]}>
                            {[
                                ['Cartão', payload.summary.card_links],
                                ['PIX', payload.summary.pix_links],
                                ['Boleto', payload.summary.boleto_links],
                                ['Pagos', payload.summary.paid_links],
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
                                <Button type="primary" block onClick={() => navigate('/links-pagamento/novo')}>
                                    Novo link
                                </Button>
                                <Button block onClick={() => navigate('/links-pagamento/novo?tipo=PIX')}>
                                    Novo PIX
                                </Button>
                                <Button block onClick={() => navigate('/links-pagamento/novo?tipo=BOLETO')}>
                                    Novo boleto
                                </Button>
                            </Space>
                        </Card>

                        <Card size="small" title="Últimos links" bordered={false}>
                            {payload.recent_links.length === 0 ? (
                                <Empty description="Nenhum link recente encontrado." />
                            ) : (
                                <List
                                    dataSource={payload.recent_links}
                                    renderItem={(item) => (
                                        <List.Item className="spa-quick-link-item">
                                            <Space direction="vertical" size={2} style={{ width: '100%' }}>
                                                <Typography.Text strong>{item.title}</Typography.Text>
                                                <Typography.Text type="secondary">{item.code}</Typography.Text>
                                                <Space wrap>
                                                    <Tag color="green">{item.amount}</Tag>
                                                    <Tag color={item.status === 'Ativo' ? 'green' : 'gold'}>{item.status}</Tag>
                                                </Space>
                                                <Typography.Text type="secondary">{item.expires_at}</Typography.Text>
                                                <Button
                                                    size="small"
                                                    icon={<EyeOutlined />}
                                                    onClick={() => navigate(buildDetailHref(item))}
                                                >
                                                    Abrir detalhes
                                                </Button>
                                            </Space>
                                        </List.Item>
                                    )}
                                />
                            )}
                        </Card>

                        <Divider />

                        <Typography.Title level={4} className="spa-section-title">
                            Observação
                        </Typography.Title>
                        <Typography.Paragraph type="secondary" style={{ marginBottom: 0 }}>
                            Cada linha da lista abre a página de detalhes do link, onde é possível copiar a URL pública,
                            editar, ativar/desativar e excluir o cadastro.
                        </Typography.Paragraph>
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

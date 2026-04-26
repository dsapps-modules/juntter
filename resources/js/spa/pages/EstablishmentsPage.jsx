import {
    ArrowRightOutlined,
    CloseOutlined,
    EyeOutlined,
    LineChartOutlined,
    MoreOutlined,
    SearchOutlined,
} from '@ant-design/icons';
import {
    Alert,
    Button,
    Card,
    Col,
    Divider,
    Empty,
    Input,
    Row,
    Segmented,
    Skeleton,
    Space,
    Statistic,
    Table,
    Tag,
    Timeline,
    Typography,
} from 'antd';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import StatusPill from '../components/StatusPill';

const defaultFilters = ['Todos', 'Ativos', 'Inativos'];

const statusColors = {
    Ativo: 'gold',
    Bloqueado: 'volcano',
    'Em anÃƒÂ¡lise': 'default',
    Inativo: 'red',
};

const defaultPagination = {
    current_page: 1,
    per_page: 20,
    total: 0,
    last_page: 1,
};

const defaultPayload = {
    summary: {
        total_establishments: 0,
        active_establishments: 0,
        blocked_establishments: 0,
        total_revenue: 'R$ 0,00',
    },
    rows: [],
    selected: null,
    recent_transactions: [],
    pagination: defaultPagination,
};

export default function EstablishmentsPage() {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [filter, setFilter] = useState('Todos');
    const [searchTerm, setSearchTerm] = useState('');
    const [page, setPage] = useState(1);
    const [selectedId, setSelectedId] = useState(null);
    const [payload, setPayload] = useState(defaultPayload);
    const [filters, setFilters] = useState(defaultFilters);

    useEffect(() => {
        const controller = new AbortController();

        async function loadOverview() {
            setLoading(true);
            setError('');

            try {
                const params = new URLSearchParams({
                    page: String(page),
                });

                if (filter !== 'Todos') {
                    params.set('filter', filter);
                }

                if (searchTerm.trim() !== '') {
                    params.set('search', searchTerm.trim());
                }

                const response = await fetch(`/api/spa/estabelecimentos?${params.toString()}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar a listagem.');
                }

                const data = await response.json();
                const nextRows = data.rows ?? [];

                setPayload((current) => ({
                    ...current,
                    ...data,
                    summary: data.summary ?? current.summary,
                    rows: nextRows,
                    selected: data.selected ?? nextRows[0] ?? null,
                    recent_transactions: data.recent_transactions ?? [],
                    pagination: data.pagination ?? current.pagination,
                }));
                setFilters(data.filters ?? defaultFilters);
                setSelectedId(data.selected?.id ?? nextRows[0]?.id ?? null);
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar a tela.');
                }
            } finally {
                setLoading(false);
            }
        }

        const timeout = window.setTimeout(loadOverview, searchTerm.trim() !== '' ? 250 : 0);

        return () => {
            controller.abort();
            window.clearTimeout(timeout);
        };
    }, [filter, page, searchTerm]);

    const rows = payload.rows ?? [];
    const selectedRow = rows.find((item) => item.id === selectedId) ?? payload.selected ?? rows[0] ?? null;
    const pagination = payload.pagination ?? defaultPagination;

    const columns = [
        {
            title: 'Cliente',
            dataIndex: 'name',
            render: (_, record) => (
                <div>
                    <Typography.Text strong>{record.name}</Typography.Text>
                    <div>
                        <Typography.Text type="secondary">{record.owner}</Typography.Text>
                    </div>
                </div>
            ),
        },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (value) => <Tag color={statusColors[value]}>{value}</Tag>,
        },
        {
            title: 'E-mail',
            dataIndex: 'email',
            render: (value) => <Typography.Text>{value}</Typography.Text>,
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
                                        <Statistic title="Total" value={payload.summary.total_establishments} />
                                    </Col>
                                    <Col xs={24} md={8}>
                                        <Statistic title="Ativos" value={payload.summary.active_establishments} />
                                    </Col>
                                    <Col xs={24} md={8}>
                                        <Statistic title="Receita" value={payload.summary.total_revenue} />
                                    </Col>
                                </Row>

                                <div className="spa-filter-row">
                                    <Segmented
                                        value={filter}
                                        options={filters}
                                        onChange={(value) => {
                                            setFilter(value);
                                            setPage(1);
                                        }}
                                        className="spa-segmented"
                                    />

                                    <Input
                                        allowClear
                                        prefix={<SearchOutlined />}
                                        placeholder="Buscar nome, documento, e-mail ou cidade"
                                        value={searchTerm}
                                        onChange={(event) => {
                                            setSearchTerm(event.target.value);
                                            setPage(1);
                                        }}
                                        className="spa-search-input"
                                    />
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
                            ) : rows.length === 0 ? (
                                <Empty description="Nenhum estabelecimento encontrado" />
                            ) : (
                                <Table
                                    rowKey="id"
                                    columns={columns}
                                    dataSource={rows}
                                    pagination={{
                                        current: pagination.current_page,
                                        pageSize: pagination.per_page,
                                        total: pagination.total,
                                        showSizeChanger: false,
                                        onChange: (nextPage) => setPage(nextPage),
                                    }}
                                    className="spa-table"
                                    onRow={(record) => ({
                                        onClick: () => setSelectedId(record.id),
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
                <Card className="spa-quick-view-card" title={selectedRow ? selectedRow.name : 'Quick View'}>
                    <Button type="text" icon={<CloseOutlined />} className="spa-quick-close" />

                    {!selectedRow ? (
                        <Empty description="Selecione uma linha para ver os detalhes" />
                    ) : (
                        <>
                            <Row gutter={16}>
                                <Col span={12}>
                                    <Statistic title="Revenue" value={selectedRow.revenue} />
                                </Col>
                                <Col span={12}>
                                    <Statistic title="Active Tasks" value={selectedRow.active_tasks} />
                                </Col>
                            </Row>

                            <Divider />

                            <Space direction="vertical" size={10} className="spa-detail-stack">
                                <Space wrap>
                                    <StatusPill status={selectedRow.status} />
                                    <Tag color="default">{selectedRow.segment}</Tag>
                                </Space>
                                <Typography.Text strong>{selectedRow.email}</Typography.Text>
                                <Typography.Text type="secondary">
                                    Responsável {selectedRow.owner} - {selectedRow.city}
                                </Typography.Text>
                                <Typography.Text type="secondary">{selectedRow.phone}</Typography.Text>
                            </Space>

                            <Divider />

                            <Typography.Title level={4} className="spa-section-title">
                                Timeline
                            </Typography.Title>

                            <Timeline
                                items={(selectedRow.timeline ?? []).map((item) => ({
                                    color: item.color,
                                    children: (
                                        <div>
                                            <div>{item.title}</div>
                                            <Typography.Text type="secondary">{item.description}</Typography.Text>
                                        </div>
                                    ),
                                }))}
                                className="spa-timeline"
                            />

                            <Divider />

                            <Space wrap className="spa-action-row">
                                <Button icon={<EyeOutlined />} onClick={() => navigate(`/estabelecimentos/${selectedRow.id}`)} disabled={!selectedRow}>Visualizar</Button>
                                <Button icon={<LineChartOutlined />}>Relatórios</Button>
                                <Button icon={<MoreOutlined />}>Ações</Button>
                            </Space>

                            <Button
                                type="primary"
                                icon={<ArrowRightOutlined />}
                                className="spa-primary-button spa-full-width"
                                onClick={() => navigate(`/estabelecimentos/${selectedRow.id}/editar`)}
                            >
                                Editar
                            </Button>
                        </>
                    )}
                </Card>
            </Col>
        </Row>
    );
}

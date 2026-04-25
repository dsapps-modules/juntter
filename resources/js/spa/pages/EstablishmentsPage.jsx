import {
    ArrowRightOutlined,
    CloseOutlined,
    EyeOutlined,
    LineChartOutlined,
    MoreOutlined,
    SearchOutlined,
    SwapOutlined,
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
import { useEffect, useMemo, useState } from 'react';
import StatusPill from '../components/StatusPill';
import { useNavigate } from 'react-router-dom';

const defaultFilters = ['Todos', 'Ativos', 'Inadimplentes', 'Inativos'];

const statusColors = {
    Ativo: 'gold',
    Bloqueado: 'volcano',
    'Em análise': 'default',
    Inativo: 'red',
};

export default function EstablishmentsPage() {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [filter, setFilter] = useState('Todos');
    const [searchTerm, setSearchTerm] = useState('');
    const [rows, setRows] = useState([]);
    const [selectedId, setSelectedId] = useState(null);
    const [summary, setSummary] = useState({
        total_establishments: 0,
        active_establishments: 0,
        blocked_establishments: 0,
        total_revenue: 'R$ 0,00',
    });
    const [filters, setFilters] = useState(defaultFilters);

    useEffect(() => {
        const controller = new AbortController();

        async function loadOverview() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch('/api/spa/estabelecimentos', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar a listagem.');
                }

                const payload = await response.json();
                setRows(payload.rows ?? []);
                setSelectedId(payload.selected?.id ?? payload.rows?.[0]?.id ?? null);
                setSummary(payload.summary ?? summary);
                setFilters(payload.filters ?? defaultFilters);
                setFilter('Todos');
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar a tela.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadOverview();

        return () => controller.abort();
    }, []);

    const visibleRows = useMemo(() => {
        return rows.filter((item) => {
            const matchesSearch =
                item.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                item.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                item.owner.toLowerCase().includes(searchTerm.toLowerCase());

            if (!matchesSearch) {
                return false;
            }

            if (filter === 'Todos') {
                return true;
            }

            if (filter === 'Ativos') {
                return item.status === 'Ativo';
            }

            if (filter === 'Inadimplentes') {
                return item.status === 'Bloqueado';
            }

            return item.status === 'Inativo';
        });
    }, [filter, rows, searchTerm]);

    const selectedRow = visibleRows.find((item) => item.id === selectedId) ?? visibleRows[0] ?? null;

    useEffect(() => {
        if (selectedRow && selectedRow.id !== selectedId) {
            setSelectedId(selectedRow.id);
        }
    }, [selectedId, selectedRow]);

    const columns = [
        {
            title: 'Cliente',
            dataIndex: 'name',
            render: (_, record) => (
                <Space size={14}>
                    <Avatar className="spa-row-avatar">{record.initials}</Avatar>
                    <div>
                        <Typography.Text strong>{record.name}</Typography.Text>
                        <div>
                            <Typography.Text type="secondary">{record.owner}</Typography.Text>
                        </div>
                    </div>
                </Space>
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
        {
            title: 'Receita',
            dataIndex: 'revenue',
            render: (value) => <Typography.Text strong>{value}</Typography.Text>,
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
                                        <Statistic title="Total" value={summary.total_establishments} />
                                    </Col>
                                    <Col xs={24} md={8}>
                                        <Statistic title="Ativos" value={summary.active_establishments} />
                                    </Col>
                                    <Col xs={24} md={8}>
                                        <Statistic title="Receita" value={summary.total_revenue} />
                                    </Col>
                                </Row>

                                <Segmented
                                    value={filter}
                                    options={filters}
                                    onChange={setFilter}
                                    className="spa-segmented"
                                />

                                <Space wrap className="spa-filter-row">
                                    <Input
                                        allowClear
                                        prefix={<SearchOutlined />}
                                        placeholder="Buscar cliente, responsável ou e-mail"
                                        value={searchTerm}
                                        onChange={(event) => setSearchTerm(event.target.value)}
                                        className="spa-search-input"
                                    />
                                    <Button icon={<SwapOutlined />} className="spa-secondary-button">
                                        Ordenar
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
                                <Empty description="Nenhum estabelecimento encontrado" />
                            ) : (
                                <Table
                                    rowKey="id"
                                    columns={columns}
                                    dataSource={visibleRows}
                                    pagination={false}
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
                <Card className="spa-quick-view-card" title={selectedRow ? `Quick View: ${selectedRow.name}` : 'Quick View'}>
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
                                <Button icon={<EyeOutlined />}>Visualizar</Button>
                                <Button icon={<LineChartOutlined />}>Relatórios</Button>
                                <Button icon={<MoreOutlined />}>Ações</Button>
                            </Space>

                            <Button
                                type="primary"
                                icon={<ArrowRightOutlined />}
                                className="spa-primary-button spa-full-width"
                                onClick={() => navigate(`/estabelecimentos/${selectedRow.id}/editar`)}
                            >
                                Edit Details
                            </Button>
                        </>
                    )}
                </Card>
            </Col>
        </Row>
    );
}

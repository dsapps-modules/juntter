import { ArrowRightOutlined, EllipsisOutlined, SafetyOutlined, ThunderboltOutlined, TeamOutlined } from '@ant-design/icons';
import { Alert, Avatar, Button, Card, Col, Divider, Empty, Input, List, Row, Segmented, Skeleton, Space, Statistic, Table, Tag, Timeline, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

const defaultPayload = {
    summary: {
        total_vendors: 0,
        active_vendors: 0,
        inactive_vendors: 0,
        admin_loja: 0,
        vendedor_loja: 0,
        must_change_password: 0,
        linked_establishments: 0,
    },
    rows: [],
    selected: null,
    recent_activity: [],
    actions: [],
};

const filters = ['Todos', 'Ativos', 'Inativos', 'Senha obrigatória'];

const filterMatcher = {
    Todos: () => true,
    Ativos: (item) => item.status === 'Ativo',
    Inativos: (item) => item.status === 'Inativo',
    'Senha obrigatória': (item) => item.must_change_password,
};

export default function VendedoresPage() {
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
                const response = await fetch('/api/spa/vendedores', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar os vendedores.');
                }

                const data = await response.json();
                setPayload((current) => ({
                    ...current,
                    ...data,
                    summary: data.summary ?? current.summary,
                    rows: data.rows ?? [],
                    selected: data.selected ?? data.rows?.[0] ?? null,
                    recent_activity: data.recent_activity ?? [],
                    actions: data.actions ?? [],
                }));
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar os vendedores.');
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
            const text = `${item.name} ${item.email} ${item.establishment} ${item.role}`.toLowerCase();
            return text.includes(searchTerm.toLowerCase()) && (filterMatcher[filter] ?? filterMatcher.Todos)(item);
        });
    }, [filter, payload.rows, searchTerm]);

    const selectedRow = visibleRows.find((item) => item.id === payload.selected?.id) ?? visibleRows[0] ?? payload.selected;

    const columns = [
        {
            title: 'Usuário',
            dataIndex: 'name',
            render: (_, record) => (
                <Space size={14}>
                    <Avatar className="spa-row-avatar">{record.name?.slice(0, 2)?.toUpperCase() ?? 'VT'}</Avatar>
                    <div>
                        <Typography.Text strong>{record.name}</Typography.Text>
                        <div>
                            <Typography.Text type="secondary">{record.email}</Typography.Text>
                        </div>
                    </div>
                </Space>
            ),
        },
        {
            title: 'Perfil',
            dataIndex: 'role',
            render: (value) => <Tag color="gold">{value}</Tag>,
        },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (value) => <Tag color={value === 'Ativo' ? 'green' : 'volcano'}>{value}</Tag>,
        },
        {
            title: 'Estabelecimento',
            dataIndex: 'establishment',
        },
        {
            title: 'Transações',
            dataIndex: 'total_transactions',
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
                                        <Statistic title="Vendedores" value={payload.summary.total_vendors} />
                                    </Col>
                                    <Col xs={24} md={8}>
                                        <Statistic title="Ativos" value={payload.summary.active_vendors} />
                                    </Col>
                                    <Col xs={24} md={8}>
                                        <Statistic title="Senha obrigatória" value={payload.summary.must_change_password} />
                                    </Col>
                                </Row>

                                <Segmented value={filter} options={filters} onChange={setFilter} className="spa-segmented" />

                                <Space wrap className="spa-filter-row">
                                    <Input
                                        allowClear
                                        prefix={<TeamOutlined />}
                                        className="spa-search-input"
                                        placeholder="Buscar usuário, e-mail, estabelecimento ou perfil"
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
                                <Empty description="Nenhum vendedor encontrado" />
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
                <Card className="spa-quick-view-card" title={selectedRow ? `Quick View: ${selectedRow.name}` : 'Quick View'} extra={<EllipsisOutlined />}>
                    {!selectedRow ? (
                        <Empty description="Selecione um vendedor para ver detalhes" />
                    ) : (
                        <>
                            <Row gutter={16}>
                                <Col span={12}>
                                    <Statistic title="Volume" value={selectedRow.total_amount} />
                                </Col>
                                <Col span={12}>
                                    <Statistic title="Transações" value={selectedRow.total_transactions} />
                                </Col>
                            </Row>

                            <Divider />

                            <Space direction="vertical" size={10} className="spa-detail-stack">
                                <Space wrap>
                                    <Tag color="gold">{selectedRow.role}</Tag>
                                    <Tag color={selectedRow.status === 'Ativo' ? 'green' : 'volcano'}>{selectedRow.status}</Tag>
                                </Space>
                                <Typography.Text strong>{selectedRow.name}</Typography.Text>
                                <Typography.Text type="secondary">{selectedRow.email}</Typography.Text>
                                <Typography.Text type="secondary">{selectedRow.establishment}</Typography.Text>
                                <Typography.Text type="secondary">Última atividade {selectedRow.last_activity}</Typography.Text>
                            </Space>

                            <Divider />

                            <Typography.Title level={4} className="spa-section-title">
                                Ações rápidas
                            </Typography.Title>

                            <List
                                dataSource={payload.actions}
                                renderItem={(item) => (
                                    <List.Item className="spa-quick-link-item">
                                        <Link to={item.href} className="spa-quick-link">
                                            <Space align="start" size={14}>
                                                <div className="spa-quick-link-icon">
                                                    <SafetyOutlined />
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
                                            <div>{item.name}</div>
                                            <Typography.Text type="secondary">
                                                {item.role} • {item.last_activity}
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

import {
    ArrowRightOutlined,
    BankOutlined,
    CreditCardOutlined,
    EllipsisOutlined,
    ThunderboltOutlined,
    TeamOutlined,
} from '@ant-design/icons';
import { Alert, Avatar, Button, Card, Col, Divider, List, Row, Skeleton, Space, Statistic, Table, Tag, Timeline, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

const defaultPayload = {
    user: {
        name: 'Usuário',
        email: '',
        nivel_label: '',
        verified: false,
        must_change_password: false,
        created_at: '',
    },
    summary: {
        total_establishments: 0,
        active_establishments: 0,
        blocked_establishments: 0,
        total_transactions: 0,
        pending_transactions: 0,
        today_transactions: 0,
        total_revenue: 'R$ 0,00',
    },
    rows: [],
    selected: null,
    recent_transactions: [],
    actions: [],
};

export default function HomePage() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState(defaultPayload);

    useEffect(() => {
        const controller = new AbortController();

        async function loadHome() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch('/api/spa/dashboard', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar o painel.');
                }

                const data = await response.json();
                setPayload((current) => ({
                    ...current,
                    ...data,
                    summary: data.summary ?? current.summary,
                    user: data.user ?? current.user,
                    rows: data.rows ?? [],
                    selected: data.selected ?? data.rows?.[0] ?? null,
                    recent_transactions: data.recent_transactions ?? [],
                    actions: data.actions ?? [],
                }));
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar o painel.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadHome();

        return () => controller.abort();
    }, []);

    const topRows = useMemo(() => payload.rows.slice(0, 5), [payload.rows]);
    const selectedRow = payload.selected ?? payload.rows[0] ?? null;

    const topColumns = [
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
            render: (value) => <Tag color={value === 'Ativo' ? 'gold' : 'volcano'}>{value}</Tag>,
        },
        {
            title: 'Receita',
            dataIndex: 'revenue',
        },
    ];

    return (
        <Row gutter={[20, 20]} className="spa-home-grid">
            <Col xs={24} xl={16}>
                <Card className="spa-hero-card">
                    <Space direction="vertical" size={18} className="spa-hero-stack">
                        <div>
                            <Typography.Text className="spa-brand-kicker">
                                {payload.user.nivel_label || 'Painel operacional'}
                            </Typography.Text>
                            <Typography.Title level={2} className="spa-hero-title">
                                Olá, {payload.user.name}.
                            </Typography.Title>
                            <Typography.Paragraph className="spa-hero-description">
                                Este painel consolida a operação em uma leitura rápida: volume, status, atividade recente e
                                atalhos para os módulos migrados.
                            </Typography.Paragraph>
                        </div>

                        <Space wrap>
                            <Button type="primary" icon={<ArrowRightOutlined />} className="spa-primary-button">
                                Ir para estabelecimentos
                            </Button>
                            <Button className="spa-secondary-button" icon={<ThunderboltOutlined />}>
                                Ver atividade
                            </Button>
                        </Space>

                        <Row gutter={[16, 16]}>
                            <Col xs={24} sm={12} lg={6}>
                                <Statistic title="Estabelecimentos" value={payload.summary.total_establishments} />
                            </Col>
                            <Col xs={24} sm={12} lg={6}>
                                <Statistic title="Ativos" value={payload.summary.active_establishments} />
                            </Col>
                            <Col xs={24} sm={12} lg={6}>
                                <Statistic title="Hoje" value={payload.summary.today_transactions} />
                            </Col>
                            <Col xs={24} sm={12} lg={6}>
                                <Statistic title="Receita" value={payload.summary.total_revenue} />
                            </Col>
                        </Row>
                    </Space>
                </Card>

                <Card
                    title="Clientes recentes"
                    className="spa-table-card spa-home-table-card"
                    extra={<Link to="/estabelecimentos">Ver tudo</Link>}
                >
                    {loading ? (
                        <Skeleton active paragraph={{ rows: 5 }} />
                    ) : error ? (
                        <Alert type="error" showIcon message="Falha ao carregar o painel" description={error} />
                    ) : (
                        <Table
                            rowKey="id"
                            columns={topColumns}
                            dataSource={topRows}
                            pagination={false}
                            size="middle"
                            className="spa-table"
                        />
                    )}
                </Card>
            </Col>

            <Col xs={24} xl={8}>
                <Card
                    className="spa-quick-view-card"
                    title={selectedRow ? `Quick View: ${selectedRow.name}` : 'Quick View'}
                    extra={<EllipsisOutlined />}
                >
                    <Space direction="vertical" size={16} className="spa-detail-stack">
                        <Row gutter={16}>
                            <Col span={12}>
                                <Statistic title="Atividade" value={payload.summary.total_transactions} />
                            </Col>
                            <Col span={12}>
                                <Statistic title="Pendentes" value={payload.summary.pending_transactions} />
                            </Col>
                        </Row>

                        <Divider />

                        <Typography.Title level={4} className="spa-section-title">
                            Acessos rápidos
                        </Typography.Title>

                        <List
                            dataSource={payload.actions}
                            renderItem={(item, index) => (
                                <List.Item className="spa-quick-link-item">
                                    <Link to={item.href} className="spa-quick-link">
                                        <Space align="start" size={14}>
                                            <div className="spa-quick-link-icon">
                                                {index === 0 ? <BankOutlined /> : index === 1 ? <CreditCardOutlined /> : <TeamOutlined />}
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

                        <Space direction="vertical" size={10} className="spa-detail-stack">
                            <Space wrap>
                                <Avatar className="spa-row-avatar">{selectedRow?.initials ?? 'JT'}</Avatar>
                                <div>
                                    <Typography.Text strong>{selectedRow?.name ?? 'Sem dados'}</Typography.Text>
                                    <div>
                                        <Typography.Text type="secondary">
                                            {selectedRow?.owner ?? 'Nenhum estabelecimento disponível'}
                                        </Typography.Text>
                                    </div>
                                </div>
                            </Space>

                            <Space wrap>
                                <Tag color="gold">{selectedRow?.status ?? 'Sem status'}</Tag>
                                <Tag color="default">{selectedRow?.segment ?? 'Geral'}</Tag>
                            </Space>

                            <Typography.Text type="secondary">
                                {selectedRow?.email ?? 'N/A'} • {selectedRow?.city ?? 'N/A'}
                            </Typography.Text>
                        </Space>

                        <Divider />

                        <Typography.Title level={4} className="spa-section-title">
                            Últimas movimentações
                        </Typography.Title>

                        <Timeline
                            items={(payload.recent_transactions || []).slice(0, 4).map((item) => ({
                                color: 'gold',
                                children: (
                                    <div>
                                        <div>{item.establishment}</div>
                                        <Typography.Text type="secondary">
                                            {item.type} • {item.created_at}
                                        </Typography.Text>
                                    </div>
                                ),
                            }))}
                            className="spa-timeline"
                        />
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

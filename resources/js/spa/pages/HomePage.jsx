import {
    ArrowRightOutlined,
    BankOutlined,
    CreditCardOutlined,
    FireOutlined,
    TeamOutlined,
} from '@ant-design/icons';
import { Alert, Button, Card, Col, Divider, List, Row, Skeleton, Space, Statistic, Table, Tag, Timeline, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

export default function HomePage() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState({
        summary: {
            total_establishments: 0,
            active_establishments: 0,
            blocked_establishments: 0,
            total_revenue: 'R$ 0,00',
        },
        rows: [],
        recent_transactions: [],
    });

    useEffect(() => {
        const controller = new AbortController();

        async function loadHome() {
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
                    throw new Error('Não foi possível carregar a home.');
                }

                const data = await response.json();
                setPayload({
                    summary: data.summary ?? payload.summary,
                    rows: data.rows ?? [],
                    recent_transactions: data.recent_transactions ?? [],
                });
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar a home.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadHome();

        return () => controller.abort();
    }, []);

    const topRows = useMemo(() => payload.rows.slice(0, 5), [payload.rows]);

    const topColumns = [
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
            render: (value) => <Tag color={value === 'Ativo' ? 'gold' : 'volcano'}>{value}</Tag>,
        },
        {
            title: 'Receita',
            dataIndex: 'revenue',
        },
    ];

    const quickLinks = [
        { title: 'Estabelecimentos', description: 'Cadastro e monitoramento de contas.', icon: <BankOutlined />, href: '/estabelecimentos' },
        { title: 'Cobrança', description: 'Fluxos de cartão, PIX e boleto.', icon: <CreditCardOutlined />, href: '/cobranca' },
        { title: 'Vendedores', description: 'Acesso, faturamento e permissões.', icon: <TeamOutlined />, href: '/vendedores' },
    ];

    return (
        <Row gutter={[20, 20]} className="spa-home-grid">
            <Col xs={24} xl={16}>
                <Card className="spa-hero-card">
                    <Space direction="vertical" size={18} className="spa-hero-stack">
                        <div>
                            <Typography.Text className="spa-brand-kicker">Home operacional</Typography.Text>
                            <Typography.Title level={2} className="spa-hero-title">
                                Controle central com foco em performance, status e ação rápida.
                            </Typography.Title>
                            <Typography.Paragraph className="spa-hero-description">
                                Esta é a primeira home React da migração. O layout já segue a direção visual definida:
                                superfícies claras, cartão destacado, amarelo como cor de ação e leitura rápida do estado do negócio.
                            </Typography.Paragraph>
                        </div>

                        <Space wrap>
                            <Button type="primary" icon={<ArrowRightOutlined />} className="spa-primary-button">
                                Ir para estabelecimentos
                            </Button>
                            <Button className="spa-secondary-button" icon={<FireOutlined />}>
                                Ver pendências
                            </Button>
                        </Space>

                        <Row gutter={[16, 16]}>
                            <Col xs={24} sm={12} lg={6}>
                                <Statistic title="Total" value={payload.summary.total_establishments} />
                            </Col>
                            <Col xs={24} sm={12} lg={6}>
                                <Statistic title="Ativos" value={payload.summary.active_establishments} />
                            </Col>
                            <Col xs={24} sm={12} lg={6}>
                                <Statistic title="Bloqueados" value={payload.summary.blocked_establishments} />
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
                    extra={<Link to="/app/estabelecimentos">Ver tudo</Link>}
                >
                    {loading ? (
                        <Skeleton active paragraph={{ rows: 5 }} />
                    ) : error ? (
                        <Alert type="error" showIcon message="Falha ao carregar a home" description={error} />
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
                <Card className="spa-quick-view-card" title="Atalhos e atividade">
                    <Space direction="vertical" size={16} className="spa-detail-stack">
                        <List
                            dataSource={quickLinks}
                            renderItem={(item) => (
                                <List.Item className="spa-quick-link-item">
                                    <Link to={item.href} className="spa-quick-link">
                                        <Space align="start" size={14}>
                                            <div className="spa-quick-link-icon">{item.icon}</div>
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

                        <Divider />

                        <Card className="spa-mini-surface" bordered={false}>
                            <Space direction="vertical" size={6}>
                                <Typography.Text className="spa-placeholder-kicker">Próximo corte</Typography.Text>
                                <Typography.Title level={4} className="spa-mini-title">
                                    Monitoramento de cobranças
                                </Typography.Title>
                                <Typography.Text type="secondary">
                                    A próxima fase vai encaixar o módulo de cobrança com o mesmo padrão visual.
                                </Typography.Text>
                            </Space>
                        </Card>
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

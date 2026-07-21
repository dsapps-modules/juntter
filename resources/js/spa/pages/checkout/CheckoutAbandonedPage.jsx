import { Button, Card, Col, Empty, Row, Space, Spin, Table, Tag, Typography, message } from 'antd';
import dayjs from 'dayjs';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

const recoveryStatusLabels = {
    pending: 'pendente',
    in_progress: 'em andamento',
    failed: 'falhou',
    skipped: 'ignorado',
};

const recoveryStatusColors = {
    pending: 'gold',
    in_progress: 'blue',
    failed: 'red',
    skipped: 'default',
};

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(Number(value ?? 0));
}

function formatDateTime(value) {
    return value ? dayjs(value).format('DD/MM/YYYY HH:mm') : '-';
}

export default function CheckoutAbandonedPage() {
    const navigate = useNavigate();
    const [sessions, setSessions] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const controller = new AbortController();

        async function loadAbandonedSessions() {
            setLoading(true);

            try {
                const response = await fetch('/seller/checkout-links/abandonados', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar os carrinhos abandonados.');
                }

                const data = await response.json();
                setSessions(data.abandoned_sessions ?? []);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    message.error(error.message || 'Falha ao carregar os carrinhos abandonados.');
                    setSessions([]);
                }
            } finally {
                setLoading(false);
            }
        }

        loadAbandonedSessions();

        return () => controller.abort();
    }, []);

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card
                    title="Carrinho abandonado"
                    extra={
                        <Button onClick={() => navigate('/seller/checkout-links')}>
                            Links de checkout
                        </Button>
                    }
                >
                    <Typography.Paragraph type="secondary" style={{ marginBottom: 24 }}>
                        Acompanhe os clientes que interromperam a compra e o andamento da sequência de recuperação.
                    </Typography.Paragraph>

                    {loading ? (
                        <div style={{ display: 'flex', justifyContent: 'center', padding: '48px 0' }}>
                            <Spin />
                        </div>
                    ) : sessions.length === 0 ? (
                        <Empty description="Nenhum carrinho abandonado encontrado" />
                    ) : (
                        <Space direction="vertical" size={20} style={{ width: '100%' }}>
                            <Row gutter={[16, 16]}>
                                <Col xs={24} md={8}>
                                    <Card bordered={false} style={{ background: '#fafafa' }}>
                                        <Typography.Text type="secondary">Carrinhos abandonados</Typography.Text>
                                        <Typography.Title level={2} style={{ marginTop: 8, marginBottom: 0 }}>
                                            {sessions.length}
                                        </Typography.Title>
                                    </Card>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Card bordered={false} style={{ background: '#fafafa' }}>
                                        <Typography.Text type="secondary">Com e-mail</Typography.Text>
                                        <Typography.Title level={2} style={{ marginTop: 8, marginBottom: 0 }}>
                                            {sessions.filter((session) => Boolean(session.customer_email)).length}
                                        </Typography.Title>
                                    </Card>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Card bordered={false} style={{ background: '#fafafa' }}>
                                        <Typography.Text type="secondary">Recuperações enviadas</Typography.Text>
                                        <Typography.Title level={2} style={{ marginTop: 8, marginBottom: 0 }}>
                                            {sessions.filter((session) => session.sent_recoveries_count > 0).length}
                                        </Typography.Title>
                                    </Card>
                                </Col>
                            </Row>

                            <Table
                                rowKey="id"
                                dataSource={sessions}
                                pagination={false}
                                columns={[
                                    {
                                        title: 'Cliente',
                                        render: (_, record) => (
                                            <Space direction="vertical" size={0}>
                                                <Typography.Text strong>{record.customer_name || 'Cliente sem nome'}</Typography.Text>
                                                <Typography.Text type="secondary">{record.customer_email || 'E-mail não informado'}</Typography.Text>
                                                <Typography.Text type="secondary">{record.customer_phone || 'Telefone não informado'}</Typography.Text>
                                            </Space>
                                        ),
                                    },
                                    {
                                        title: 'Compra',
                                        render: (_, record) => (
                                            <Space direction="vertical" size={0}>
                                                <Typography.Text>{record.product_name || 'Produto não identificado'}</Typography.Text>
                                                <Typography.Text type="secondary">{record.link_name || 'Link não identificado'}</Typography.Text>
                                            </Space>
                                        ),
                                    },
                                    {
                                        title: 'Total',
                                        dataIndex: 'total',
                                        render: (value) => formatCurrency(value),
                                    },
                                    {
                                        title: 'Abandonado em',
                                        dataIndex: 'abandoned_at',
                                        render: (value) => formatDateTime(value),
                                    },
                                    {
                                        title: 'Recuperação',
                                        render: (_, record) => (
                                            <Space direction="vertical" size={4}>
                                                <Tag color={recoveryStatusColors[record.recovery_status] ?? 'default'}>
                                                    {recoveryStatusLabels[record.recovery_status] ?? record.recovery_status}
                                                </Tag>
                                                <Typography.Text type="secondary">
                                                    {record.sent_recoveries_count}/{record.recoveries_count} enviadas
                                                </Typography.Text>
                                            </Space>
                                        ),
                                    },
                                ]}
                            />
                        </Space>
                    )}
                </Card>
            </Col>
        </Row>
    );
}

import { CopyOutlined, DeleteOutlined, EditOutlined, EyeOutlined, PlusOutlined, PauseCircleOutlined, PlayCircleOutlined } from '@ant-design/icons';
import { Button, Card, Col, Empty, Row, Space, Table, Tag, Typography, message } from 'antd';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

export default function CheckoutLinksPage() {
    const navigate = useNavigate();
    const [links, setLinks] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const controller = new AbortController();

        async function loadLinks() {
            setLoading(true);

            try {
                const response = await fetch('/seller/checkout-links', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar os links.');
                }

                const data = await response.json();
                setLinks(data.checkout_links ?? []);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    message.error(error.message || 'Falha ao carregar os links.');
                    setLinks([]);
                }
            } finally {
                setLoading(false);
            }
        }

        loadLinks();

        return () => controller.abort();
    }, []);

    async function copyLink(publicToken) {
        const url = `${window.location.origin}/checkout/${publicToken}`;
        await navigator.clipboard.writeText(url);
        message.success('Link copiado.');
    }

    async function updateStatus(linkId, endpoint, successMessage) {
        const response = await fetch(`/seller/checkout-links/${linkId}/${endpoint}`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            message.error('Não foi possível atualizar o status.');
            return;
        }

        message.success(successMessage);
        setLinks((current) =>
            current.map((link) => (link.id === linkId ? { ...link, status: endpoint === 'activate' ? 'active' : 'inactive' } : link)),
        );
    }

    async function deleteLink(linkId) {
        const confirmed = window.confirm('Excluir este link?');

        if (!confirmed) {
            return;
        }

        const response = await fetch(`/seller/checkout-links/${linkId}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            message.error('Não foi possível excluir o link.');
            return;
        }

        setLinks((current) => current.filter((link) => link.id !== linkId));
        message.success('Link excluído.');
    }

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card
                    title="Links de Checkout"
                    extra={
                        <Button icon={<PlusOutlined />} type="primary" onClick={() => navigate('/seller/checkout-links/novo')}>
                            Novo link
                        </Button>
                    }
                >
                    <Typography.Paragraph type="secondary" style={{ marginBottom: 24 }}>
                        Gerencie os links públicos que serão usados no site do vendedor.
                    </Typography.Paragraph>

                    {links.length === 0 && !loading ? (
                        <Empty description="Nenhum link cadastrado" />
                    ) : (
                        <Table
                            rowKey="id"
                            loading={loading}
                            dataSource={links}
                            pagination={false}
                            columns={[
                                {
                                    title: 'Link',
                                    dataIndex: 'name',
                                    render: (value, record) => (
                                        <Space direction="vertical" size={0}>
                                            <Typography.Text strong>{value}</Typography.Text>
                                            <Typography.Text type="secondary">{record.product?.name}</Typography.Text>
                                            <Typography.Text type="secondary">{`${window.location.origin}/checkout/${record.public_token}`}</Typography.Text>
                                        </Space>
                                    ),
                                },
                                {
                                    title: 'Status',
                                    dataIndex: 'status',
                                    render: (value) => <Tag color={value === 'active' ? 'green' : 'red'}>{value}</Tag>,
                                },
                                {
                                    title: 'Preço',
                                    dataIndex: 'total_price',
                                    render: (value) => `R$ ${Number(value).toFixed(2).replace('.', ',')}`,
                                },
                                {
                                    title: 'Vendas',
                                    render: (_, record) => record.orders?.filter((order) => order.status === 'paid')?.length ?? 0,
                                },
                                {
                                    title: 'Ações',
                                    render: (_, record) => (
                                        <Space wrap>
                                            <Button icon={<CopyOutlined />} onClick={() => copyLink(record.public_token)}>
                                                Copiar
                                            </Button>
                                            <Button icon={<EditOutlined />} onClick={() => navigate(`/seller/checkout-links/${record.id}/editar`)}>
                                                Editar
                                            </Button>
                                            <Button icon={<EyeOutlined />} onClick={() => navigate(`/seller/checkout-links/${record.id}/vendas`)}>
                                                Vendas
                                            </Button>
                                            {record.status === 'active' ? (
                                                <Button icon={<PauseCircleOutlined />} onClick={() => updateStatus(record.id, 'deactivate', 'Link desativado.')}>
                                                    Desativar
                                                </Button>
                                            ) : (
                                                <Button icon={<PlayCircleOutlined />} onClick={() => updateStatus(record.id, 'activate', 'Link ativado.')}>
                                                    Ativar
                                                </Button>
                                            )}
                                            <Button danger icon={<DeleteOutlined />} onClick={() => deleteLink(record.id)}>
                                                Excluir
                                            </Button>
                                        </Space>
                                    ),
                                },
                            ]}
                        />
                    )}
                </Card>
            </Col>
        </Row>
    );
}

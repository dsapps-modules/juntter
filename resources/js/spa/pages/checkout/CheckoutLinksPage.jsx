import {
    CopyOutlined,
    DeleteOutlined,
    EditOutlined,
    EyeOutlined,
    PlusOutlined,
} from '@ant-design/icons';
import { Button, Card, Col, Empty, Row, Space, Spin, Table, Tag, Typography, message } from 'antd';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

export default function CheckoutLinksPage() {
    const navigate = useNavigate();
    const [links, setLinks] = useState([]);
    const [loading, setLoading] = useState(true);
    const [statusLoadingLinkId, setStatusLoadingLinkId] = useState(null);

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
        setStatusLoadingLinkId(linkId);

        try {
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
        } finally {
            setStatusLoadingLinkId(null);
        }
    }

    function toggleLinkStatus(linkId, currentStatus) {
        if (currentStatus === 'active') {
            updateStatus(linkId, 'deactivate', 'Link desativado.');
            return;
        }

        updateStatus(linkId, 'activate', 'Link ativado.');
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
                                    render: (value, record) => (
                                        <div style={{ alignItems: 'center', display: 'flex', gap: 8, whiteSpace: 'nowrap' }}>
                                            <Tag
                                                aria-busy={statusLoadingLinkId === record.id ? 'true' : undefined}
                                                aria-label={value === 'active' ? 'Desativar link' : 'Ativar link'}
                                                color={value === 'active' ? 'green' : 'red'}
                                                onClick={() => {
                                                    if (statusLoadingLinkId === record.id) {
                                                        return;
                                                    }

                                                    toggleLinkStatus(record.id, value);
                                                }}
                                                onKeyDown={(event) => {
                                                    if (statusLoadingLinkId === record.id) {
                                                        return;
                                                    }

                                                    if (event.key === 'Enter' || event.key === ' ') {
                                                        event.preventDefault();
                                                        toggleLinkStatus(record.id, value);
                                                    }
                                                }}
                                                role="button"
                                                tabIndex={0}
                                                style={{ cursor: statusLoadingLinkId === record.id ? 'wait' : 'pointer', display: 'inline-flex' }}
                                            >
                                                {value}
                                            </Tag>
                                            <span style={{ display: 'inline-flex', justifyContent: 'center', marginLeft: 20, width: 16, flexShrink: 0 }}>
                                                {statusLoadingLinkId === record.id ? <Spin size="small" /> : null}
                                            </span>
                                        </div>
                                    ),
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
                                    render: (_, record) => (
                                        <Space wrap>
                                            <Button
                                                aria-label="Copiar link"
                                                icon={<CopyOutlined />}
                                                onClick={() => copyLink(record.public_token)}
                                                title="Copiar link"
                                            />
                                            <Button
                                                aria-label="Editar link"
                                                icon={<EditOutlined />}
                                                onClick={() => navigate(`/seller/checkout-links/${record.id}/editar`)}
                                                title="Editar link"
                                            />
                                            <Button
                                                aria-label="Ver vendas"
                                                icon={<EyeOutlined />}
                                                onClick={() => navigate(`/seller/checkout-links/${record.id}/vendas`)}
                                                title="Ver vendas"
                                            />
                                            <Button
                                                aria-label="Excluir link"
                                                danger
                                                icon={<DeleteOutlined />}
                                                onClick={() => deleteLink(record.id)}
                                                title="Excluir link"
                                            />
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

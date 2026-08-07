import {
    CopyOutlined,
    DeleteOutlined,
    EditOutlined,
    EyeOutlined,
    PlusOutlined,
} from '@ant-design/icons';
import { Button, Card, Col, Empty, Row, Space, Table, Typography, message } from 'antd';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

function getCheckoutLinkThumbnailUrl(record) {
    return record.product_image_url || record.product?.image_url || '';
}

function resolveAvailabilityStatus(record) {
    if (record.status !== 'active') {
        return record.status;
    }

    if (record.expires_at && new Date(record.expires_at).getTime() < Date.now()) {
        return 'expired';
    }

    if (record.product?.status !== 'active') {
        return 'product_inactive';
    }

    if (record.seller?.nivel_acesso !== 'vendedor') {
        return 'seller_inactive';
    }

    return record.availability_status ?? 'active';
}

function resolveAvailabilityIndicatorLabel(record) {
    return resolveAvailabilityStatus(record) === 'active' ? 'Link ativo' : 'Link inativo';
}

function resolveAvailabilityIndicatorStyle(record) {
    return {
        backgroundColor: resolveAvailabilityStatus(record) === 'active' ? '#22c55e' : '#ef4444',
        borderRadius: '9999px',
        boxShadow: '0 0 0 2px rgba(255, 255, 255, 0.95)',
        display: 'inline-block',
        flexShrink: 0,
        height: 10,
        width: 10,
    };
}

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

    async function copyLink(link) {
        const url = link.public_spa_url || `${window.location.origin}/checkout/spa/${link.public_token}`;
        await navigator.clipboard.writeText(url);
        message.success('Link copiado.');
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
                                        <Space align="start" size={12}>
                                            {getCheckoutLinkThumbnailUrl(record) ? (
                                                <div
                                                    aria-hidden="true"
                                                    className="spa-link-product-thumb"
                                                    style={{
                                                        backgroundImage: `url(${getCheckoutLinkThumbnailUrl(record)})`,
                                                    }}
                                                />
                                            ) : null}
                                            <Space direction="vertical" size={0}>
                                                <Space align="center" size={8}>
                                                    <span
                                                        aria-label={resolveAvailabilityIndicatorLabel(record)}
                                                        style={resolveAvailabilityIndicatorStyle(record)}
                                                        title={resolveAvailabilityIndicatorLabel(record)}
                                                    />
                                                    <Typography.Text strong>{value}</Typography.Text>
                                                </Space>
                                                <Typography.Text type="secondary">
                                                    {record.public_spa_url || `${window.location.origin}/checkout/spa/${record.public_token}`}
                                                </Typography.Text>
                                            </Space>
                                        </Space>
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
                                                onClick={() => copyLink(record)}
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

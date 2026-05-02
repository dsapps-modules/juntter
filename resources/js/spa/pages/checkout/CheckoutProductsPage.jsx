import { DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import { Button, Card, Col, Empty, Row, Space, Table, Tag, Typography, message } from 'antd';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

export default function CheckoutProductsPage() {
    const navigate = useNavigate();
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const controller = new AbortController();

        async function loadProducts() {
            setLoading(true);

            try {
                const response = await fetch('/seller/products', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar os produtos.');
                }

                const data = await response.json();
                setProducts(data.products ?? []);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    message.error(error.message || 'Falha ao carregar os produtos.');
                    setProducts([]);
                }
            } finally {
                setLoading(false);
            }
        }

        loadProducts();

        return () => controller.abort();
    }, []);

    async function deleteProduct(productId) {
        const confirmed = window.confirm('Excluir este produto?');

        if (!confirmed) {
            return;
        }

        const response = await fetch(`/seller/products/${productId}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            message.error('Não foi possível excluir o produto.');
            return;
        }

        setProducts((current) => current.filter((product) => product.id !== productId));
        message.success('Produto excluído.');
    }

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card
                    title="Produtos do Checkout"
                    extra={
                        <Button icon={<PlusOutlined />} type="primary" onClick={() => navigate('/seller/products/novo')}>
                            Novo produto
                        </Button>
                    }
                >
                    <Typography.Paragraph type="secondary" style={{ marginBottom: 24 }}>
                        Cadastre os produtos que serão vinculados aos links de checkout.
                    </Typography.Paragraph>

                    {products.length === 0 && !loading ? (
                        <Empty description="Nenhum produto cadastrado" />
                    ) : (
                        <Table
                            rowKey="id"
                            loading={loading}
                            dataSource={products}
                            pagination={false}
                            columns={[
                                {
                                    title: 'Produto',
                                    dataIndex: 'name',
                                    render: (value, record) => (
                                        <Space direction="vertical" size={0}>
                                            <Typography.Text strong>{value}</Typography.Text>
                                            <Typography.Text type="secondary">{record.short_description}</Typography.Text>
                                        </Space>
                                    ),
                                },
                                {
                                    title: 'Preço',
                                    dataIndex: 'price',
                                    render: (value) => `R$ ${Number(value).toFixed(2).replace('.', ',')}`,
                                },
                                {
                                    title: 'Status',
                                    dataIndex: 'status',
                                    render: (value) => <Tag color={value === 'active' ? 'green' : 'red'}>{value}</Tag>,
                                },
                                {
                                    title: 'Ações',
                                    render: (_, record) => (
                                        <Space>
                                            <Button icon={<EditOutlined />} onClick={() => navigate(`/seller/products/${record.id}/editar`)}>
                                                Editar
                                            </Button>
                                            <Button danger icon={<DeleteOutlined />} onClick={() => deleteProduct(record.id)}>
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

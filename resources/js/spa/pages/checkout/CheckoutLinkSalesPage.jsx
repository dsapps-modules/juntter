import { Card, Col, Empty, Row, Space, Statistic, Table, Tag, Typography, message } from 'antd';
import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';

export default function CheckoutLinkSalesPage() {
    const params = useParams();
    const [sales, setSales] = useState([]);
    const [summary, setSummary] = useState({ total_sales: 0 });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const controller = new AbortController();

        async function loadSales() {
            setLoading(true);

            try {
                const response = await fetch(`/seller/checkout-links/${params.checkoutLinkId}/sales`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar as vendas.');
                }

                const data = await response.json();
                setSales(data.orders ?? []);
                setSummary({
                    total_sales: data.total_sales ?? 0,
                });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    message.error(error.message || 'Falha ao carregar as vendas.');
                    setSales([]);
                }
            } finally {
                setLoading(false);
            }
        }

        loadSales();

        return () => controller.abort();
    }, [params.checkoutLinkId]);

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card title="Vendas do Checkout">
                    <Typography.Paragraph type="secondary" style={{ marginBottom: 24 }}>
                        Acompanhe os pedidos gerados a partir dos links de checkout.
                    </Typography.Paragraph>

                    <Space style={{ marginBottom: 24 }} size={20} wrap>
                        <Statistic title="Total vendido" value={`R$ ${Number(summary.total_sales).toFixed(2).replace('.', ',')}`} />
                        <Statistic title="Pedidos" value={sales.length} />
                    </Space>

                    {sales.length === 0 && !loading ? (
                        <Empty description="Nenhuma venda encontrada" />
                    ) : (
                        <Table
                            rowKey="id"
                            loading={loading}
                            dataSource={sales}
                            pagination={false}
                            columns={[
                                {
                                    title: 'Pedido',
                                    dataIndex: 'order_number',
                                },
                                {
                                    title: 'Cliente',
                                    dataIndex: 'customer_name',
                                },
                                {
                                    title: 'Status',
                                    dataIndex: 'status',
                                    render: (value) => <Tag color={value === 'paid' ? 'green' : 'gold'}>{value}</Tag>,
                                },
                                {
                                    title: 'Total',
                                    dataIndex: 'total',
                                    render: (value) => `R$ ${Number(value).toFixed(2).replace('.', ',')}`,
                                },
                            ]}
                        />
                    )}
                </Card>
            </Col>
        </Row>
    );
}

import { ArrowLeftOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, Descriptions, Row, Skeleton, Space, Tag, Typography, message } from 'antd';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

function formatCurrency(value) {
    return `R$ ${Number(value ?? 0).toFixed(2).replace('.', ',')}`;
}

function formatDateTime(value) {
    if (!value) {
        return 'N/A';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(date);
}

function formatDate(value) {
    if (!value) {
        return 'N/A';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
    }).format(date);
}

function statusTag(status) {
    return <Tag color={status === 'paid' ? 'green' : 'gold'}>{status || 'pending'}</Tag>;
}

export default function CheckoutLinkSaleDetailPage() {
    const navigate = useNavigate();
    const params = useParams();
    const [loading, setLoading] = useState(true);
    const [sale, setSale] = useState(null);
    const [checkoutSession, setCheckoutSession] = useState(null);
    const [paymentTransaction, setPaymentTransaction] = useState(null);
    const [accessLevel, setAccessLevel] = useState('');
    const isAdminUser = ['admin', 'super_admin'].includes(accessLevel);

    useEffect(() => {
        const controller = new AbortController();

        async function loadPageData() {
            setLoading(true);

            try {
                const [profileResponse, saleResponse] = await Promise.all([
                    fetch('/api/spa/perfil', {
                        signal: controller.signal,
                        headers: {
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    }),
                    fetch(`/seller/checkout-links/${params.checkoutLinkId}/sales/${params.orderId}`, {
                        signal: controller.signal,
                        headers: {
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    }),
                ]);

                if (!profileResponse.ok) {
                    throw new Error('Não foi possível carregar o perfil do usuário.');
                }

                if (!saleResponse.ok) {
                    throw new Error('Não foi possível carregar os detalhes da venda.');
                }

                const profileData = await profileResponse.json();
                const saleData = await saleResponse.json();

                setAccessLevel(profileData.profile?.nivel_acesso ?? '');
                setSale(saleData.order ?? null);
                setCheckoutSession(saleData.checkout_session ?? null);
                setPaymentTransaction(saleData.payment_transaction ?? null);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    message.error(error.message || 'Falha ao carregar os detalhes da venda.');
                    setSale(null);
                    setCheckoutSession(null);
                    setPaymentTransaction(null);
                    setAccessLevel('');
                }
            } finally {
                setLoading(false);
            }
        }

        loadPageData();

        return () => controller.abort();
    }, [params.checkoutLinkId, params.orderId]);

    if (loading) {
        return (
            <Row gutter={[20, 20]} className="spa-board">
                <Col span={24}>
                    <Card
                        title="Detalhes da venda"
                        extra={
                            <Button icon={<ArrowLeftOutlined />} onClick={() => navigate(`/seller/checkout-links/${params.checkoutLinkId}/vendas`)}>
                                Voltar
                            </Button>
                        }
                    >
                        <Skeleton active paragraph={{ rows: 12 }} />
                    </Card>
                </Col>
            </Row>
        );
    }

    if (!sale) {
        return (
            <Row gutter={[20, 20]} className="spa-board">
                <Col span={24}>
                    <Card
                        title="Detalhes da venda"
                        extra={
                            <Button icon={<ArrowLeftOutlined />} onClick={() => navigate(`/seller/checkout-links/${params.checkoutLinkId}/vendas`)}>
                                Voltar
                            </Button>
                        }
                    >
                        <Alert type="warning" message="Venda não encontrada." showIcon />
                    </Card>
                </Col>
            </Row>
        );
    }

    const paymentMethod = sale.payment_method === 'credit_card'
        ? 'Cartão de crédito'
        : sale.payment_method === 'boleto'
            ? 'Boleto'
            : 'Pix';

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card
                    title={
                        <Space direction="vertical" size={0}>
                            <Typography.Text strong>Detalhes da venda</Typography.Text>
                            <Typography.Text type="secondary">{sale.order_number}</Typography.Text>
                        </Space>
                    }
                    extra={
                        <Button icon={<ArrowLeftOutlined />} onClick={() => navigate(`/seller/checkout-links/${params.checkoutLinkId}/vendas`)}>
                            Voltar
                        </Button>
                    }
                >
                    <Space direction="vertical" size={20} style={{ width: '100%' }}>
                        <Row gutter={[20, 20]}>
                            <Col xs={24} lg={12}>
                                <Card title="Dados do pedido">
                                    <Descriptions column={1} size="small">
                                        <Descriptions.Item label="Número">{sale.order_number}</Descriptions.Item>
                                        <Descriptions.Item label="Status">{statusTag(sale.status)}</Descriptions.Item>
                                        <Descriptions.Item label="Método de pagamento">{paymentMethod}</Descriptions.Item>
                                        <Descriptions.Item label="Quantidade">{sale.quantity}</Descriptions.Item>
                                        <Descriptions.Item label="Preço unitário">{formatCurrency(sale.unit_price)}</Descriptions.Item>
                                        <Descriptions.Item label="Subtotal">{formatCurrency(sale.subtotal)}</Descriptions.Item>
                                        <Descriptions.Item label="Desconto">{formatCurrency(sale.discount_total)}</Descriptions.Item>
                                        <Descriptions.Item label="Frete">{formatCurrency(sale.shipping_total)}</Descriptions.Item>
                                        <Descriptions.Item label="Total">{formatCurrency(sale.total)}</Descriptions.Item>
                                        <Descriptions.Item label="Criado em">{formatDateTime(sale.created_at)}</Descriptions.Item>
                                    </Descriptions>
                                </Card>
                            </Col>

                            <Col xs={24} lg={12}>
                                <Card title="Dados do cliente">
                                    <Descriptions column={1} size="small">
                                        <Descriptions.Item label="Nome">{sale.customer_name || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="E-mail">{sale.customer_email || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Documento">{sale.customer_document || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Telefone">{sale.customer_phone || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Produto">{sale.product?.name || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Link">{sale.checkout_link?.name || 'N/A'}</Descriptions.Item>
                                    </Descriptions>
                                </Card>
                            </Col>
                        </Row>

                        <Row gutter={[20, 20]}>
                            <Col xs={24} lg={12}>
                                <Card title="Endereço de entrega">
                                    <Descriptions column={1} size="small">
                                        <Descriptions.Item label="Recebedor">{checkoutSession?.recipient_name || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="CEP">{checkoutSession?.zipcode || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Rua">{checkoutSession?.street || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Número">{checkoutSession?.number || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Complemento">{checkoutSession?.complement || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Bairro">{checkoutSession?.neighborhood || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Cidade">{checkoutSession?.city || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Estado">{checkoutSession?.state || 'N/A'}</Descriptions.Item>
                                    </Descriptions>
                                </Card>
                            </Col>

                            <Col xs={24} lg={12}>
                                <Card title="Dados do pagamento">
                                    <Descriptions column={1} size="small">
                                        <Descriptions.Item label="Gateway">{paymentTransaction?.gateway || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Status interno">{statusTag(paymentTransaction?.internal_status)}</Descriptions.Item>
                                        <Descriptions.Item label="Status do gateway">{paymentTransaction?.gateway_status || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Transação">{paymentTransaction?.gateway_transaction_id || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Parcelas">{paymentTransaction?.installments || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Valor">{formatCurrency(paymentTransaction?.amount ?? sale.total)}</Descriptions.Item>
                                        <Descriptions.Item label="Últimos 4">{paymentTransaction?.card_last_four || 'N/A'}</Descriptions.Item>
                                        <Descriptions.Item label="Bandeira">{paymentTransaction?.card_brand || 'N/A'}</Descriptions.Item>
                                    </Descriptions>
                                </Card>
                            </Col>
                        </Row>

                        {isAdminUser ? (
                            <Row gutter={[20, 20]}>
                                <Col xs={24} lg={12}>
                                    <Card title="Sessão do checkout">
                                        <Descriptions column={1} size="small">
                                            <Descriptions.Item label="Token">{checkoutSession?.session_token || 'N/A'}</Descriptions.Item>
                                            <Descriptions.Item label="Etapa atual">{checkoutSession?.current_step || 'N/A'}</Descriptions.Item>
                                            <Descriptions.Item label="Status">{checkoutSession?.status || 'N/A'}</Descriptions.Item>
                                            <Descriptions.Item label="Última atividade">{formatDateTime(checkoutSession?.last_activity_at)}</Descriptions.Item>
                                            <Descriptions.Item label="Empresa">{checkoutSession?.customer_company_name || 'N/A'}</Descriptions.Item>
                                            <Descriptions.Item label="Responsável">{checkoutSession?.recipient_name || 'N/A'}</Descriptions.Item>
                                            <Descriptions.Item label="Nascimento">{formatDate(checkoutSession?.customer_birth_date)}</Descriptions.Item>
                                            <Descriptions.Item label="CPF/CNPJ responsável">{checkoutSession?.customer_responsible_document || 'N/A'}</Descriptions.Item>
                                        </Descriptions>
                                    </Card>
                                </Col>

                                <Col xs={24} lg={12}>
                                    <Card title="Payloads">
                                        <Typography.Paragraph type="secondary">
                                            Dados enviados e retornados pelo gateway.
                                        </Typography.Paragraph>
                                        <Typography.Text strong className="block">
                                            Request
                                        </Typography.Text>
                                        <pre style={{ whiteSpace: 'pre-wrap', wordBreak: 'break-word', marginTop: 8 }}>
                                            {JSON.stringify(paymentTransaction?.request_payload ?? {}, null, 2)}
                                        </pre>
                                        <Typography.Text strong className="block">
                                            Response
                                        </Typography.Text>
                                        <pre style={{ whiteSpace: 'pre-wrap', wordBreak: 'break-word', marginTop: 8 }}>
                                            {JSON.stringify(paymentTransaction?.response_payload ?? {}, null, 2)}
                                        </pre>
                                    </Card>
                                </Col>
                            </Row>
                        ) : null}
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

import { Button, Card, Col, Form, Input, InputNumber, Row, Select, Space, Spin, Switch, Typography, message } from 'antd';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

const statusOptions = [
    { value: 'active', label: 'Ativo' },
    { value: 'inactive', label: 'Inativo' },
    { value: 'archived', label: 'Arquivado' },
];

const discountTypeOptions = [
    { value: 'none', label: 'Nenhum' },
    { value: 'fixed', label: 'Fixo' },
    { value: 'percentage', label: 'Percentual' },
];

const visualDefaults = {
    store_name: '',
    primary_color: '#FFC800',
    offer_message: '',
    footer_text: '',
};

export default function CheckoutLinkFormPage() {
    const navigate = useNavigate();
    const params = useParams();
    const [form] = Form.useForm();
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(Boolean(params.checkoutLinkId && params.checkoutLinkId !== 'novo'));
    const [saving, setSaving] = useState(false);
    const isEditing = Boolean(params.checkoutLinkId && params.checkoutLinkId !== 'novo');

    useEffect(() => {
        const controller = new AbortController();

        async function loadInitialData() {
            try {
                const [productsResponse, linkResponse] = await Promise.all([
                    fetch('/seller/products', {
                        signal: controller.signal,
                        headers: {
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    }),
                    isEditing
                        ? fetch(`/seller/checkout-links/${params.checkoutLinkId}`, {
                            signal: controller.signal,
                            headers: {
                                Accept: 'application/json',
                            },
                            credentials: 'same-origin',
                        })
                        : Promise.resolve(null),
                ]);

                if (!productsResponse.ok) {
                    throw new Error('Não foi possível carregar os produtos.');
                }

                const productsData = await productsResponse.json();
                setProducts(productsData.products ?? []);

                if (linkResponse) {
                    if (!linkResponse.ok) {
                        throw new Error('Não foi possível carregar o link.');
                    }

                    const linkData = await linkResponse.json();
                    const checkoutLink = linkData.checkout_link;
                    form.setFieldsValue({
                        ...checkoutLink,
                        visual_config: checkoutLink.visual_config ? JSON.stringify({ ...visualDefaults, ...checkoutLink.visual_config }, null, 2) : JSON.stringify(visualDefaults, null, 2),
                    });
                } else {
                    form.setFieldsValue({
                        status: 'active',
                        quantity: 1,
                        allow_pix: true,
                        allow_boleto: true,
                        allow_credit_card: true,
                        pix_discount_type: 'none',
                        boleto_discount_type: 'none',
                        free_shipping: true,
                        unit_price: 0,
                        visual_config: JSON.stringify(visualDefaults, null, 2),
                    });
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    message.error(error.message || 'Falha ao carregar os dados.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadInitialData();

        return () => controller.abort();
    }, [form, isEditing, params.checkoutLinkId]);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            const payload = {
                ...values,
                visual_config: values.visual_config ? JSON.parse(values.visual_config) : null,
            };

            const response = await fetch(isEditing ? `/seller/checkout-links/${params.checkoutLinkId}` : '/seller/checkout-links', {
                method: isEditing ? 'PUT' : 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Não foi possível salvar o link.');
            }

            message.success(data.message || 'Link salvo com sucesso.');
            navigate('/seller/checkout-links');
        } catch (error) {
            message.error(error.message || 'Falha ao salvar o link.');
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete() {
        const confirmed = window.confirm('Excluir este link?');

        if (!confirmed) {
            return;
        }

        const response = await fetch(`/seller/checkout-links/${params.checkoutLinkId}`, {
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

        message.success('Link excluído.');
        navigate('/seller/checkout-links');
    }

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card title={isEditing ? 'Editar link de checkout' : 'Novo link de checkout'}>
                    <Typography.Paragraph type="secondary">
                        Configure o produto, preço congelado, regras de pagamento e a personalização visual.
                    </Typography.Paragraph>

                    {loading ? (
                        <Spin />
                    ) : (
                        <Form form={form} layout="vertical" onFinish={handleSubmit}>
                            <Form.Item label="Nome do link" name="name" rules={[{ required: true, message: 'Informe o nome.' }]}>
                                <Input />
                            </Form.Item>
                            <Form.Item label="Produto" name="product_id" rules={[{ required: true, message: 'Selecione um produto.' }]}>
                                <Select
                                    options={products.map((product) => ({
                                        value: product.id,
                                        label: product.name,
                                    }))}
                                    placeholder="Selecione um produto"
                                />
                            </Form.Item>
                            <Row gutter={16}>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Quantidade" name="quantity" rules={[{ required: true }]}>
                                        <InputNumber className="w-full" min={1} step={1} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Preço unitário" name="unit_price" rules={[{ required: true }]}>
                                        <InputNumber className="w-full" min={0.01} step={0.01} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Status" name="status" rules={[{ required: true }]}>
                                        <Select options={statusOptions} />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={16}>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Desconto Pix" name="pix_discount_type">
                                        <Select options={discountTypeOptions} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Valor do desconto Pix" name="pix_discount_value">
                                        <InputNumber className="w-full" min={0} step={0.01} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Desconto Boleto" name="boleto_discount_type">
                                        <Select options={discountTypeOptions} />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={16}>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Valor do desconto Boleto" name="boleto_discount_value">
                                        <InputNumber className="w-full" min={0} step={0.01} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Frete grátis" name="free_shipping" valuePropName="checked">
                                        <Switch />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Permitir Pix" name="allow_pix" valuePropName="checked">
                                        <Switch />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={16}>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Permitir Boleto" name="allow_boleto" valuePropName="checked">
                                        <Switch />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Permitir Cartão" name="allow_credit_card" valuePropName="checked">
                                        <Switch />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Expira em" name="expires_at">
                                        <Input type="datetime-local" />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Form.Item label="URL de sucesso" name="success_url">
                                <Input />
                            </Form.Item>
                            <Form.Item label="URL de falha" name="failure_url">
                                <Input />
                            </Form.Item>
                            <Form.Item label="Configuração visual JSON" name="visual_config">
                                <Input.TextArea rows={8} />
                            </Form.Item>

                            <Space>
                                <Button type="primary" htmlType="submit" loading={saving}>
                                    Salvar
                                </Button>
                                <Button onClick={() => navigate('/seller/checkout-links')}>Voltar</Button>
                                {isEditing ? (
                                    <Button danger onClick={handleDelete}>
                                        Excluir
                                    </Button>
                                ) : null}
                            </Space>
                        </Form>
                    )}
                </Card>
            </Col>
        </Row>
    );
}

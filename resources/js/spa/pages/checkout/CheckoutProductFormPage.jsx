import { Button, Card, Col, Form, Input, InputNumber, Row, Select, Space, Spin, Typography, message } from 'antd';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

const statusOptions = [
    { value: 'active', label: 'Ativo' },
    { value: 'inactive', label: 'Inativo' },
];

export default function CheckoutProductFormPage() {
    const navigate = useNavigate();
    const params = useParams();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(Boolean(params.productId && params.productId !== 'novo'));
    const [saving, setSaving] = useState(false);
    const isEditing = Boolean(params.productId && params.productId !== 'novo');

    useEffect(() => {
        if (!isEditing) {
            form.setFieldsValue({
                status: 'active',
                price: 0,
            });

            setLoading(false);
            return;
        }

        const controller = new AbortController();

        async function loadProduct() {
            try {
                const response = await fetch(`/seller/products/${params.productId}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar o produto.');
                }

                const data = await response.json();
                form.setFieldsValue({
                    ...data.product,
                });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    message.error(error.message || 'Falha ao carregar o produto.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadProduct();

        return () => controller.abort();
    }, [form, isEditing, params.productId]);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            const response = await fetch(isEditing ? `/seller/products/${params.productId}` : '/seller/products', {
                method: isEditing ? 'PUT' : 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(values),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Não foi possível salvar o produto.');
            }

            message.success(data.message || 'Produto salvo com sucesso.');
            navigate('/seller/products');
        } catch (error) {
            message.error(error.message || 'Falha ao salvar o produto.');
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete() {
        const confirmed = window.confirm('Excluir este produto?');

        if (!confirmed) {
            return;
        }

        const response = await fetch(`/seller/products/${params.productId}`, {
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

        message.success('Produto excluído.');
        navigate('/seller/products');
    }

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card title={isEditing ? 'Editar produto' : 'Novo produto'}>
                    <Typography.Paragraph type="secondary">
                        Configure o produto que será usado nos links de checkout.
                    </Typography.Paragraph>

                    {loading ? (
                        <Spin />
                    ) : (
                        <Form form={form} layout="vertical" onFinish={handleSubmit}>
                            <Form.Item label="Nome do produto" name="name" rules={[{ required: true, message: 'Informe o nome.' }]}>
                                <Input />
                            </Form.Item>
                            <Form.Item label="Slug" name="slug">
                                <Input />
                            </Form.Item>
                            <Form.Item label="Resumo curto" name="short_description">
                                <Input />
                            </Form.Item>
                            <Form.Item label="Descrição" name="description">
                                <Input.TextArea rows={4} />
                            </Form.Item>
                            <Form.Item label="SKU" name="sku">
                                <Input />
                            </Form.Item>
                            <Form.Item label="Imagem" name="image_path">
                                <Input />
                            </Form.Item>
                            <Form.Item label="Preço" name="price" rules={[{ required: true, message: 'Informe o preço.' }]}>
                                <InputNumber className="w-full" min={0.01} step={0.01} />
                            </Form.Item>
                            <Form.Item label="Status" name="status" initialValue="active" rules={[{ required: true }]}>
                                <Select options={statusOptions} />
                            </Form.Item>
                            <Space>
                                <Button type="primary" htmlType="submit" loading={saving}>
                                    Salvar
                                </Button>
                                <Button onClick={() => navigate('/seller/products')}>Voltar</Button>
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

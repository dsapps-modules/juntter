import { Button, Card, Col, Form, Input, InputNumber, Row, Select, Space, Spin, Typography, message } from 'antd';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

const statusOptions = [
    { value: 'active', label: 'Ativo' },
    { value: 'inactive', label: 'Inativo' },
];

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

function formatCurrencyInput(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const numericValue = typeof value === 'number'
        ? value
        : Number(String(value).replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.'));

    if (Number.isNaN(numericValue)) {
        return '';
    }

    return currencyFormatter.format(numericValue);
}

function parseCurrencyInput(value) {
    if (typeof value !== 'string') {
        return value;
    }

    const normalizedValue = value
        .replace(/\s?R\$\s?/g, '')
        .replace(/\./g, '')
        .replace(',', '.');

    if (normalizedValue === '') {
        return '';
    }

    const numericValue = Number(normalizedValue);

    return Number.isNaN(numericValue) ? '' : numericValue;
}

export default function CheckoutProductFormPage() {
    const navigate = useNavigate();
    const params = useParams();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(Boolean(params.productId && params.productId !== 'novo'));
    const [saving, setSaving] = useState(false);
    const [selectedImageFile, setSelectedImageFile] = useState(null);
    const [currentImagePath, setCurrentImagePath] = useState('');
    const [imagePreviewUrl, setImagePreviewUrl] = useState('');
    const isEditing = Boolean(params.productId && params.productId !== 'novo');

    useEffect(() => {
        if (!isEditing) {
            form.setFieldsValue({
                status: 'active',
                price: 0,
            });
            setSelectedImageFile(null);
            setCurrentImagePath('');
            setImagePreviewUrl('');

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
                    price: Number(data.product.price ?? 0),
                });
                setSelectedImageFile(null);
                setCurrentImagePath(data.product.image_path ?? '');
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

    useEffect(() => {
        if (selectedImageFile) {
            const previewUrl = URL.createObjectURL(selectedImageFile);
            setImagePreviewUrl(previewUrl);

            return () => URL.revokeObjectURL(previewUrl);
        }

        if (currentImagePath) {
            setImagePreviewUrl(`/storage/${currentImagePath}`);
            return;
        }

        setImagePreviewUrl('');
    }, [currentImagePath, selectedImageFile]);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            const payload = new FormData();

            Object.entries(values).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    payload.append(key, String(value));
                }
            });

            if (isEditing) {
                payload.append('_method', 'PUT');
            }

            if (selectedImageFile) {
                payload.append('image', selectedImageFile);
            } else if (currentImagePath) {
                payload.append('image_path', currentImagePath);
            }

            const response = await fetch(isEditing ? `/seller/products/${params.productId}` : '/seller/products', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                },
                credentials: 'same-origin',
                body: payload,
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
                            <Row gutter={24} align="stretch">
                                <Col xs={24} lg={17}>
                                    <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                        <Form.Item label="Nome do produto" name="name" rules={[{ required: true, message: 'Informe o nome.' }]}>
                                            <Input />
                                        </Form.Item>
                                        <Form.Item label="Resumo curto" name="short_description">
                                            <Input />
                                        </Form.Item>
                                        <Form.Item label="Descrição" name="description">
                                            <Input.TextArea rows={4} />
                                        </Form.Item>
                                    </Space>
                                </Col>

                                <Col xs={24} lg={7}>
                                    <div
                                        className="h-full rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4"
                                        style={{
                                            minHeight: '100%',
                                            display: 'flex',
                                            flexDirection: 'column',
                                        }}
                                    >
                                        <Typography.Text strong className="block">
                                            Imagem
                                        </Typography.Text>
                                        <Input
                                            type="file"
                                            accept=".png,.jpg,.jpeg,image/png,image/jpeg"
                                            className="mt-3"
                                            onChange={(event) => {
                                                setSelectedImageFile(event.target.files?.[0] ?? null);
                                            }}
                                        />
                                        <Typography.Text type="secondary" className="mt-2 block">
                                            Aceita arquivos JPG e PNG.
                                        </Typography.Text>

                                        <div
                                            className="mx-auto mt-4 flex items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-white"
                                            style={{
                                                aspectRatio: '1 / 1',
                                                width: '100%',
                                                maxWidth: '260px',
                                                flex: '0 0 auto',
                                                maxHeight: '260px',
                                            }}
                                        >
                                            {imagePreviewUrl ? (
                                                <img
                                                    src={imagePreviewUrl}
                                                    alt="Pré-visualização da imagem do produto"
                                                    className="h-full w-full object-contain p-4"
                                                />
                                            ) : (
                                                <div className="px-6 text-center">
                                                    <Typography.Title level={5} style={{ marginBottom: 8 }}>
                                                        Nenhuma imagem enviada
                                                    </Typography.Title>
                                                    <Typography.Text type="secondary">
                                                        O preview aparecerá aqui após selecionar um arquivo.
                                                    </Typography.Text>
                                                </div>
                                            )}
                                        </div>

                                        {currentImagePath && !selectedImageFile ? (
                                            <Typography.Paragraph type="secondary" style={{ marginBottom: 0, marginTop: 12 }}>
                                                Imagem atual: {currentImagePath}
                                            </Typography.Paragraph>
                                        ) : null}
                                    </div>
                                </Col>
                            </Row>
                            <Row gutter={16} className="product-form-inline-row">
                                <Col xs={24} md={8}>
                                    <Form.Item label="Preço" name="price" rules={[{ required: true, message: 'Informe o preço.' }]}>
                                        <InputNumber
                                            className="w-full"
                                            style={{ width: '100%' }}
                                            min={0.01}
                                            step={0.01}
                                            precision={2}
                                            formatter={formatCurrencyInput}
                                            parser={parseCurrencyInput}
                                            placeholder="R$ 0,00"
                                            aria-label="Preço do produto"
                                        />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="SKU" name="sku">
                                        <Input />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Status" name="status" initialValue="active" rules={[{ required: true }]}>
                                        <Select options={statusOptions} />
                                    </Form.Item>
                                </Col>
                            </Row>
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

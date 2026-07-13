import { Button, Card, Col, Form, Input, InputNumber, Row, Select, Space, Spin, Switch, Typography, message } from 'antd';
import { useEffect, useState } from 'react';
import { Radio } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import MoneyInputField, { formatCurrencyInput, parseCurrencyInput } from '../../components/form/MoneyInputField';

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
    theme: 'elegance',
    store_name: '',
    primary_color: '#1F2937',
    navbar_background_color: '#FFFFFF',
    navbar_text_color: '#1F2937',
    button_text_color: '#FFFFFF',
    offer_message: '',
    footer_text: '',
};

const checkoutThemes = [
    {
        value: 'elegance',
        name: 'Elegância',
        description: 'Editorial, leve e sofisticado. É o visual atual do checkout.',
        palette: ['#FFFFFF', '#F6F2EC', '#1F2937'],
        colors: {
            navbar_background_color: '#FFFFFF',
            navbar_text_color: '#1F2937',
            primary_color: '#1F2937',
            button_text_color: '#FFFFFF',
        },
    },
    {
        value: 'essential',
        name: 'Essencial',
        description: 'Limpo, direto e funcional, com foco total no preenchimento rápido.',
        palette: ['#F5F6F8', '#FFFFFF', '#000000'],
        colors: {
            navbar_background_color: '#FFFFFF',
            navbar_text_color: '#111111',
            primary_color: '#000000',
            button_text_color: '#FFFFFF',
        },
    },
    {
        value: 'noir',
        name: 'Noir Premium',
        description: 'Experiência marcante em tons escuros, com acabamento dourado.',
        palette: ['#11100E', '#24201B', '#D8B875'],
        colors: {
            navbar_background_color: '#171512',
            navbar_text_color: '#F8F2E8',
            primary_color: '#D8B875',
            button_text_color: '#17120D',
        },
    },
    {
        value: 'horizon',
        name: 'Horizonte',
        description: 'Azul e cinza em uma composição contemporânea, serena e profissional.',
        palette: ['#FFFFFF', '#EEF3F8', '#356B9A'],
        colors: {
            navbar_background_color: '#FFFFFF',
            navbar_text_color: '#243B53',
            primary_color: '#356B9A',
            button_text_color: '#FFFFFF',
        },
    },
    {
        value: 'iris',
        name: 'Íris',
        description: 'Roxo profundo e amarelo suave para uma experiência refinada e acolhedora.',
        palette: ['#FFFFFF', '#FFF8D8', '#37245F'],
        colors: {
            navbar_background_color: '#FFFFFF',
            navbar_text_color: '#37245F',
            primary_color: '#4A3278',
            button_text_color: '#FFFFFF',
        },
    },
    {
        value: 'atlantic',
        name: 'Atlântico',
        description: 'Verde-água e azul escuro em um visual fresco, seguro e elegante.',
        palette: ['#FFFFFF', '#E5F8F5', '#123B59'],
        colors: {
            navbar_background_color: '#FFFFFF',
            navbar_text_color: '#123B59',
            primary_color: '#147D82',
            button_text_color: '#FFFFFF',
        },
    },
];

export default function CheckoutLinkFormPage() {
    const navigate = useNavigate();
    const params = useParams();
    const [form] = Form.useForm();
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(Boolean(params.checkoutLinkId && params.checkoutLinkId !== 'novo'));
    const [saving, setSaving] = useState(false);
    const [productImagePreviewUrl, setProductImagePreviewUrl] = useState('');
    const [productImageFile, setProductImageFile] = useState(null);
    const isEditing = Boolean(params.checkoutLinkId && params.checkoutLinkId !== 'novo');
    const selectedProductId = Form.useWatch('product_id', form);
    const selectedProduct = products.find((product) => product.id === selectedProductId);

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
                    const visualConfig = checkoutLink.visual_config ?? {};

                    setProductImagePreviewUrl(checkoutLink.product_image_url ?? '');

                    form.setFieldsValue({
                        ...checkoutLink,
                        request_address: checkoutLink.request_address ?? true,
                        unit_price: formatCurrencyInput(checkoutLink.unit_price ?? 0),
                        theme: visualConfig.theme ?? visualDefaults.theme,
                        store_name: visualConfig.store_name ?? checkoutLink.seller?.name ?? visualDefaults.store_name,
                        primary_color: visualConfig.primary_color ?? visualDefaults.primary_color,
                        navbar_background_color: visualConfig.navbar_background_color ?? visualDefaults.navbar_background_color,
                        navbar_text_color: visualConfig.navbar_text_color ?? visualDefaults.navbar_text_color,
                        button_text_color: visualConfig.button_text_color ?? visualConfig.navbar_text_color ?? visualDefaults.button_text_color,
                        offer_message: visualConfig.offer_message ?? visualDefaults.offer_message,
                        footer_text: visualConfig.footer_text ?? visualDefaults.footer_text,
                    });
                } else {
                    form.setFieldsValue({
                        status: 'active',
                        quantity: 1,
                        allow_pix: true,
                        allow_boleto: true,
                        allow_credit_card: true,
                        request_address: true,
                        pix_discount_type: 'none',
                        boleto_discount_type: 'none',
                        free_shipping: true,
                        unit_price: formatCurrencyInput(0),
                        theme: visualDefaults.theme,
                        store_name: visualDefaults.store_name,
                        primary_color: visualDefaults.primary_color,
                        navbar_background_color: visualDefaults.navbar_background_color,
                        navbar_text_color: visualDefaults.navbar_text_color,
                        button_text_color: visualDefaults.button_text_color,
                        offer_message: visualDefaults.offer_message,
                        footer_text: visualDefaults.footer_text,
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

    useEffect(() => {
        return () => {
            if (productImagePreviewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(productImagePreviewUrl);
            }
        };
    }, [productImagePreviewUrl]);

    function handleProductImageChange(event) {
        const file = event.target.files?.[0] ?? null;

        if (productImagePreviewUrl.startsWith('blob:')) {
            URL.revokeObjectURL(productImagePreviewUrl);
        }

        setProductImageFile(file);
        setProductImagePreviewUrl(file ? URL.createObjectURL(file) : '');
    }

    async function handleSubmit(values) {
        setSaving(true);

        try {
            const {
                theme,
                store_name,
                primary_color,
                navbar_background_color,
                navbar_text_color,
                button_text_color,
                offer_message,
                footer_text,
                ...restValues
            } = values;
            const payload = new FormData();

            Object.entries({
                ...restValues,
                unit_price: parseCurrencyInput(restValues.unit_price),
                visual_config: JSON.stringify({
                    theme: theme || visualDefaults.theme,
                    store_name,
                    primary_color: primary_color || visualDefaults.primary_color,
                    navbar_background_color: navbar_background_color || visualDefaults.navbar_background_color,
                    navbar_text_color: navbar_text_color || visualDefaults.navbar_text_color,
                    button_text_color: button_text_color || navbar_text_color || visualDefaults.button_text_color,
                    offer_message,
                    footer_text,
                }),
            }).forEach(([key, value]) => {
                if (value === null || value === undefined) {
                    return;
                }

                if (typeof value === 'boolean') {
                    payload.append(key, value ? '1' : '0');
                    return;
                }

                payload.append(key, String(value));
            });

            if (productImageFile) {
                payload.append('product_image', productImageFile);
            }

            if (isEditing) {
                payload.append('_method', 'PUT');
            }

            const response = await fetch(isEditing ? `/seller/checkout-links/${params.checkoutLinkId}` : '/seller/checkout-links', {
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

    function handleNavbarBackgroundColorChange(event) {
        const nextColor = event.target.value;

        form.setFieldsValue({
            primary_color: nextColor,
        });
    }

    function handleNavbarTextColorChange(event) {
        const nextColor = event.target.value;

        form.setFieldsValue({
            button_text_color: nextColor,
        });
    }

    function handleThemeChange(event) {
        const selectedTheme = checkoutThemes.find((theme) => theme.value === event.target.value);

        if (!selectedTheme) {
            return;
        }

        form.setFieldsValue(selectedTheme.colors);
    }

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card
                    title={isEditing ? 'Editar link de checkout' : 'Criar'}
                    extra={
                        isEditing ? (
                            <Button onClick={() => navigate('/seller/checkout-links')}>
                                Voltar
                            </Button>
                        ) : null
                    }
                >
                    {loading ? (
                        <Spin />
                    ) : (
                        <Form form={form} layout="vertical" onFinish={handleSubmit}>
                            <Form.Item label="Nome do link" name="name" rules={[{ required: true, message: 'Informe o nome.' }]}>
                                <Input />
                            </Form.Item>
                            <Form.Item
                                label="Estilo do checkout"
                                name="theme"
                                extra="Escolha a experiência visual que seus clientes encontrarão ao pagar. Você pode ajustar as cores depois."
                                rules={[{ required: true, message: 'Selecione um estilo para o checkout.' }]}
                            >
                                <Radio.Group className="checkout-theme-selector" onChange={handleThemeChange}>
                                    {checkoutThemes.map((theme) => (
                                        <Radio.Button className="checkout-theme-option" key={theme.value} value={theme.value}>
                                            <span className="checkout-theme-option__preview" aria-hidden="true">
                                                <span className="checkout-theme-option__top" style={{ background: theme.palette[0] }} />
                                                <span className="checkout-theme-option__content" style={{ background: theme.palette[1] }}>
                                                    <span style={{ background: theme.palette[2] }} />
                                                    <span style={{ background: theme.palette[2] }} />
                                                    <span style={{ background: theme.palette[2] }} />
                                                </span>
                                                <span className="checkout-theme-option__aside" style={{ background: theme.palette[2] }} />
                                            </span>
                                            <span className="checkout-theme-option__copy">
                                                <strong>{theme.name}</strong>
                                                <span>{theme.description}</span>
                                            </span>
                                        </Radio.Button>
                                    ))}
                                </Radio.Group>
                            </Form.Item>

                            <Row gutter={16}>
                                <Col xs={24} md={16}>
                                    <Form.Item label="Produto" name="product_id" rules={[{ required: true, message: 'Selecione um produto.' }]}>
                                        <Select
                                            options={products.map((product) => ({
                                                value: product.id,
                                                label: product.name,
                                            }))}
                                            placeholder="Selecione um produto"
                                        />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Preço Padrão">
                                        <Input readOnly value={formatCurrencyInput(selectedProduct?.price ?? 0)} />
                                    </Form.Item>
                                </Col>
                            </Row>
                            <Row gutter={16}>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Quantidade" name="quantity" rules={[{ required: true }]}>
                                        <InputNumber className="w-full" min={1} step={1} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Preço unitário" name="unit_price" rules={[{ required: true }]}>
                                        <MoneyInputField className="w-full" />
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
                                    <Form.Item label="Permitir Pix" name="allow_pix" valuePropName="checked">
                                        <Switch />
                                    </Form.Item>
                                </Col>
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
                            </Row>

                            <Row gutter={16}>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Permitir Boleto" name="allow_boleto" valuePropName="checked">
                                        <Switch />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Desconto Boleto" name="boleto_discount_type">
                                        <Select options={discountTypeOptions} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item label="Valor do desconto Boleto" name="boleto_discount_value">
                                        <InputNumber className="w-full" min={0} step={0.01} />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={16}>
                                <Col xs={24} md={5}>
                                    <Form.Item label="Permitir Cartão" name="allow_credit_card" valuePropName="checked">
                                        <Switch />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={5}>
                                    <Form.Item label="Solicitar endereço do cliente" name="request_address" valuePropName="checked">
                                        <Switch />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={6}>
                                    <Form.Item label="Frete grátis" name="free_shipping" valuePropName="checked">
                                        <Switch />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={6}>
                                    <Form.Item label="Expira em" name="expires_at">
                                        <Input type="datetime-local" />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={16}>
                                <Col xs={24} md={6}>
                                    <Form.Item label="Cor do topo" name="navbar_background_color">
                                        <Input type="color" style={{ width: 120, padding: 4 }} onChange={handleNavbarBackgroundColorChange} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={6}>
                                    <Form.Item label="Cor do botão" name="primary_color">
                                        <Input type="color" style={{ width: 120, padding: 4 }} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={6}>
                                    <Form.Item label="Letra do topo" name="navbar_text_color">
                                        <Input type="color" style={{ width: 120, padding: 4 }} onChange={handleNavbarTextColorChange} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={6}>
                                    <Form.Item label="Letra do botão" name="button_text_color">
                                        <Input type="color" style={{ width: 120, padding: 4 }} />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={16}>
                                <Col xs={24} md={8}>
                                    <Form.Item
                                        label="Imagem do produto"
                                        extra="Envie uma imagem quadrada de 250x250 px, preferencialmente."
                                    >
                                        <input
                                            accept="image/*"
                                            type="file"
                                            onChange={handleProductImageChange}
                                        />
                                        {productImagePreviewUrl ? (
                                            <div style={{ marginTop: 12 }}>
                                                <img
                                                    alt="Pré-visualização da imagem do produto"
                                                    src={productImagePreviewUrl}
                                                    style={{ borderRadius: 12, display: 'block', height: 96, objectFit: 'cover', width: 96 }}
                                                />
                                            </div>
                                        ) : null}
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Form.Item hidden label="Nome da loja" name="store_name" extra="Como o checkout vai chamar sua loja no topo da página.">
                                <Input placeholder="Ex.: Juntter Shop" />
                            </Form.Item>
                            <Form.Item hidden label="Mensagem da oferta" name="offer_message" extra="Texto curto para destacar a oferta no checkout.">
                                <Input.TextArea rows={3} placeholder="Ex.: Oferta especial disponível por tempo limitado." />
                            </Form.Item>
                            <Form.Item hidden label="Texto do rodapé" name="footer_text" extra="Mensagem opcional exibida no rodapé do checkout.">
                                <Input.TextArea rows={3} placeholder="Ex.: Atendimento de segunda a sexta, das 9h às 18h." />
                            </Form.Item>
                            <Form.Item label="URL de sucesso" name="success_url">
                                <Input />
                            </Form.Item>
                            <Form.Item label="URL de falha" name="failure_url">
                                <Input />
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

import { DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import { Button, Card, Col, Form, Input, InputNumber, Popconfirm, Row, Space, Switch, Tag, Typography, message } from 'antd';
import { useEffect, useState } from 'react';
import MoneyInputField, { formatCurrencyInput, parseCurrencyInput } from '../../components/form/MoneyInputField';

function formatCurrency(value) {
    return `R$ ${Number(value ?? 0).toFixed(2).replace('.', ',')}`;
}

function buildDefaultShippingValues(hasOptions) {
    return {
        name: 'Frete padrão',
        price: formatCurrencyInput(0),
        eta_days: 5,
        is_default: !hasOptions,
        is_active: true,
    };
}

export default function CheckoutShippingPage() {
    const [form] = Form.useForm();
    const [shippingOptions, setShippingOptions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [editingOptionId, setEditingOptionId] = useState(null);
    const hasRealOptions = shippingOptions.some((option) => option.id !== null);
    const selectedOption = shippingOptions.find((option) => option.id === editingOptionId) || null;

    useEffect(() => {
        const controller = new AbortController();

        async function loadShippingOptions() {
            setLoading(true);

            try {
                const response = await fetch('/seller/checkout-links/frete', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar os fretes.');
                }

                const data = await response.json();
                const nextShippingOptions = (data.shipping_options ?? []).filter((option) => option.id !== null);

                setShippingOptions(nextShippingOptions);

                if (nextShippingOptions.length > 0) {
                    const defaultOption = nextShippingOptions.find((option) => option.is_default) ?? nextShippingOptions[0];
                    setEditingOptionId(defaultOption?.id ?? null);
                    form.setFieldsValue({
                        name: defaultOption?.name ?? 'Frete padrão',
                        price: formatCurrencyInput(defaultOption?.price ?? 0),
                        eta_days: defaultOption?.eta_days ?? 5,
                        is_default: defaultOption?.is_default ?? false,
                        is_active: defaultOption?.is_active ?? true,
                    });
                } else {
                    setEditingOptionId(null);
                    form.setFieldsValue(buildDefaultShippingValues(false));
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    message.error(error.message || 'Falha ao carregar os fretes.');
                    setShippingOptions([]);
                    form.setFieldsValue(buildDefaultShippingValues(false));
                }
            } finally {
                setLoading(false);
            }
        }

        loadShippingOptions();

        return () => controller.abort();
    }, [form]);

    useEffect(() => {
        if (!selectedOption) {
            return;
        }

        form.setFieldsValue({
            name: selectedOption.name,
            price: formatCurrencyInput(selectedOption.price ?? 0),
            eta_days: selectedOption.eta_days ?? 5,
            is_default: Boolean(selectedOption.is_default),
            is_active: Boolean(selectedOption.is_active),
        });
    }, [form, selectedOption]);

    function resetToNewShipping() {
        setEditingOptionId(null);
        form.setFieldsValue(buildDefaultShippingValues(hasRealOptions));
    }

    async function handleSubmit(values) {
        setSaving(true);

        try {
            const payload = new FormData();

            payload.append('name', values.name);
            payload.append('price', String(parseCurrencyInput(values.price)));
            payload.append('eta_days', String(values.eta_days ?? 5));
            payload.append('is_default', values.is_default ? '1' : '0');
            payload.append('is_active', values.is_active ? '1' : '0');

            const hasSelection = Boolean(editingOptionId);
            if (hasSelection) {
                payload.append('_method', 'PUT');
            }

            const endpoint = hasSelection
                ? `/seller/checkout-links/frete/${editingOptionId}`
                : '/seller/checkout-links/frete';

            const response = await fetch(endpoint, {
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
                throw new Error(data.message || 'Não foi possível salvar o frete.');
            }

            message.success(data.message || 'Frete salvo com sucesso.');
            const nextOptions = data.shipping_options ?? [];
            setShippingOptions(nextOptions);

            if (nextOptions.length > 0) {
                const nextSelected = data.shipping_option ?? nextOptions.find((option) => option.is_default) ?? nextOptions[0];
                setEditingOptionId(nextSelected?.id ?? null);
                form.setFieldsValue({
                    name: nextSelected?.name ?? 'Frete padrão',
                    price: formatCurrencyInput(nextSelected?.price ?? 0),
                    eta_days: nextSelected?.eta_days ?? 5,
                    is_default: Boolean(nextSelected?.is_default),
                    is_active: Boolean(nextSelected?.is_active),
                });
            } else {
                resetToNewShipping();
            }
        } catch (error) {
            message.error(error.message || 'Falha ao salvar o frete.');
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete(shippingOptionId) {
        const response = await fetch(`/seller/checkout-links/frete/${shippingOptionId}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Não foi possível excluir o frete.');
        }

        message.success(data.message || 'Frete excluído.');
        setShippingOptions(data.shipping_options ?? []);

        const nextOptions = data.shipping_options ?? [];
        if (nextOptions.length > 0) {
            const nextDefault = nextOptions.find((option) => option.is_default) ?? nextOptions[0];
            setEditingOptionId(nextDefault?.id ?? null);
            form.setFieldsValue({
                name: nextDefault?.name ?? 'Frete padrão',
                price: formatCurrencyInput(nextDefault?.price ?? 0),
                eta_days: nextDefault?.eta_days ?? 5,
                is_default: Boolean(nextDefault?.is_default),
                is_active: Boolean(nextDefault?.is_active),
            });
        } else {
            resetToNewShipping();
        }
    }

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card
                    title="Configurar frete"
                    extra={
                        <Button icon={<PlusOutlined />} onClick={resetToNewShipping}>
                            Novo frete
                        </Button>
                    }
                >
                    <Typography.Paragraph type="secondary" style={{ marginBottom: 24 }}>
                        Cadastre as opções de frete que aparecerão no checkout público. Se não houver mais opções, mantenha ao menos o frete padrão.
                    </Typography.Paragraph>

                    <Row gutter={[20, 20]}>
                        <Col xs={24} lg={8}>
                            <Card title="Fretes cadastrados" bordered={false} style={{ background: '#fafafa' }}>
                                <Space direction="vertical" size={12} style={{ width: '100%' }}>
                                    {hasRealOptions ? (
                                        shippingOptions.map((option) => (
                                            <div
                                                key={option.id}
                                                className="rounded-lg border border-slate-200 bg-white p-4"
                                                style={{ cursor: 'pointer' }}
                                                onClick={() => {
                                                    setEditingOptionId(option.id);
                                                }}
                                                onKeyDown={(event) => {
                                                    if (event.key === 'Enter' || event.key === ' ') {
                                                        event.preventDefault();
                                                        setEditingOptionId(option.id);
                                                    }
                                                }}
                                                role="button"
                                                tabIndex={0}
                                            >
                                                <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12 }}>
                                                    <div>
                                                        <Typography.Text strong>{option.name}</Typography.Text>
                                                        <div style={{ color: '#6b7280' }}>
                                                            {option.eta_days ? `Entrega em até ${option.eta_days} dias` : 'Prazo livre'}
                                                        </div>
                                                    </div>
                                                    <Space wrap>
                                                        {option.is_default ? <Tag color="gold">Padrão</Tag> : null}
                                                        {option.is_active ? <Tag color="green">Ativo</Tag> : <Tag color="red">Inativo</Tag>}
                                                    </Space>
                                                </div>
                                                <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 12 }}>
                                                    <strong>{formatCurrency(option.price)}</strong>
                                                    <Space>
                                                        <Button
                                                            type="text"
                                                            icon={<EditOutlined />}
                                                            onClick={(event) => {
                                                                event.stopPropagation();
                                                                setEditingOptionId(option.id);
                                                            }}
                                                        >
                                                            Editar
                                                        </Button>
                                                        <Popconfirm
                                                            title="Excluir este frete?"
                                                            description="A opção continuará disponível no checkout enquanto houver alternativas cadastradas."
                                                            onConfirm={async (event) => {
                                                                event?.stopPropagation?.();
                                                                try {
                                                                    await handleDelete(option.id);
                                                                } catch (error) {
                                                                    message.error(error.message || 'Não foi possível excluir o frete.');
                                                                }
                                                            }}
                                                            okText="Excluir"
                                                            cancelText="Cancelar"
                                                        >
                                                            <Button
                                                                danger
                                                                type="text"
                                                                icon={<DeleteOutlined />}
                                                                onClick={(event) => event.stopPropagation()}
                                                            >
                                                                Excluir
                                                            </Button>
                                                        </Popconfirm>
                                                    </Space>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="rounded-lg border border-dashed border-slate-300 bg-white p-4">
                                            <Typography.Text strong>Frete padrão</Typography.Text>
                                            <Typography.Paragraph type="secondary" style={{ marginBottom: 0 }}>
                                                Configure uma opção padrão para que o checkout sempre tenha ao menos um frete disponível.
                                            </Typography.Paragraph>
                                        </div>
                                    )}
                                </Space>
                            </Card>
                        </Col>

                        <Col xs={24} lg={16}>
                            <Card title={editingOptionId ? 'Editar frete' : 'Novo frete'}>
                                <Form form={form} layout="vertical" onFinish={handleSubmit} initialValues={buildDefaultShippingValues(hasRealOptions)}>
                                    <Row gutter={16}>
                                        <Col xs={24} md={16}>
                                            <Form.Item label="Nome" name="name" rules={[{ required: true, message: 'Informe o nome do frete.' }]}>
                                                <Input placeholder="Frete padrão" />
                                            </Form.Item>
                                        </Col>
                                        <Col xs={24} md={8}>
                                            <Form.Item label="Status" name="is_active" valuePropName="checked">
                                                <Switch checkedChildren="Ativo" unCheckedChildren="Inativo" />
                                            </Form.Item>
                                        </Col>
                                    </Row>

                                    <Row gutter={16}>
                                        <Col xs={24} md={12}>
                                            <Form.Item label="Preço do frete" name="price" rules={[{ required: true, message: 'Informe o preço.' }]}>
                                                <MoneyInputField />
                                            </Form.Item>
                                        </Col>
                                        <Col xs={24} md={12}>
                                            <Form.Item label="Prazo de entrega" name="eta_days" rules={[{ required: true, message: 'Informe o prazo.' }]}>
                                                <InputNumber className="w-full" min={0} max={365} step={1} addonAfter="dias" />
                                            </Form.Item>
                                        </Col>
                                    </Row>

                                    <Form.Item name="is_default" valuePropName="checked">
                                        <Switch checkedChildren="Padrão" unCheckedChildren="Opcional" />
                                    </Form.Item>

                                    <Space>
                                        <Button type="primary" htmlType="submit" loading={saving}>
                                            {editingOptionId ? 'Salvar alteração' : 'Criar frete'}
                                        </Button>
                                        <Button onClick={resetToNewShipping} disabled={saving}>
                                            Limpar
                                        </Button>
                                    </Space>
                                </Form>
                            </Card>
                        </Col>
                    </Row>
                </Card>
            </Col>
        </Row>
    );
}

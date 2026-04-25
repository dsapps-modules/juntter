import {
    BankOutlined,
    CreditCardOutlined,
    MailOutlined,
    QrcodeOutlined,
    SaveOutlined,
    LinkOutlined,
} from '@ant-design/icons';
import { Alert, Button, Card, Col, DatePicker, Divider, Input, InputNumber, Row, Select, Space, Switch, Typography } from 'antd';
import dayjs from 'dayjs';
import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';

const paymentTypes = [
    { label: 'Cartão', value: 'CARTAO', icon: <CreditCardOutlined /> },
    { label: 'PIX', value: 'PIX', icon: <QrcodeOutlined /> },
    { label: 'Boleto', value: 'BOLETO', icon: <BankOutlined /> },
];

const initialState = {
    tipo_pagamento: 'CARTAO',
    descricao: '',
    valor: '',
    parcelas: 1,
    juros: 'CLIENT',
    data_expiracao: null,
    data_vencimento: null,
    data_limite_pagamento: null,
    url_retorno: '',
    url_webhook: '',
    dados_cliente_preenchidos: {
        nome: '',
        sobrenome: '',
        email: '',
        telefone: '',
        documento: '',
        endereco: {
            rua: '',
            numero: '',
            bairro: '',
            cidade: '',
            estado: '',
            cep: '',
            complemento: '',
        },
    },
    instrucoes_boleto: {
        description: '',
        late_fee: { amount: '' },
        interest: { amount: '' },
        discount: { amount: '', limit_date: null },
    },
};

export default function LinkPagamentoFormPage() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const { linkId } = useParams();
    const isEdit = Boolean(linkId);
    const [loading, setLoading] = useState(isEdit);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [formState, setFormState] = useState({
        ...initialState,
        tipo_pagamento: searchParams.get('tipo') ?? 'CARTAO',
    });

    useEffect(() => {
        const controller = new AbortController();

        async function loadLink() {
            if (!isEdit) {
                setLoading(false);
                return;
            }

            try {
                const response = await fetch(`/api/spa/links-pagamento/${linkId}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar o link.');
                }

                const data = await response.json();
                const link = data.link ?? {};

                setFormState({
                    ...initialState,
                    ...link,
                    parcelas: Array.isArray(link.parcelas) ? Math.max(...link.parcelas, 1) : 1,
                    data_expiracao: link.data_expiracao ? dayjs(link.data_expiracao) : null,
                    data_vencimento: link.data_vencimento ? dayjs(link.data_vencimento) : null,
                    data_limite_pagamento: link.data_limite_pagamento ? dayjs(link.data_limite_pagamento) : null,
                    instrucoes_boleto: {
                        ...initialState.instrucoes_boleto,
                        ...(link.instrucoes_boleto ?? {}),
                        late_fee: {
                            amount: link.instrucoes_boleto?.late_fee?.amount ?? '',
                        },
                        interest: {
                            amount: link.instrucoes_boleto?.interest?.amount ?? '',
                        },
                        discount: {
                            amount: link.instrucoes_boleto?.discount?.amount ?? '',
                            limit_date: link.instrucoes_boleto?.discount?.limit_date
                                ? dayjs(link.instrucoes_boleto.discount.limit_date)
                                : null,
                        },
                    },
                    dados_cliente_preenchidos: {
                        ...initialState.dados_cliente_preenchidos,
                        ...(link.dados_cliente_preenchidos ?? {}),
                        endereco: {
                            ...initialState.dados_cliente_preenchidos.endereco,
                            ...(link.dados_cliente_preenchidos?.endereco ?? {}),
                        },
                    },
                });
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar o formulário.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadLink();

        return () => controller.abort();
    }, [isEdit, linkId]);

    const isBoleto = formState.tipo_pagamento === 'BOLETO';
    const isPix = formState.tipo_pagamento === 'PIX';

    const submitUrl = useMemo(() => {
        if (formState.tipo_pagamento === 'PIX') {
            return isEdit ? `/links-pagamento-pix/${linkId}` : '/links-pagamento-pix';
        }

        if (formState.tipo_pagamento === 'BOLETO') {
            return isEdit ? `/links-pagamento-boleto/${linkId}` : '/links-pagamento-boleto';
        }

        return isEdit ? `/links-pagamento/${linkId}` : '/links-pagamento';
    }, [formState.tipo_pagamento, isEdit, linkId]);

    const submitMethod = isEdit ? 'PUT' : 'POST';

    function updateField(path, value) {
        setFormState((current) => {
            if (path === 'tipo_pagamento') {
                return { ...current, tipo_pagamento: value };
            }

            if (path === 'descricao' || path === 'valor' || path === 'parcelas' || path === 'juros' || path === 'url_retorno' || path === 'url_webhook' || path === 'data_expiracao' || path === 'data_vencimento' || path === 'data_limite_pagamento') {
                return { ...current, [path]: value };
            }

            if (path.startsWith('dados_cliente_preenchidos.endereco.')) {
                const key = path.replace('dados_cliente_preenchidos.endereco.', '');

                return {
                    ...current,
                    dados_cliente_preenchidos: {
                        ...current.dados_cliente_preenchidos,
                        endereco: {
                            ...current.dados_cliente_preenchidos.endereco,
                            [key]: value,
                        },
                    },
                };
            }

            if (path.startsWith('dados_cliente_preenchidos.')) {
                const key = path.replace('dados_cliente_preenchidos.', '');

                return {
                    ...current,
                    dados_cliente_preenchidos: {
                        ...current.dados_cliente_preenchidos,
                        [key]: value,
                    },
                };
            }

            if (path.startsWith('instrucoes_boleto.late_fee.')) {
                const key = path.replace('instrucoes_boleto.late_fee.', '');

                return {
                    ...current,
                    instrucoes_boleto: {
                        ...current.instrucoes_boleto,
                        late_fee: {
                            ...current.instrucoes_boleto.late_fee,
                            [key]: value,
                        },
                    },
                };
            }

            if (path.startsWith('instrucoes_boleto.interest.')) {
                const key = path.replace('instrucoes_boleto.interest.', '');

                return {
                    ...current,
                    instrucoes_boleto: {
                        ...current.instrucoes_boleto,
                        interest: {
                            ...current.instrucoes_boleto.interest,
                            [key]: value,
                        },
                    },
                };
            }

            if (path.startsWith('instrucoes_boleto.discount.')) {
                const key = path.replace('instrucoes_boleto.discount.', '');

                return {
                    ...current,
                    instrucoes_boleto: {
                        ...current.instrucoes_boleto,
                        discount: {
                            ...current.instrucoes_boleto.discount,
                            [key]: value,
                        },
                    },
                };
            }

            if (path === 'instrucoes_boleto.description') {
                return {
                    ...current,
                    instrucoes_boleto: {
                        ...current.instrucoes_boleto,
                        description: value,
                    },
                };
            }

            return current;
        });
    }

    async function handleSubmit(event) {
        event.preventDefault();
        setSubmitting(true);
        setError('');
        setSuccess('');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const payload = {
                ...formState,
                data_expiracao: formState.data_expiracao ? formState.data_expiracao.format('YYYY-MM-DD') : null,
                data_vencimento: formState.data_vencimento ? formState.data_vencimento.format('YYYY-MM-DD') : null,
                data_limite_pagamento: formState.data_limite_pagamento
                    ? formState.data_limite_pagamento.format('YYYY-MM-DD')
                    : null,
                instrucoes_boleto: {
                    ...formState.instrucoes_boleto,
                    discount: {
                        ...formState.instrucoes_boleto.discount,
                        limit_date: formState.instrucoes_boleto.discount.limit_date
                            ? formState.instrucoes_boleto.discount.limit_date.format('YYYY-MM-DD')
                            : null,
                    },
                },
            };

            const response = await fetch(submitUrl, {
                method: submitMethod,
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = Object.values(result.errors ?? {}).flat().shift();
                throw new Error(firstError ?? result.message ?? 'Não foi possível salvar o link.');
            }

            setSuccess(result.message ?? 'Link salvo com sucesso.');
            navigate(result.redirect ?? '/links-pagamento');
        } catch (submitError) {
            setError(submitError.message || 'Falha ao salvar o link.');
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <Row gutter={[20, 20]} className="spa-profile-grid">
            <Col xs={24} xl={16}>
                <Card className="spa-hero-card">
                    <Space direction="vertical" size={18} className="spa-hero-stack">
                        <div>
                            <Typography.Text className="spa-brand-kicker">Links de pagamento</Typography.Text>
                            <Typography.Title level={2} className="spa-hero-title">
                                {isEdit ? 'Editar link' : 'Criar novo link'}
                            </Typography.Title>
                            <Typography.Paragraph className="spa-hero-description">
                                Ajuste tipo, valor, parcelamento e instruções do link sem sair do layout da SPA.
                            </Typography.Paragraph>
                        </div>

                        {error ? <Alert type="error" showIcon message={error} /> : null}
                        {success ? <Alert type="success" showIcon message={success} /> : null}

                        <Space wrap>
                            {paymentTypes.map((item) => (
                                <Button
                                    key={item.value}
                                    type={formState.tipo_pagamento === item.value ? 'primary' : 'default'}
                                    icon={item.icon}
                                    disabled={isEdit}
                                    onClick={() => updateField('tipo_pagamento', item.value)}
                                >
                                    {item.label}
                                </Button>
                            ))}
                        </Space>
                    </Space>
                </Card>

                <Card className="spa-table-card" title="Dados do link">
                    {loading ? (
                        <Typography.Text type="secondary">Carregando formulário...</Typography.Text>
                    ) : (
                        <form onSubmit={handleSubmit}>
                            <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <div>
                                            <Typography.Text strong>Descrição</Typography.Text>
                                            <Input
                                                value={formState.descricao}
                                                onChange={(event) => updateField('descricao', event.target.value)}
                                                placeholder="O que o cliente está pagando?"
                                                size="large"
                                            />
                                        </div>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <div>
                                            <Typography.Text strong>Valor</Typography.Text>
                                            <Input
                                                value={formState.valor}
                                                onChange={(event) => updateField('valor', event.target.value)}
                                                placeholder="R$ 0,00"
                                                size="large"
                                            />
                                        </div>
                                    </Col>
                                </Row>

                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <div>
                                            <Typography.Text strong>Quem paga as taxas</Typography.Text>
                                            <Select
                                                value={formState.juros}
                                                onChange={(value) => updateField('juros', value)}
                                                size="large"
                                                options={[
                                                    { value: 'CLIENT', label: 'Cliente' },
                                                    { value: 'ESTABLISHMENT', label: 'Estabelecimento' },
                                                ]}
                                                style={{ width: '100%' }}
                                            />
                                        </div>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <div>
                                            <Typography.Text strong>Expiração</Typography.Text>
                                            <DatePicker
                                                value={formState.data_expiracao}
                                                onChange={(value) => updateField('data_expiracao', value)}
                                                style={{ width: '100%' }}
                                                size="large"
                                            />
                                        </div>
                                    </Col>
                                </Row>

                                {isBoleto ? (
                                    <>
                                        <Row gutter={16}>
                                            <Col xs={24} md={12}>
                                                <div>
                                                    <Typography.Text strong>Vencimento</Typography.Text>
                                                    <DatePicker
                                                        value={formState.data_vencimento}
                                                        onChange={(value) => updateField('data_vencimento', value)}
                                                        style={{ width: '100%' }}
                                                        size="large"
                                                    />
                                                </div>
                                            </Col>
                                            <Col xs={24} md={12}>
                                                <div>
                                                    <Typography.Text strong>Limite de pagamento</Typography.Text>
                                                    <DatePicker
                                                        value={formState.data_limite_pagamento}
                                                        onChange={(value) => updateField('data_limite_pagamento', value)}
                                                        style={{ width: '100%' }}
                                                        size="large"
                                                    />
                                                </div>
                                            </Col>
                                        </Row>
                                        <div>
                                            <Typography.Text strong>Instruções do boleto</Typography.Text>
                                            <Input.TextArea
                                                value={formState.instrucoes_boleto.description}
                                                onChange={(event) => updateField('instrucoes_boleto.description', event.target.value)}
                                                placeholder="Descrição do boleto"
                                                rows={3}
                                            />
                                        </div>
                                    </>
                                ) : null}

                                <Divider />

                                <Typography.Title level={4} className="spa-section-title">
                                    Parcelamento e cliente
                                </Typography.Title>

                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <div>
                                            <Typography.Text strong>Parcelas máximas</Typography.Text>
                                            <InputNumber
                                                min={1}
                                                max={18}
                                                value={formState.parcelas}
                                                onChange={(value) => updateField('parcelas', value ?? 1)}
                                                style={{ width: '100%' }}
                                                size="large"
                                                disabled={isPix || isBoleto}
                                            />
                                        </div>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <div>
                                            <Typography.Text strong>Campos de cliente preenchidos</Typography.Text>
                                            <Switch
                                                checked={Boolean(formState.dados_cliente_preenchidos.nome || formState.dados_cliente_preenchidos.email)}
                                                onChange={(checked) => {
                                                    if (!checked) {
                                                        updateField('dados_cliente_preenchidos', initialState.dados_cliente_preenchidos);
                                                        return;
                                                    }
                                                }}
                                            />
                                        </div>
                                    </Col>
                                </Row>

                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <Input
                                            value={formState.dados_cliente_preenchidos.nome}
                                            onChange={(event) => updateField('dados_cliente_preenchidos.nome', event.target.value)}
                                            placeholder="Nome do cliente"
                                        />
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Input
                                            value={formState.dados_cliente_preenchidos.sobrenome}
                                            onChange={(event) => updateField('dados_cliente_preenchidos.sobrenome', event.target.value)}
                                            placeholder="Sobrenome do cliente"
                                        />
                                    </Col>
                                </Row>

                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <Input
                                            prefix={<MailOutlined />}
                                            value={formState.dados_cliente_preenchidos.email}
                                            onChange={(event) => updateField('dados_cliente_preenchidos.email', event.target.value)}
                                            placeholder="email@exemplo.com"
                                        />
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Input
                                            value={formState.dados_cliente_preenchidos.telefone}
                                            onChange={(event) => updateField('dados_cliente_preenchidos.telefone', event.target.value)}
                                            placeholder="Telefone"
                                        />
                                    </Col>
                                </Row>

                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <Input
                                            value={formState.dados_cliente_preenchidos.documento}
                                            onChange={(event) => updateField('dados_cliente_preenchidos.documento', event.target.value)}
                                            placeholder="CPF/CNPJ"
                                        />
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Input
                                            prefix={<LinkOutlined />}
                                            value={formState.url_retorno}
                                            onChange={(event) => updateField('url_retorno', event.target.value)}
                                            placeholder="URL de retorno"
                                        />
                                    </Col>
                                </Row>

                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <Input
                                            value={formState.url_webhook}
                                            onChange={(event) => updateField('url_webhook', event.target.value)}
                                            placeholder="URL do webhook"
                                        />
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Input
                                            value={formState.dados_cliente_preenchidos.endereco.cidade}
                                            onChange={(event) => updateField('dados_cliente_preenchidos.endereco.cidade', event.target.value)}
                                            placeholder="Cidade"
                                        />
                                    </Col>
                                </Row>

                                {isBoleto ? (
                                    <>
                                        <Row gutter={16}>
                                            <Col xs={24} md={12}>
                                                <Input
                                                    value={formState.dados_cliente_preenchidos.endereco.rua}
                                                    onChange={(event) =>
                                                        updateField('dados_cliente_preenchidos.endereco.rua', event.target.value)
                                                    }
                                                    placeholder="Rua"
                                                />
                                            </Col>
                                            <Col xs={24} md={12}>
                                                <Input
                                                    value={formState.dados_cliente_preenchidos.endereco.numero}
                                                    onChange={(event) =>
                                                        updateField('dados_cliente_preenchidos.endereco.numero', event.target.value)
                                                    }
                                                    placeholder="Número"
                                                />
                                            </Col>
                                        </Row>

                                        <Row gutter={16}>
                                            <Col xs={24} md={12}>
                                                <Input
                                                    value={formState.dados_cliente_preenchidos.endereco.bairro}
                                                    onChange={(event) =>
                                                        updateField('dados_cliente_preenchidos.endereco.bairro', event.target.value)
                                                    }
                                                    placeholder="Bairro"
                                                />
                                            </Col>
                                            <Col xs={24} md={12}>
                                                <Input
                                                    value={formState.dados_cliente_preenchidos.endereco.cidade}
                                                    onChange={(event) =>
                                                        updateField('dados_cliente_preenchidos.endereco.cidade', event.target.value)
                                                    }
                                                    placeholder="Cidade"
                                                />
                                            </Col>
                                        </Row>

                                        <Row gutter={16}>
                                            <Col xs={24} md={8}>
                                                <Select
                                                    value={formState.dados_cliente_preenchidos.endereco.estado}
                                                    onChange={(value) =>
                                                        updateField('dados_cliente_preenchidos.endereco.estado', value)
                                                    }
                                                    style={{ width: '100%' }}
                                                    placeholder="Estado"
                                                    options={[
                                                        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG',
                                                        'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
                                                    ].map((value) => ({ value, label: value }))}
                                                />
                                            </Col>
                                            <Col xs={24} md={8}>
                                                <Input
                                                    value={formState.dados_cliente_preenchidos.endereco.cep}
                                                    onChange={(event) =>
                                                        updateField('dados_cliente_preenchidos.endereco.cep', event.target.value)
                                                    }
                                                    placeholder="CEP"
                                                />
                                            </Col>
                                            <Col xs={24} md={8}>
                                                <Input
                                                    value={formState.dados_cliente_preenchidos.endereco.complemento}
                                                    onChange={(event) =>
                                                        updateField('dados_cliente_preenchidos.endereco.complemento', event.target.value)
                                                    }
                                                    placeholder="Complemento"
                                                />
                                            </Col>
                                        </Row>
                                    </>
                                ) : null}

                                {isBoleto ? (
                                    <>
                                        <Divider />
                                        <Typography.Title level={4} className="spa-section-title">
                                            Juros e desconto
                                        </Typography.Title>
                                        <Row gutter={16}>
                                            <Col xs={24} md={8}>
                                                <Input
                                                    value={formState.instrucoes_boleto.late_fee.amount}
                                                    onChange={(event) => updateField('instrucoes_boleto.late_fee.amount', event.target.value)}
                                                    placeholder="Multa"
                                                />
                                            </Col>
                                            <Col xs={24} md={8}>
                                                <Input
                                                    value={formState.instrucoes_boleto.interest.amount}
                                                    onChange={(event) => updateField('instrucoes_boleto.interest.amount', event.target.value)}
                                                    placeholder="Juros"
                                                />
                                            </Col>
                                            <Col xs={24} md={8}>
                                                <Input
                                                    value={formState.instrucoes_boleto.discount.amount}
                                                    onChange={(event) => updateField('instrucoes_boleto.discount.amount', event.target.value)}
                                                    placeholder="Desconto"
                                                />
                                            </Col>
                                        </Row>
                                    </>
                                ) : null}

                                <div className="spa-profile-actions">
                                    <Button type="primary" htmlType="submit" loading={submitting} icon={<SaveOutlined />}>
                                        Salvar link
                                    </Button>
                                    <Button onClick={() => navigate('/links-pagamento')}>
                                        Cancelar
                                    </Button>
                                </div>
                            </Space>
                        </form>
                    )}
                </Card>
            </Col>

            <Col xs={24} xl={8}>
                <Card className="spa-quick-view-card" title="Resumo do link">
                    <Space direction="vertical" size={14} className="spa-detail-stack">
                        <Typography.Text type="secondary">Tipo</Typography.Text>
                        <Typography.Title level={4} className="spa-section-title">
                            {paymentTypes.find((item) => item.value === formState.tipo_pagamento)?.label ?? 'Cartão'}
                        </Typography.Title>
                        <Typography.Text type="secondary">
                            {isBoleto ? 'Formulário com campos obrigatórios de boleto.' : isPix ? 'Formulário simplificado para PIX.' : 'Formulário para pagamento em cartão.'}
                        </Typography.Text>

                        <Divider />

                        <Typography.Text type="secondary">Ações</Typography.Text>
                        <Link to="/links-pagamento">Voltar para a listagem</Link>
                        <Link to="/links-pagamento/novo">Novo link</Link>
                        <Link to="/links-pagamento/novo?tipo=PIX">Novo PIX</Link>
                        <Link to="/links-pagamento/novo?tipo=BOLETO">Novo boleto</Link>
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

import {
    ArrowLeftOutlined,
    CopyOutlined,
    DeleteOutlined,
    EditOutlined,
    LinkOutlined,
    PauseOutlined,
    PlayCircleOutlined,
    ThunderboltOutlined,
} from '@ant-design/icons';
import { Alert, Button, Card, Col, Descriptions, Empty, Input, Row, Skeleton, Space, Tag, Typography, message } from 'antd';
import dayjs from 'dayjs';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

const defaultLink = {
    id: null,
    codigo_unico: '',
    descricao: '',
    valor: '0.00',
    parcelas: [1],
    parcelas_maximas: 1,
    parcelas_permitidas: [1],
    status: 'ATIVO',
    juros: 'CLIENT',
    tipo_pagamento: 'CARTAO',
    data_expiracao: null,
    data_vencimento: null,
    data_limite_pagamento: null,
    created_at: null,
    url_completa: '',
    url_retorno: '',
    url_webhook: '',
    dados_cliente_preenchidos: {},
    instrucoes_boleto: {},
};

function formatCurrency(value) {
    const numericValue = Number(value ?? 0);

    if (Number.isNaN(numericValue)) {
        return 'R$ 0,00';
    }

    return `R$ ${numericValue.toFixed(2).replace('.', ',')}`;
}

function formatDateTime(value) {
    if (!value) {
        return 'Sem data';
    }

    const date = dayjs(value);

    if (!date.isValid()) {
        return String(value);
    }

    return date.format('DD/MM/YYYY HH:mm');
}

function formatDate(value) {
    if (!value) {
        return 'Sem expiração';
    }

    const date = dayjs(value);

    if (!date.isValid()) {
        return String(value);
    }

    return date.format('DD/MM/YYYY');
}

function formatPaymentType(type) {
    switch (type) {
        case 'PIX':
            return 'PIX';
        case 'BOLETO':
            return 'Boleto';
        default:
            return 'Cartão';
    }
}

function formatStatus(status) {
    switch (status) {
        case 'ATIVO':
            return 'Ativo';
        case 'INATIVO':
            return 'Inativo';
        case 'EXPIRADO':
            return 'Expirado';
        case 'PAID':
            return 'Pago';
        default:
            return status || 'Desconhecido';
    }
}

function getStatusColor(status) {
    switch (status) {
        case 'ATIVO':
            return 'green';
        case 'INATIVO':
            return 'volcano';
        case 'EXPIRADO':
            return 'volcano';
        case 'PAID':
            return 'gold';
        default:
            return 'default';
    }
}

function formatInstallments(parcelas) {
    if (Array.isArray(parcelas)) {
        const numbers = parcelas
            .map((item) => Number(item))
            .filter((item) => Number.isFinite(item) && item > 0)
            .sort((left, right) => left - right);

        if (numbers.length === 0) {
            return '1x';
        }

        if (numbers.length === 1) {
            return `${numbers[0]}x`;
        }

        return `${numbers[0]}x a ${numbers[numbers.length - 1]}x`;
    }

    const numberValue = Number(parcelas);

    if (!Number.isFinite(numberValue) || numberValue <= 0) {
        return '1x';
    }

    return `${numberValue}x`;
}

export default function LinkPagamentoDetailPage() {
    const navigate = useNavigate();
    const { linkId } = useParams();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [link, setLink] = useState(defaultLink);

    useEffect(() => {
        const controller = new AbortController();

        async function loadLink() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch(`/api/spa/links-pagamento/${linkId}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar os detalhes do link.');
                }

                const data = await response.json();
                setLink({
                    ...defaultLink,
                    ...(data.link ?? {}),
                });
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar os detalhes.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadLink();

        return () => controller.abort();
    }, [linkId]);

    const statusLabel = formatStatus(link.status);
    const statusColor = getStatusColor(link.status);
    const paymentTypeLabel = formatPaymentType(link.tipo_pagamento);
    const expirationLabel = formatDate(link.data_expiracao);
    const createdAtLabel = formatDateTime(link.created_at);
    const installmentLabel = formatInstallments(link.parcelas_permitidas ?? link.parcelas ?? link.parcelas_maximas);
    const clientData = link.dados_cliente_preenchidos ?? {};
    const hasClientData = Object.values(clientData).some((value) => Boolean(value));
    const boletoInstructions = link.instrucoes_boleto ?? {};
    const isBoleto = link.tipo_pagamento === 'BOLETO';

    async function copyText(text) {
        if (!text) {
            return;
        }

        try {
            await navigator.clipboard.writeText(text);
            message.success('Copiado para a área de transferência.');
        } catch (copyError) {
            message.error('Não foi possível copiar o texto.');
        }
    }

    async function updateStatus(nextStatus) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(`/links-pagamento/${linkId}/status`, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ status: nextStatus }),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Não foi possível alterar o status.');
            }

            setLink((current) => ({
                ...current,
                status: nextStatus,
            }));

            message.success(result.message ?? 'Status alterado com sucesso.');
        } catch (statusError) {
            message.error(statusError.message || 'Falha ao alterar o status.');
        }
    }

    async function deleteLink() {
        const confirmed = window.confirm('Excluir este link?');

        if (!confirmed) {
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(`/links-pagamento/${linkId}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Não foi possível excluir o link.');
            }

            message.success(result.message ?? 'Link excluído com sucesso.');
            navigate('/links-pagamento');
        } catch (deleteError) {
            message.error(deleteError.message || 'Falha ao excluir o link.');
        }
    }

    const quickActions = [
        {
            label: 'Copiar link',
            icon: <CopyOutlined />,
            onClick: () => copyText(link.url_completa),
        },
        {
            label: 'Testar link',
            icon: <ThunderboltOutlined />,
            onClick: () => window.open(link.url_completa, '_blank', 'noopener,noreferrer'),
        },
        {
            label: 'Editar',
            icon: <EditOutlined />,
            onClick: () => navigate(`/links-pagamento/${linkId}/editar`),
        },
        {
            label: link.status === 'ATIVO' ? 'Desativar' : 'Ativar',
            icon: link.status === 'ATIVO' ? <PauseOutlined /> : <PlayCircleOutlined />,
            onClick: () => updateStatus(link.status === 'ATIVO' ? 'INATIVO' : 'ATIVO'),
        },
        {
            label: 'Excluir',
            icon: <DeleteOutlined />,
            danger: true,
            onClick: deleteLink,
        },
    ];

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} xl={16}>
                <Card className="spa-table-card spa-pix-detail-card">
                    <Space direction="vertical" size={20} style={{ width: '100%' }}>
                        {error ? <Alert type="error" message={error} showIcon /> : null}

                        <div className="spa-pix-detail-header">
                            <div>
                                <Space align="center" size={12}>
                                    <LinkOutlined className="spa-pix-detail-header-icon" />
                                    <Typography.Title level={2} className="spa-pix-detail-title">
                                        Detalhes do link de pagamento
                                    </Typography.Title>
                                </Space>
                                <Typography.Paragraph className="spa-pix-detail-description">
                                    {link.descricao || 'Detalhes do link'}
                                </Typography.Paragraph>
                            </div>

                            <Space wrap>
                                <Button icon={<ArrowLeftOutlined />} onClick={() => navigate('/links-pagamento')}>
                                    Voltar
                                </Button>
                                <Button type="primary" icon={<EditOutlined />} onClick={() => navigate(`/links-pagamento/${linkId}/editar`)}>
                                    Editar
                                </Button>
                            </Space>
                        </div>

                        <Card className="spa-pix-detail-info-card" title="Informações do link" bordered={false}>
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 4 }} />
                            ) : (
                                <Descriptions bordered column={{ xs: 1, md: 2 }} size="small">
                                    <Descriptions.Item label="ID">{link.id ?? '-'}</Descriptions.Item>
                                    <Descriptions.Item label="Código">{link.codigo_unico || '-'}</Descriptions.Item>
                                    <Descriptions.Item label="Tipo">{paymentTypeLabel}</Descriptions.Item>
                                    <Descriptions.Item label="Status">
                                        <Tag color={statusColor}>{statusLabel}</Tag>
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Valor">{formatCurrency(link.valor)}</Descriptions.Item>
                                    <Descriptions.Item label="Parcelas">{installmentLabel}</Descriptions.Item>
                                    <Descriptions.Item label="Quem paga as taxas">
                                        {link.juros === 'ESTABLISHMENT' ? 'Estabelecimento' : 'Cliente'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Criado em">{createdAtLabel}</Descriptions.Item>
                                    <Descriptions.Item label="Expira em">{expirationLabel}</Descriptions.Item>
                                    <Descriptions.Item label="URL de retorno">{link.url_retorno || 'N/A'}</Descriptions.Item>
                                    <Descriptions.Item label="Webhook">{link.url_webhook || 'N/A'}</Descriptions.Item>
                                </Descriptions>
                            )}
                        </Card>

                        <Card className="spa-pix-detail-link-card" title="Link público" bordered={false}>
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 5 }} />
                            ) : (
                                <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                    <div className="spa-pix-detail-url-box">
                                        <Input value={link.url_completa} readOnly size="large" />
                                        <Button
                                            icon={<CopyOutlined />}
                                            onClick={() => copyText(link.url_completa)}
                                            title="Copiar link"
                                            aria-label="Copiar link"
                                        />
                                    </div>

                                    <Space wrap>
                                        <Button
                                            type="primary"
                                            icon={<ThunderboltOutlined />}
                                            onClick={() => window.open(link.url_completa, '_blank', 'noopener,noreferrer')}
                                        >
                                            Testar link
                                        </Button>
                                        <Button
                                            icon={link.status === 'ATIVO' ? <PauseOutlined /> : <PlayCircleOutlined />}
                                            onClick={() => updateStatus(link.status === 'ATIVO' ? 'INATIVO' : 'ATIVO')}
                                        >
                                            {link.status === 'ATIVO' ? 'Desativar' : 'Ativar'}
                                        </Button>
                                        <Button danger icon={<DeleteOutlined />} onClick={deleteLink}>
                                            Excluir
                                        </Button>
                                    </Space>
                                </Space>
                            )}
                        </Card>

                        <Card className="spa-pix-detail-client-card" title="Dados do cliente" bordered={false}>
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 4 }} />
                            ) : hasClientData ? (
                                <Row gutter={[24, 20]}>
                                    {[
                                        ['Nome', clientData.nome],
                                        ['Sobrenome', clientData.sobrenome],
                                        ['E-mail', clientData.email],
                                        ['Telefone', clientData.telefone],
                                        ['Documento', clientData.documento],
                                    ].map(([label, value]) => (
                                        <Col xs={24} md={12} key={label}>
                                            <Typography.Text type="secondary">{label}</Typography.Text>
                                            <div className="spa-pix-client-detail-item">
                                                <Typography.Text strong>{value}</Typography.Text>
                                            </div>
                                        </Col>
                                    ))}
                                </Row>
                            ) : (
                                <Empty description="Nenhum dado de cliente preenchido para este link." />
                            )}
                        </Card>

                        {isBoleto ? (
                            <Card className="spa-pix-detail-config-card" title="Instruções do boleto" bordered={false}>
                                {loading ? (
                                    <Skeleton active paragraph={{ rows: 4 }} />
                                ) : (
                                    <Row gutter={[24, 20]}>
                                        <Col xs={24}>
                                            <Typography.Text type="secondary">Descrição</Typography.Text>
                                            <div>
                                                <Typography.Text strong>{boletoInstructions.description || 'N/A'}</Typography.Text>
                                            </div>
                                        </Col>
                                        <Col xs={24} md={8}>
                                            <Typography.Text type="secondary">Multa</Typography.Text>
                                            <div>
                                                <Typography.Text strong>{boletoInstructions.late_fee?.amount ?? 'N/A'}</Typography.Text>
                                            </div>
                                        </Col>
                                        <Col xs={24} md={8}>
                                            <Typography.Text type="secondary">Juros</Typography.Text>
                                            <div>
                                                <Typography.Text strong>{boletoInstructions.interest?.amount ?? 'N/A'}</Typography.Text>
                                            </div>
                                        </Col>
                                        <Col xs={24} md={8}>
                                            <Typography.Text type="secondary">Desconto</Typography.Text>
                                            <div>
                                                <Typography.Text strong>{boletoInstructions.discount?.amount ?? 'N/A'}</Typography.Text>
                                            </div>
                                        </Col>
                                    </Row>
                                )}
                            </Card>
                        ) : null}
                    </Space>
                </Card>
            </Col>

            <Col xs={24} xl={8}>
                <Card className="spa-quick-view-card spa-pix-detail-side-card" bordered={false}>
                    {loading ? (
                        <Skeleton active paragraph={{ rows: 5 }} />
                    ) : (
                        <Space direction="vertical" size={16} style={{ width: '100%' }}>
                            <div className="spa-pix-detail-side-hero">
                                <LinkOutlined className="spa-pix-detail-side-icon" />
                                <Typography.Title level={4} className="spa-pix-detail-side-title">
                                    {paymentTypeLabel}
                                </Typography.Title>
                            </div>

                            <Card size="small" title="Resumo do link" bordered={false}>
                                <Space direction="vertical" size={12} style={{ width: '100%' }}>
                                    <div>
                                        <Typography.Text type="secondary">Código único</Typography.Text>
                                        <div>
                                            <Typography.Text code>{link.codigo_unico}</Typography.Text>
                                        </div>
                                    </div>

                                    <div>
                                        <Typography.Text type="secondary">Valor e status</Typography.Text>
                                        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                                            <Tag color="green">{formatCurrency(link.valor)}</Tag>
                                            <Tag color={statusColor}>{statusLabel}</Tag>
                                        </div>
                                    </div>

                                    <div>
                                        <Typography.Text type="secondary">Prazo</Typography.Text>
                                        <div>
                                            <Typography.Text>{expirationLabel}</Typography.Text>
                                        </div>
                                    </div>

                                    <div>
                                        <Typography.Text type="secondary">Criado em</Typography.Text>
                                        <div>
                                            <Typography.Text>{createdAtLabel}</Typography.Text>
                                        </div>
                                    </div>
                                </Space>
                            </Card>

                            <Card size="small" title="Ações rápidas" bordered={false}>
                                <Space direction="vertical" size={10} style={{ width: '100%' }}>
                                    {quickActions.map((action) => (
                                        <Button
                                            key={action.label}
                                            block
                                            danger={Boolean(action.danger)}
                                            icon={action.icon}
                                            onClick={action.onClick}
                                        >
                                            {action.label}
                                        </Button>
                                    ))}
                                </Space>
                            </Card>

                            <Card size="small" title="Ajustes" bordered={false}>
                                <Space direction="vertical" size={10} style={{ width: '100%' }}>
                                    <Typography.Text>1. Copie a URL pública para compartilhar com o cliente.</Typography.Text>
                                    <Typography.Text>
                                        2. Edite o link quando precisar ajustar valor, expiração ou dados do cliente.
                                    </Typography.Text>
                                    <Typography.Text>
                                        3. Desative o link para impedir novos pagamentos sem excluir o cadastro.
                                    </Typography.Text>
                                </Space>
                            </Card>
                        </Space>
                    )}
                </Card>
            </Col>
        </Row>
    );
}

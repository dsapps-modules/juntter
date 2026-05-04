import {
    ArrowLeftOutlined,
    BankOutlined,
    CopyOutlined,
    DollarOutlined,
    FileTextOutlined,
    HomeOutlined,
    LinkOutlined,
    UserOutlined,
} from '@ant-design/icons';
import { Alert, Button, Card, Col, Descriptions, Empty, Row, Skeleton, Space, Tag, Typography, message } from 'antd';
import dayjs from 'dayjs';
import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

const defaultPayload = {
    boleto: null,
};

function formatCurrency(valueInCents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((valueInCents ?? 0) / 100);
}

function formatText(value, fallback = 'N/A') {
    return value === null || value === undefined || value === '' ? fallback : value;
}

function formatStatus(status) {
    switch (status) {
        case 'PAID':
            return 'Pago';
        case 'APPROVED':
            return 'Aprovado';
        case 'PENDING':
            return 'Pendente';
        case 'PROCESSING':
            return 'Processando';
        case 'FAILED':
            return 'Falha';
        case 'CANCELED':
            return 'Cancelado';
        case 'REFUNDED':
            return 'Estornado';
        default:
            return status ?? 'Desconhecido';
    }
}

function statusColor(status) {
    switch (formatStatus(status)) {
        case 'Pago':
        case 'Aprovado':
            return 'green';
        case 'Pendente':
        case 'Processando':
            return 'gold';
        case 'Falha':
        case 'Cancelado':
        case 'Estornado':
            return 'red';
        default:
            return 'blue';
    }
}

async function copyText(text) {
    if (!text) {
        return;
    }

    try {
        await navigator.clipboard.writeText(text);
        message.success('Copiado para a área de transferência.');
    } catch (_error) {
        message.error('Não foi possível copiar o texto.');
    }
}

function normalizeInstructionLabel(name) {
    switch (name) {
        case 'late_fee':
            return 'Multa';
        case 'interest':
            return 'Juros';
        case 'discount':
            return 'Desconto';
        default:
            return name ?? 'Instrucao';
    }
}

export default function CobrancaBoletoDetailPage() {
    const navigate = useNavigate();
    const { boletoId } = useParams();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState(defaultPayload);

    useEffect(() => {
        const controller = new AbortController();

        async function loadBoleto() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch(`/api/spa/cobranca/boleto/${boletoId}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'Não foi possível carregar os detalhes do boleto.');
                }

                setPayload({
                    boleto: data.boleto ?? null,
                });
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar os detalhes do boleto.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadBoleto();

        return () => controller.abort();
    }, [boletoId]);

    const boleto = payload.boleto;

    const instructionItems = useMemo(() => {
        return (boleto?.billing_instructions ?? []).map((item) => {
            const label = normalizeInstructionLabel(item?.name);
            const mode = item?.mode === 'PERCENTAGE'
                ? 'Percentual'
                : item?.mode === 'MONTHLY_PERCENTAGE'
                    ? 'Percentual mensal'
                    : item?.mode ?? 'N/A';
            const suffix = item?.mode === 'PERCENTAGE' || item?.mode === 'MONTHLY_PERCENTAGE' ? '%' : '';

            return {
                label,
                mode,
                amount: item?.amount ?? '0',
                suffix,
                limit_date: item?.limit_date ?? null,
            };
        });
    }, [boleto?.billing_instructions]);

    const paymentLimitDate = boleto?.payment_limit_date
        ? dayjs(boleto.payment_limit_date).format('DD/MM/YYYY')
        : boleto?.expiration_at
            ? dayjs(boleto.expiration_at).format('DD/MM/YYYY')
            : 'N/A';

    const createdAt = boleto?.created_at ? dayjs(boleto.created_at).format('DD/MM/YYYY HH:mm') : 'N/A';
    const updatedAt = boleto?.updated_at ? dayjs(boleto.updated_at).format('DD/MM/YYYY HH:mm') : 'N/A';
    const expirationAt = boleto?.expiration_at ? dayjs(boleto.expiration_at).format('DD/MM/YYYY HH:mm') : 'N/A';
    const paidAt = boleto?.paid_at ? dayjs(boleto.paid_at).format('DD/MM/YYYY HH:mm') : 'N/A';
    const hasBillingInstructions = instructionItems.length > 0;

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} xl={16}>
                <Card className="spa-table-card spa-pix-detail-card">
                    <Space direction="vertical" size={20} style={{ width: '100%' }}>
                        {error ? <Alert type="error" message={error} showIcon /> : null}

                        <div className="spa-pix-detail-header">
                            <div>
                                <Space align="center" size={12}>
                                    <FileTextOutlined className="spa-pix-detail-header-icon" />
                                    <Typography.Title level={2} className="spa-pix-detail-title">
                                        Boleto {boleto?.external_id || boletoId}
                                    </Typography.Title>
                                </Space>
                                <Typography.Paragraph className="spa-pix-detail-description">
                                    Detalhes completos do boleto emitido na cobrança.
                                </Typography.Paragraph>
                            </div>

                            <Space wrap>
                                <Button icon={<ArrowLeftOutlined />} onClick={() => navigate('/cobranca/boleto')}>
                                    Voltar
                                </Button>
                                <Button icon={<HomeOutlined />} onClick={() => navigate('/cobranca')}>
                                    Cobrança
                                </Button>
                            </Space>
                        </div>

                        <Card className="spa-pix-detail-info-card" title="Informações do boleto" bordered={false}>
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 6 }} />
                            ) : boleto ? (
                                <Descriptions bordered column={{ xs: 1, md: 2 }} size="small">
                                    <Descriptions.Item label="ID do boleto">{formatText(boleto.external_id)}</Descriptions.Item>
                                    <Descriptions.Item label="Status">
                                        <Tag color={statusColor(boleto.status)}>{formatStatus(boleto.status)}</Tag>
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Valor final">{formatCurrency(boleto.amount)}</Descriptions.Item>
                                    <Descriptions.Item label="Valor original">{formatCurrency(boleto.original_amount)}</Descriptions.Item>
                                    <Descriptions.Item label="Taxas">{formatCurrency(boleto.fees)}</Descriptions.Item>
                                    <Descriptions.Item label="Gateway">{formatText(boleto.gateway_key || boleto.authorization_code)}</Descriptions.Item>
                                    <Descriptions.Item label="Emissão">{createdAt}</Descriptions.Item>
                                    <Descriptions.Item label="Atualizado em">{updatedAt}</Descriptions.Item>
                                    <Descriptions.Item label="Vencimento">{expirationAt}</Descriptions.Item>
                                    <Descriptions.Item label="Data limite de pagamento">{paymentLimitDate}</Descriptions.Item>
                                    <Descriptions.Item label="Pago em">{paidAt}</Descriptions.Item>
                                    <Descriptions.Item label="Linha digitável">
                                        <Space.Compact style={{ width: '100%' }}>
                                            <Typography.Text
                                                code
                                                style={{
                                                    display: 'block',
                                                    width: '100%',
                                                    padding: '8px 12px',
                                                    overflow: 'hidden',
                                                    textOverflow: 'ellipsis',
                                                }}
                                            >
                                                {formatText(boleto.boleto_digitable_line)}
                                            </Typography.Text>
                                            <Button icon={<CopyOutlined />} onClick={() => copyText(boleto.boleto_digitable_line)} />
                                        </Space.Compact>
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Código de barras">
                                        <Space.Compact style={{ width: '100%' }}>
                                            <Typography.Text
                                                code
                                                style={{
                                                    display: 'block',
                                                    width: '100%',
                                                    padding: '8px 12px',
                                                    overflow: 'hidden',
                                                    textOverflow: 'ellipsis',
                                                }}
                                            >
                                                {formatText(boleto.boleto_barcode)}
                                            </Typography.Text>
                                            <Button icon={<CopyOutlined />} onClick={() => copyText(boleto.boleto_barcode)} />
                                        </Space.Compact>
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Link do boleto">
                                        {boleto.boleto_url ? (
                                            <Space wrap>
                                                <Button
                                                    type="primary"
                                                    icon={<LinkOutlined />}
                                                    onClick={() => window.open(boleto.boleto_url, '_blank', 'noopener,noreferrer')}
                                                >
                                                    Abrir boleto
                                                </Button>
                                                <Button icon={<CopyOutlined />} onClick={() => copyText(boleto.boleto_url)} />
                                            </Space>
                                        ) : (
                                            'N/A'
                                        )}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="PIX (Copia e Cola)">
                                        {boleto.pix_emv ? (
                                            <Space wrap>
                                                <Typography.Text code>{boleto.pix_emv}</Typography.Text>
                                                <Button icon={<CopyOutlined />} onClick={() => copyText(boleto.pix_emv)} />
                                            </Space>
                                        ) : (
                                            'N/A'
                                        )}
                                    </Descriptions.Item>
                                </Descriptions>
                            ) : (
                                <Empty description="Boleto não encontrado." />
                            )}
                        </Card>

                        {hasBillingInstructions ? (
                            <Card className="spa-pix-detail-config-card" title="Instruções do boleto" bordered={false}>
                                <Space direction="vertical" size={12} style={{ width: '100%' }}>
                                    {instructionItems.map((item) => (
                                        <Space key={`${item.label}-${item.amount}`} align="start" style={{ width: '100%' }}>
                                            <Tag color="blue">{item.label}</Tag>
                                            <Typography.Text>
                                                {item.mode}: {item.amount}
                                                {item.suffix}
                                                {item.limit_date ? ` (até ${dayjs(item.limit_date).format('DD/MM/YYYY')})` : ''}
                                            </Typography.Text>
                                        </Space>
                                    ))}
                                </Space>
                            </Card>
                        ) : null}
                    </Space>
                </Card>
            </Col>

            <Col xs={24} xl={8}>
                <Card className="spa-quick-view-card spa-pix-detail-side-card" bordered={false}>
                    {loading ? (
                        <Skeleton active paragraph={{ rows: 5 }} />
                    ) : boleto ? (
                        <Space direction="vertical" size={16} style={{ width: '100%' }}>
                            <div className="spa-pix-detail-side-hero">
                                <BankOutlined className="spa-pix-detail-side-icon" />
                                <Typography.Title level={4} className="spa-pix-detail-side-title">
                                    Resumo do boleto
                                </Typography.Title>
                            </div>

                            <Card size="small" title="Cliente" bordered={false}>
                                <Space direction="vertical" size={8} style={{ width: '100%' }}>
                                    <Typography.Text strong>
                                        {formatText(boleto.customer?.first_name)} {formatText(boleto.customer?.last_name, '')}
                                    </Typography.Text>
                                    <Typography.Text type="secondary">
                                        CPF/CNPJ: {formatText(boleto.customer?.document)}
                                    </Typography.Text>
                                    <Typography.Text type="secondary">
                                        Email: {formatText(boleto.customer?.email)}
                                    </Typography.Text>
                                </Space>
                            </Card>

                            <Card size="small" title="Estabelecimento" bordered={false}>
                                <Space direction="vertical" size={8} style={{ width: '100%' }}>
                                    <Typography.Text type="secondary">ID: {formatText(boleto.establishment?.id)}</Typography.Text>
                                    <Typography.Text type="secondary">Nome: {formatText(boleto.establishment?.name)}</Typography.Text>
                                    <Typography.Text type="secondary">
                                        Status: <Tag color={statusColor(boleto.status)}>{formatStatus(boleto.status)}</Tag>
                                    </Typography.Text>
                                </Space>
                            </Card>

                            <Card size="small" title="Valores" bordered={false}>
                                <Space direction="vertical" size={8} style={{ width: '100%' }}>
                                    <Typography.Text>
                                        <DollarOutlined /> Valor final: {formatCurrency(boleto.amount)}
                                    </Typography.Text>
                                    <Typography.Text type="secondary">
                                        Valor original: {formatCurrency(boleto.original_amount)}
                                    </Typography.Text>
                                    <Typography.Text type="secondary">
                                        Taxas: {formatCurrency(boleto.fees)}
                                    </Typography.Text>
                                </Space>
                            </Card>

                            <Card size="small" title="Ações" bordered={false}>
                                <Space direction="vertical" size={10} style={{ width: '100%' }}>
                                    {boleto.boleto_url ? (
                                        <Button
                                            type="primary"
                                            icon={<LinkOutlined />}
                                            onClick={() => window.open(boleto.boleto_url, '_blank', 'noopener,noreferrer')}
                                        >
                                            Abrir PDF do boleto
                                        </Button>
                                    ) : null}
                                    <Button icon={<CopyOutlined />} onClick={() => copyText(boleto.boleto_digitable_line)}>
                                        Copiar linha digitável
                                    </Button>
                                    <Button icon={<UserOutlined />} onClick={() => navigate('/cobranca/boleto')}>
                                        Voltar para a lista
                                    </Button>
                                </Space>
                            </Card>
                        </Space>
                    ) : (
                        <Empty description="Nenhum detalhe disponível." />
                    )}
                </Card>
            </Col>
        </Row>
    );
}

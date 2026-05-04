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
import { Alert, Button, Card, Col, Empty, Row, Skeleton, Space, Tag, Typography, message } from 'antd';
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

function normalizeInstructionLabel(name) {
    switch (name) {
        case 'late_fee':
            return 'Multa';
        case 'interest':
            return 'Juros';
        case 'discount':
            return 'Desconto';
        default:
            return name ?? 'Instrução';
    }
}

function splitName(name) {
    const parts = String(name ?? '').trim().split(/\s+/).filter(Boolean);

    return {
        first_name: parts[0] ?? '',
        last_name: parts.slice(1).join(' '),
    };
}

function copyText(text) {
    if (!text) {
        return;
    }

    navigator.clipboard.writeText(text)
        .then(() => message.success('Copiado para a área de transferência.'))
        .catch(() => message.error('Não foi possível copiar o texto.'));
}

function InfoTile({ label, value, tone = 'default' }) {
    const toneClassName = tone === 'accent' ? 'spa-pix-mini-stat-card' : 'spa-pix-detail-metric-card';

    return (
        <Card size="small" bordered={false} className={toneClassName}>
            <Typography.Text type="secondary">{label}</Typography.Text>
            <div>
                <Typography.Text strong className="spa-pix-detail-metric-value">
                    {value}
                </Typography.Text>
            </div>
        </Card>
    );
}

function CopyField({ label, value, buttonLabel = 'Copiar' }) {
    return (
        <div className="spa-pix-detail-field">
            <Typography.Text type="secondary" className="spa-pix-detail-field-label">
                {label}
            </Typography.Text>
            <Space.Compact style={{ width: '100%' }}>
                <Typography.Text
                    code
                    className="spa-pix-detail-code"
                    style={{
                        display: 'block',
                        width: '100%',
                        padding: '12px 14px',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                        whiteSpace: 'nowrap',
                    }}
                >
                    {formatText(value)}
                </Typography.Text>
                <Button icon={<CopyOutlined />} onClick={() => copyText(value)}>
                    {buttonLabel}
                </Button>
            </Space.Compact>
        </div>
    );
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

    const customerName = `${formatText(boleto?.customer?.first_name, '')} ${formatText(boleto?.customer?.last_name, '')}`.trim() || 'N/A';
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
    const boletoStatus = formatStatus(boleto?.status);

    return (
        <Row gutter={[20, 20]} className="spa-board spa-pix-board">
            <Col xs={24} xl={16}>
                <Card className="spa-table-card spa-pix-detail-card">
                    <Space direction="vertical" size={20} style={{ width: '100%' }}>
                        {error ? <Alert type="error" message={error} showIcon /> : null}

                        <div className="spa-pix-detail-header">
                            <div>
                                <Space align="center" size={12}>
                                    <FileTextOutlined className="spa-pix-detail-header-icon" />
                                    <div>
                                        <Typography.Title level={2} className="spa-pix-detail-title" style={{ marginBottom: 0 }}>
                                            Boleto {boleto?.external_id || boletoId}
                                        </Typography.Title>
                                        <Typography.Paragraph className="spa-pix-detail-description" style={{ marginBottom: 0 }}>
                                            Visualização organizada dos dados, códigos e instruções do boleto.
                                        </Typography.Paragraph>
                                    </div>
                                </Space>
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

                        {loading ? (
                            <Skeleton active paragraph={{ rows: 10 }} />
                        ) : boleto ? (
                            <Space direction="vertical" size={20} style={{ width: '100%' }}>
                                <Row gutter={[12, 12]}>
                                    <Col xs={12} lg={6}>
                                        <InfoTile
                                            label="Status"
                                            value={<Tag color={statusColor(boleto.status)}>{boletoStatus}</Tag>}
                                            tone="accent"
                                        />
                                    </Col>
                                    <Col xs={12} lg={6}>
                                        <InfoTile label="Valor final" value={formatCurrency(boleto.amount)} />
                                    </Col>
                                    <Col xs={12} lg={6}>
                                        <InfoTile label="Vencimento" value={expirationAt} />
                                    </Col>
                                    <Col xs={12} lg={6}>
                                        <InfoTile label="Gateway" value={formatText(boleto.gateway_key || boleto.authorization_code)} />
                                    </Col>
                                </Row>

                                <Card className="spa-pix-detail-info-card" title="Informações principais" bordered={false}>
                                    <Row gutter={[16, 16]}>
                                        <Col xs={24} md={12}>
                                            <div className="spa-pix-detail-info-grid">
                                                <div>
                                                    <Typography.Text type="secondary">ID do boleto</Typography.Text>
                                                    <Typography.Title level={5} style={{ marginTop: 4 }}>
                                                        {formatText(boleto.external_id)}
                                                    </Typography.Title>
                                                </div>
                                                <div>
                                                    <Typography.Text type="secondary">Status</Typography.Text>
                                                    <div style={{ marginTop: 4 }}>
                                                        <Tag color={statusColor(boleto.status)}>{boletoStatus}</Tag>
                                                    </div>
                                                </div>
                                                <div>
                                                    <Typography.Text type="secondary">Valor original</Typography.Text>
                                                    <Typography.Title level={5} style={{ marginTop: 4 }}>
                                                        {formatCurrency(boleto.original_amount)}
                                                    </Typography.Title>
                                                </div>
                                                <div>
                                                    <Typography.Text type="secondary">Taxas</Typography.Text>
                                                    <Typography.Title level={5} style={{ marginTop: 4 }}>
                                                        {formatCurrency(boleto.fees)}
                                                    </Typography.Title>
                                                </div>
                                            </div>
                                        </Col>

                                        <Col xs={24} md={12}>
                                            <div className="spa-pix-detail-info-grid">
                                                <div>
                                                    <Typography.Text type="secondary">Emissão</Typography.Text>
                                                    <Typography.Title level={5} style={{ marginTop: 4 }}>
                                                        {createdAt}
                                                    </Typography.Title>
                                                </div>
                                                <div>
                                                    <Typography.Text type="secondary">Atualizado em</Typography.Text>
                                                    <Typography.Title level={5} style={{ marginTop: 4 }}>
                                                        {updatedAt}
                                                    </Typography.Title>
                                                </div>
                                                <div>
                                                    <Typography.Text type="secondary">Data limite de pagamento</Typography.Text>
                                                    <Typography.Title level={5} style={{ marginTop: 4 }}>
                                                        {paymentLimitDate}
                                                    </Typography.Title>
                                                </div>
                                                <div>
                                                    <Typography.Text type="secondary">Pago em</Typography.Text>
                                                    <Typography.Title level={5} style={{ marginTop: 4 }}>
                                                        {paidAt}
                                                    </Typography.Title>
                                                </div>
                                            </div>
                                        </Col>
                                    </Row>
                                </Card>

                                <Card className="spa-pix-detail-config-card" title="Códigos do pagamento" bordered={false}>
                                    <Row gutter={[16, 16]}>
                                        <Col xs={24}>
                                            <CopyField
                                                label="Linha digitável"
                                                value={boleto.boleto_digitable_line}
                                                buttonLabel="Copiar"
                                            />
                                        </Col>
                                        <Col xs={24}>
                                            <CopyField
                                                label="Código de barras"
                                                value={boleto.boleto_barcode}
                                                buttonLabel="Copiar"
                                            />
                                        </Col>
                                        <Col xs={24}>
                                            <div className="spa-pix-detail-field">
                                                <Typography.Text type="secondary" className="spa-pix-detail-field-label">
                                                    Link do boleto
                                                </Typography.Text>
                                                {boleto.boleto_url ? (
                                                    <Space wrap>
                                                        <Button
                                                            type="primary"
                                                            icon={<LinkOutlined />}
                                                            onClick={() => window.open(boleto.boleto_url, '_blank', 'noopener,noreferrer')}
                                                        >
                                                            Abrir boleto
                                                        </Button>
                                                        <Button icon={<CopyOutlined />} onClick={() => copyText(boleto.boleto_url)}>
                                                            Copiar link
                                                        </Button>
                                                    </Space>
                                                ) : (
                                                    <Empty description="Link do boleto indisponível." />
                                                )}
                                            </div>
                                        </Col>
                                        <Col xs={24}>
                                            <div className="spa-pix-detail-field">
                                                <Typography.Text type="secondary" className="spa-pix-detail-field-label">
                                                    PIX (Copia e Cola)
                                                </Typography.Text>
                                                {boleto.pix_emv ? (
                                                    <Space.Compact style={{ width: '100%' }}>
                                                        <Typography.Text
                                                            code
                                                            className="spa-pix-detail-code"
                                                            style={{
                                                                display: 'block',
                                                                width: '100%',
                                                                padding: '12px 14px',
                                                                overflow: 'hidden',
                                                                textOverflow: 'ellipsis',
                                                                whiteSpace: 'nowrap',
                                                            }}
                                                        >
                                                            {boleto.pix_emv}
                                                        </Typography.Text>
                                                        <Button icon={<CopyOutlined />} onClick={() => copyText(boleto.pix_emv)}>
                                                            Copiar
                                                        </Button>
                                                    </Space.Compact>
                                                ) : (
                                                    <Empty description="PIX não disponível para este boleto." />
                                                )}
                                            </div>
                                        </Col>
                                    </Row>
                                </Card>

                                {hasBillingInstructions ? (
                                    <Card className="spa-pix-detail-config-card" title="Instruções do boleto" bordered={false}>
                                        <Space direction="vertical" size={12} style={{ width: '100%' }}>
                                            {instructionItems.map((item) => (
                                                <div key={`${item.label}-${item.amount}`} className="spa-pix-detail-instruction-item">
                                                    <Tag color="blue">{item.label}</Tag>
                                                    <Typography.Text>
                                                        {item.mode}: {item.amount}
                                                        {item.suffix}
                                                        {item.limit_date ? ` (até ${dayjs(item.limit_date).format('DD/MM/YYYY')})` : ''}
                                                    </Typography.Text>
                                                </div>
                                            ))}
                                        </Space>
                                    </Card>
                                ) : null}
                            </Space>
                        ) : (
                            <Empty description="Boleto não encontrado." />
                        )}
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
                                <Typography.Title level={4} className="spa-pix-detail-side-title" style={{ marginBottom: 0 }}>
                                    Resumo do boleto
                                </Typography.Title>
                            </div>

                            <Card size="small" title="Cliente" bordered={false}>
                                <Space direction="vertical" size={8} style={{ width: '100%' }}>
                                    <Typography.Text strong>{customerName}</Typography.Text>
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
                                        Status: <Tag color={statusColor(boleto.status)}>{boletoStatus}</Tag>
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
                                            block
                                        >
                                            Abrir PDF do boleto
                                        </Button>
                                    ) : null}
                                    <Button icon={<CopyOutlined />} onClick={() => copyText(boleto.boleto_digitable_line)} block>
                                        Copiar linha digitável
                                    </Button>
                                    <Button icon={<UserOutlined />} onClick={() => navigate('/cobranca/boleto')} block>
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

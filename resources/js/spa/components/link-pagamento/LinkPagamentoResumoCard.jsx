import { Card, Space, Tag, Typography } from 'antd';

function formatCurrency(value) {
    const numericValue = Number(value ?? 0);

    if (Number.isNaN(numericValue)) {
        return 'R$ 0,00';
    }

    return `R$ ${numericValue.toFixed(2).replace('.', ',')}`;
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

export default function LinkPagamentoResumoCard({
    link,
    paymentSummary = {},
    showPaymentBreakdown = true,
    title = 'Resumo do link',
    codeLabel = 'Codigo unico',
    statusLabel: statusLabelOverride = null,
    statusColor: statusColorOverride = null,
    expirationLabel = 'Sem expiracao',
    createdAtLabel = 'Sem data',
}) {
    const statusLabel = statusLabelOverride ?? formatStatus(link?.status);
    const statusColor = statusColorOverride ?? getStatusColor(link?.status);
    const baseAmountCents = Number(paymentSummary.base_amount_cents ?? 0);
    const feeAmountCents = Number(paymentSummary.fee_amount_cents ?? 0);
    const baseAmountLabel = paymentSummary.base_amount_formatted || formatCurrency(link?.valor_centavos ?? Number(link?.valor) * 100);
    const feeAmountLabel = paymentSummary.fee_amount_formatted || formatCurrency(0);
    const totalAmountLabel = paymentSummary.total_amount_formatted || baseAmountLabel;

    return (
        <Card size="small" title={title} bordered={false}>
            <Space direction="vertical" size={12} style={{ width: '100%' }}>
                <div>
                    <Typography.Text type="secondary">{codeLabel}</Typography.Text>
                    <div>
                        <Typography.Text code>{link?.codigo_unico || '-'}</Typography.Text>
                    </div>
                </div>

                <div>
                    <Typography.Text type="secondary">{showPaymentBreakdown ? 'Valor, taxa e total' : 'Valor'}</Typography.Text>
                    {showPaymentBreakdown ? (
                        <Space direction="vertical" size={6} style={{ width: '100%' }}>
                            <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                                <Tag color="green">{`Base ${baseAmountLabel}`}</Tag>
                                {feeAmountCents > 0 ? <Tag color="orange">{`Taxa ${feeAmountLabel}`}</Tag> : null}
                            </div>
                            <Tag color="blue">{`Total ${totalAmountLabel}`}</Tag>
                        </Space>
                    ) : (
                        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                            <Tag color="green">{formatCurrency(link?.valor)}</Tag>
                        </div>
                    )}
                </div>

                <div>
                    <Typography.Text type="secondary">Status</Typography.Text>
                    <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
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
    );
}


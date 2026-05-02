import { CreditCardOutlined, DollarOutlined } from '@ant-design/icons';
import { Alert, Card, Col, Descriptions, Divider, List, Row, Space, Tag, Typography } from 'antd';
import { useMemo, useState } from 'react';
import PaymentAmountField from '../../components/payment-simulator/PaymentAmountField';
import PaymentInstallmentSelector from '../../components/payment-simulator/PaymentInstallmentSelector';
import PaymentPlanSelector from '../../components/payment-simulator/PaymentPlanSelector';
import { getPaymentPlanByValue, paymentPlanRates } from '../../components/payment-simulator/paymentSimulationConfig';

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

function formatCurrency(value) {
    return currencyFormatter.format(value);
}

function formatAmountInput(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    return new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value));
}

function parseAmountInput(value) {
    if (typeof value !== 'string') {
        return value;
    }

    return value.replace(/\s?R\$\s?/g, '').replace(/\./g, '').replace(',', '.');
}

function buildInstallmentBreakdown(amount, installments) {
    const totalCents = Math.round(amount * 100);
    const baseCents = Math.floor(totalCents / installments);
    const remainder = totalCents % installments;

    return Array.from({ length: installments }, (_, index) => ({
        number: index + 1,
        amount: (baseCents + (index < remainder ? 1 : 0)) / 100,
    }));
}

export default function CobrancaSimularPage() {
    const [plan, setPlan] = useState('acelerar');
    const [amount, setAmount] = useState(1000);
    const [installments, setInstallments] = useState(6);

    const selectedPlan = getPaymentPlanByValue(plan);
    const selectedRate = paymentPlanRates[plan]?.[installments] ?? 0;
    const parsedAmount = Number.isFinite(amount) ? amount : 0;
    const totalAmount = parsedAmount + parsedAmount * (selectedRate / 100);
    const installmentAmount = installments > 0 ? totalAmount / installments : 0;
    const installmentBreakdown = useMemo(() => {
        if (totalAmount <= 0 || installments <= 0) {
            return [];
        }

        return buildInstallmentBreakdown(totalAmount, installments);
    }, [installments, totalAmount]);

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} lg={10}>
                <Card className="spa-table-card" title="Dados da simulação" extra={<CreditCardOutlined />}>
                    <Space direction="vertical" size={18} style={{ width: '100%' }}>
                        <PaymentPlanSelector value={plan} onChange={setPlan} align="right" />

                        <PaymentAmountField
                            value={amount}
                            onChange={setAmount}
                            formatter={formatAmountInput}
                            parser={parseAmountInput}
                            align="right"
                        />

                        <PaymentInstallmentSelector
                            value={String(installments)}
                            onChange={(value) => setInstallments(Number(value))}
                            align="right"
                        />

                    </Space>
                </Card>
            </Col>

            <Col xs={24} lg={14}>
                <Card className="spa-table-card" title="Resultado da simulação" extra={<DollarOutlined />}>
                    <Space direction="vertical" size={18} style={{ width: '100%' }}>
                        <Descriptions bordered column={{ xs: 1, sm: 2, md: 2 }} size="small">
                            <Descriptions.Item label="Plano">{selectedPlan.label}</Descriptions.Item>
                            <Descriptions.Item label="Valor">{formatCurrency(parsedAmount)}</Descriptions.Item>
                            <Descriptions.Item label="Parcelas">{`${installments}x`}</Descriptions.Item>
                            <Descriptions.Item label="Taxa">{selectedRate.toFixed(2).replace('.', ',')}%</Descriptions.Item>
                            <Descriptions.Item label="Parcela">{formatCurrency(installmentAmount)}</Descriptions.Item>
                            <Descriptions.Item label="Total">{formatCurrency(totalAmount)}</Descriptions.Item>
                        </Descriptions>

                        <Divider orientation="left">Valor de cada parcela</Divider>

                        {installmentBreakdown.length === 0 ? (
                            <Alert
                                type="warning"
                                showIcon
                                message="Informe um valor maior que zero para visualizar a divisão das parcelas."
                            />
                ) : (
                    <List
                        bordered
                        dataSource={installmentBreakdown}
                                renderItem={(item) => (
                                    <List.Item>
                                        <Space>
                                            <Tag color="gold">{`${item.number}x`}</Tag>
                                            <Typography.Text>Parcela {item.number}</Typography.Text>
                                        </Space>
                                        <Typography.Text strong>{formatCurrency(item.amount)}</Typography.Text>
                                    </List.Item>
                                )}
                            />
                        )}
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

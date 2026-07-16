import { CreditCardOutlined, DollarOutlined } from '@ant-design/icons';
import { Alert, Card, Col, Divider, Row, Space, Spin, Tag, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import PaymentAmountField from '../../components/payment-simulator/PaymentAmountField';
import PaymentInstallmentSelector from '../../components/payment-simulator/PaymentInstallmentSelector';
import PaymentPlanSelector from '../../components/payment-simulator/PaymentPlanSelector';

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

function normalizeFlags(flags) {
    if (!Array.isArray(flags)) {
        return [];
    }

    return flags.filter((flag) => {
        if (!flag || typeof flag !== 'object') {
            return false;
        }

        return String(flag.name ?? '').toUpperCase() !== 'BACEN';
    });
}

function formatFlagLabel(flag) {
    const flagName = String(flag?.name ?? '').trim();

    if (flagName.toUpperCase() === 'OTHERS') {
        return 'Outros';
    }

    return `${flagName || `Bandeira ${flag?.id ?? ''}`}`.trim();
}

function buildFlagOptions(flags) {
    return normalizeFlags(flags).map((flag) => ({
        label: formatFlagLabel(flag),
        value: String(flag.id ?? flag.name ?? ''),
    }));
}

function buildInstallmentOptions(flag) {
    const creditFees = flag?.fees?.credit;

    if (!creditFees || typeof creditFees !== 'object') {
        return [];
    }

    return Object.entries(creditFees)
        .map(([key, value]) => ({
            key,
            amount: Number(value),
        }))
        .filter((item) => /^(\d+)x$/.test(item.key) && Number.isFinite(item.amount))
        .sort((left, right) => Number.parseInt(left.key, 10) - Number.parseInt(right.key, 10))
        .map((item) => ({
            label: item.key,
            value: item.key,
        }));
}

function resolveSelectedFlag(flags, selectedFlagId) {
    if (flags.length === 0) {
        return null;
    }

    if (selectedFlagId !== null) {
        const matchedFlag = flags.find((flag) => String(flag.id ?? flag.name ?? '') === selectedFlagId);

        if (matchedFlag) {
            return matchedFlag;
        }
    }

    return flags.find((flag) => flag.active) ?? flags[0] ?? null;
}

function resolveRate(flag, installmentValue) {
    if (!flag) {
        return 0;
    }

    const creditFees = flag?.fees?.credit ?? {};

    return Number(creditFees[installmentValue] ?? 0);
}

function ResultMetric({ label, value, hint }) {
    return (
        <div className="spa-sim-result-metric">
            <Typography.Text type="secondary" className="spa-sim-result-metric-label">
                {label}
            </Typography.Text>
            <Typography.Title level={4} className="spa-sim-result-metric-value">
                {value}
            </Typography.Title>
            {hint ? (
                <Typography.Text type="secondary" className="spa-sim-result-metric-hint">
                    {hint}
                </Typography.Text>
            ) : null}
        </div>
    );
}

export default function CobrancaSimularPage() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [sellerName, setSellerName] = useState('Vendedor');
    const [planName, setPlanName] = useState('Plano contratado');
    const [flags, setFlags] = useState([]);
    const [selectedFlagId, setSelectedFlagId] = useState(null);
    const [amount, setAmount] = useState(1000);
    const [installments, setInstallments] = useState('6x');

    useEffect(() => {
        const controller = new AbortController();

        async function loadPlanData() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch('/api/spa/cobranca/planos', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar o plano contratado.');
                }

                const data = await response.json();
                const contractedFlags = normalizeFlags(data.plan?.flags ?? []);
                const selectedFlag = contractedFlags.find((flag) => flag.active) ?? contractedFlags[0] ?? null;

                setSellerName(data.seller_name ?? 'Vendedor');
                setPlanName(data.plan?.name ?? 'Plano contratado');
                setFlags(contractedFlags);
                setSelectedFlagId(selectedFlag ? String(selectedFlag.id ?? selectedFlag.name ?? '') : null);

                const initialInstallments = buildInstallmentOptions(selectedFlag);

                if (initialInstallments.length > 0) {
                    setInstallments(initialInstallments[0].value);
                }
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar o plano contratado.');
                    setFlags([]);
                }
            } finally {
                setLoading(false);
            }
        }

        loadPlanData();

        return () => controller.abort();
    }, []);

    const flagOptions = useMemo(() => buildFlagOptions(flags), [flags]);
    const selectedFlag = useMemo(() => resolveSelectedFlag(flags, selectedFlagId), [flags, selectedFlagId]);
    const installmentOptions = useMemo(() => {
        const availableInstallments = buildInstallmentOptions(selectedFlag);

        return availableInstallments.length > 0 ? availableInstallments : [{ label: '1x', value: '1x' }];
    }, [selectedFlag]);
    const selectedRate = resolveRate(selectedFlag, installments);
    const parsedAmount = Number.isFinite(amount) ? amount : 0;
    const totalAmount = parsedAmount + parsedAmount * (selectedRate / 100);
    const installmentAmount = Number.parseInt(installments, 10) > 0 ? totalAmount / Number.parseInt(installments, 10) : 0;
    const installmentBreakdown = useMemo(() => {
        const installmentCount = Number.parseInt(installments, 10);

        if (totalAmount <= 0 || !Number.isFinite(installmentCount) || installmentCount <= 0) {
            return [];
        }

        return buildInstallmentBreakdown(totalAmount, installmentCount);
    }, [installments, totalAmount]);

    useEffect(() => {
        if (installmentOptions.length === 0) {
            return;
        }

        if (!installmentOptions.some((option) => option.value === installments)) {
            setInstallments(installmentOptions[0].value);
        }
    }, [installmentOptions, installments]);

    useEffect(() => {
        if (!selectedFlagId && selectedFlag) {
            setSelectedFlagId(String(selectedFlag.id ?? selectedFlag.name ?? ''));
        }
    }, [selectedFlag, selectedFlagId]);

    const selectedFlagLabel = selectedFlag ? formatFlagLabel(selectedFlag) : 'Bandeira';

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} lg={10}>
                <Card className="spa-table-card" title="Dados da simulação" extra={<CreditCardOutlined />}>
                    <Space direction="vertical" size={18} style={{ width: '100%' }}>
                        {loading ? (
                            <Spin tip="Carregando plano contratado" />
                        ) : error ? (
                            <Alert type="error" showIcon message="Falha ao carregar dados" description={error} />
                        ) : (
                            <>
                                <div className="spa-sim-summary">
                                    <Typography.Text type="secondary">Vendedor</Typography.Text>
                                    <Typography.Title level={5} style={{ margin: 0 }}>
                                        {sellerName}
                                    </Typography.Title>
                                </div>

                                <PaymentPlanSelector
                                    value={selectedFlagId}
                                    onChange={setSelectedFlagId}
                                    align="right"
                                    label="Bandeira"
                                    options={flagOptions}
                                    ariaLabel="Selecionar bandeira do cartão"
                                />

                                <PaymentAmountField
                                    value={amount}
                                    onChange={setAmount}
                                    formatter={formatAmountInput}
                                    parser={parseAmountInput}
                                    align="right"
                                />

                                <PaymentInstallmentSelector
                                    value={installments}
                                    onChange={setInstallments}
                                    align="right"
                                    options={installmentOptions}
                                />
                            </>
                        )}
                    </Space>
                </Card>
            </Col>

            <Col xs={24} lg={14}>
                <Card className="spa-table-card" title="Resultado da simulação" extra={<DollarOutlined />}>
                    <Space direction="vertical" size={18} style={{ width: '100%' }}>
                        {loading ? (
                            <Spin tip="Carregando resultado" />
                        ) : error ? (
                            <Alert type="error" showIcon message="Falha ao carregar dados" description={error} />
                        ) : flagOptions.length === 0 ? (
                            <Alert
                                type="warning"
                                showIcon
                                message="Nenhuma bandeira disponível"
                                description="O plano contratado não trouxe taxas de crédito para simulação."
                            />
                        ) : (
                            <>
                                <div className="spa-sim-result-header">
                                    <div className="spa-sim-result-title">
                                        <Typography.Text type="secondary">Plano contratado</Typography.Text>
                                        <Typography.Title level={4} style={{ margin: 0 }}>
                                            {planName}
                                        </Typography.Title>
                                    </div>

                                    <Space wrap className="spa-sim-result-tags">
                                        <Tag color="blue" className="spa-sim-result-tag">
                                            {selectedFlagLabel}
                                        </Tag>
                                        <Tag color="gold" className="spa-sim-result-tag">
                                            {installments}
                                        </Tag>
                                    </Space>
                                </div>

                                <Row gutter={[12, 12]} className="spa-sim-result-metrics">
                                    <Col xs={24} sm={12} xl={6}>
                                        <Card size="small" className="spa-sim-result-metric-card">
                                            <ResultMetric label="Valor da compra" value={formatCurrency(parsedAmount)} />
                                        </Card>
                                    </Col>
                                    <Col xs={24} sm={12} xl={6}>
                                        <Card size="small" className="spa-sim-result-metric-card">
                                            <ResultMetric label="Taxa aplicada" value={`${selectedRate.toFixed(2).replace('.', ',')}%`} />
                                        </Card>
                                    </Col>
                                    <Col xs={24} sm={12} xl={6}>
                                        <Card size="small" className="spa-sim-result-metric-card">
                                            <ResultMetric label="Parcela" value={formatCurrency(installmentAmount)} hint={installments} />
                                        </Card>
                                    </Col>
                                    <Col xs={24} sm={12} xl={6}>
                                        <Card size="small" className="spa-sim-result-metric-card">
                                            <ResultMetric label="Total com taxa" value={formatCurrency(totalAmount)} />
                                        </Card>
                                    </Col>
                                </Row>

                                <Divider orientation="left">Valor de cada parcela</Divider>

                                {installmentBreakdown.length === 0 ? (
                                    <Alert
                                        type="warning"
                                        showIcon
                                        message="Informe um valor maior que zero para visualizar a divisão das parcelas."
                                    />
                                ) : (
                                    <Row gutter={[12, 12]} className="spa-sim-installment-grid">
                                        {installmentBreakdown.map((item) => (
                                            <Col xs={24} sm={12} md={8} lg={6} key={item.number}>
                                                <Card size="small" className="spa-sim-installment-card">
                                                    <Space direction="vertical" size={6} style={{ width: '100%' }}>
                                                        <Tag color="gold" className="spa-sim-installment-tag">
                                                            {`${item.number}x`}
                                                        </Tag>
                                                        <Typography.Text className="spa-sim-installment-label">
                                                            Parcela {item.number}
                                                        </Typography.Text>
                                                        <Typography.Title level={4} className="spa-sim-installment-value">
                                                            {formatCurrency(item.amount)}
                                                        </Typography.Title>
                                                    </Space>
                                                </Card>
                                            </Col>
                                        ))}
                                    </Row>
                                )}
                            </>
                        )}
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

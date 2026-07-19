import { CreditCardOutlined, DollarOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, Radio, Row, Space, Spin, Tag, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import PaymentAmountField from '../../components/payment-simulator/PaymentAmountField';
import PaymentInstallmentSelector from '../../components/payment-simulator/PaymentInstallmentSelector';
import PaymentPlanSelector from '../../components/payment-simulator/PaymentPlanSelector';
import {
    buildFlagOptions,
    buildInstallmentOptions,
    formatFlagLabel,
    normalizeFlags,
    resolveRate,
    resolveSelectedFlag,
} from '../../components/payment-simulator/paymentSimulationConfig';

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

function resolvePixRate(flag) {
    const pixFees = flag?.fees?.pix;

    if (typeof pixFees === 'number' || typeof pixFees === 'string') {
        const parsedRate = Number(pixFees);

        return Number.isFinite(parsedRate) ? parsedRate : 0;
    }

    if (pixFees && typeof pixFees === 'object') {
        for (const value of Object.values(pixFees)) {
            const parsedRate = Number(value);

            if (Number.isFinite(parsedRate)) {
                return parsedRate;
            }
        }
    }

    return 0;
}

const payerOptions = [
    { label: 'Cliente', value: 'CLIENT' },
    { label: 'Vendedor', value: 'ESTABLISHMENT' },
];

const minimumCardInstallmentAmount = 5;
const minimumCardInstallmentCount = 2;

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
    const [planName, setPlanName] = useState('Plano contratado');
    const [planFlags, setPlanFlags] = useState([]);
    const [flags, setFlags] = useState([]);
    const [selectedFlagId, setSelectedFlagId] = useState(null);
    const [paymentMethod, setPaymentMethod] = useState('credit_card');
    const [interest, setInterest] = useState('ESTABLISHMENT');
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
                const contractedFlags = data.plan?.flags ?? [];
                const creditFlags = normalizeFlags(contractedFlags);
                const selectedFlag = creditFlags.find((flag) => flag.active) ?? creditFlags[0] ?? null;

                setPlanName(data.plan?.name ?? 'Plano contratado');
                setPlanFlags(contractedFlags);
                setFlags(creditFlags);
                setSelectedFlagId(selectedFlag ? String(selectedFlag.id ?? selectedFlag.name ?? '') : null);

                const initialInstallments = buildInstallmentOptions(selectedFlag);

                if (initialInstallments.length > 0) {
                    setInstallments(initialInstallments[0].value);
                }
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar o plano contratado.');
                    setPlanFlags([]);
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
    const bacenFlag = useMemo(
        () => planFlags.find((flag) => String(flag?.name ?? '').toUpperCase() === 'BACEN') ?? null,
        [planFlags],
    );
    const isPixMode = paymentMethod === 'pix';
    const selectedRate = isPixMode ? resolvePixRate(bacenFlag) : resolveRate(selectedFlag, installments);
    const parsedAmount = Number.isFinite(amount) ? amount : 0;
    const installmentCount = Number.parseInt(installments, 10);
    const taxAmount = parsedAmount * (selectedRate / 100);
    const chargeAmount = isPixMode
        ? parsedAmount
        : interest === 'CLIENT'
            ? parsedAmount + taxAmount
            : parsedAmount;
    const sellerNetAmount = isPixMode
        ? parsedAmount - taxAmount
        : interest === 'CLIENT'
            ? parsedAmount
            : parsedAmount - taxAmount;
    const installmentAmount = !isPixMode && installmentCount > 0 ? chargeAmount / installmentCount : 0;
    const filteredInstallmentOptions = useMemo(() => {
        if (isPixMode) {
            return [];
        }

        const validInstallments = installmentOptions.filter((option) => {
            const optionInstallments = Number.parseInt(option.value, 10);

            if (optionInstallments === 1) {
                return false;
            }

            return (
                optionInstallments >= minimumCardInstallmentCount
                && chargeAmount / optionInstallments >= minimumCardInstallmentAmount
            );
        });

        const oneInstallmentOption = installmentOptions.find((option) => option.value === '1x') ?? {
            label: '1x',
            value: '1x',
        };

        return [oneInstallmentOption, ...validInstallments];
    }, [chargeAmount, installmentOptions, isPixMode]);

    useEffect(() => {
        if (filteredInstallmentOptions.length === 0) {
            return;
        }

        if (!filteredInstallmentOptions.some((option) => option.value === installments)) {
            setInstallments(filteredInstallmentOptions[0].value);
        }
    }, [filteredInstallmentOptions, installments]);

    useEffect(() => {
        if (!selectedFlagId && selectedFlag) {
            setSelectedFlagId(String(selectedFlag.id ?? selectedFlag.name ?? ''));
        }
    }, [selectedFlag, selectedFlagId]);

    const selectedFlagLabel = selectedFlag ? formatFlagLabel(selectedFlag) : 'Bandeira';
    const modeTags = isPixMode
        ? [
            { color: 'blue', label: 'PIX' },
            { color: 'gold', label: `${selectedRate.toFixed(2).replace('.', ',')}%` },
        ]
        : [
            { color: 'blue', label: selectedFlagLabel },
            { color: 'gold', label: installments },
        ];

    const hasAvailableData = isPixMode ? bacenFlag !== null : flagOptions.length > 0;

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} lg={10}>
                <Card
                    className="spa-table-card"
                    title="Dados da simulação"
                    extra={
                        <Space size={6}>
                            <Button
                                type="text"
                                aria-label="Simular recebimento por PIX"
                                className={`spa-sim-method-toggle ${isPixMode ? 'spa-sim-method-toggle-active' : ''}`}
                                onClick={() => setPaymentMethod('pix')}
                            >
                                <img src="/img/payment/logo-pix.png" alt="" aria-hidden="true" className="spa-sim-header-pix-icon" />
                            </Button>
                            <Button
                                type="text"
                                aria-label="Simular recebimento por cartão"
                                className={`spa-sim-method-toggle ${!isPixMode ? 'spa-sim-method-toggle-active' : ''}`}
                                onClick={() => setPaymentMethod('credit_card')}
                            >
                                <CreditCardOutlined aria-hidden="true" />
                            </Button>
                        </Space>
                    }
                >
                    <Space direction="vertical" size={18} style={{ width: '100%' }}>
                        {loading ? (
                            <Spin tip="Carregando plano contratado" />
                        ) : error ? (
                            <Alert type="error" showIcon message="Falha ao carregar dados" description={error} />
                        ) : (
                            <>
                                {!isPixMode ? (
                                    <PaymentPlanSelector
                                        value={selectedFlagId}
                                        onChange={setSelectedFlagId}
                                        align="right"
                                        label="Bandeira"
                                        options={flagOptions}
                                        ariaLabel="Selecionar bandeira do cartão"
                                    />
                                ) : null}

                                <PaymentAmountField
                                    value={amount}
                                    onChange={setAmount}
                                    formatter={formatAmountInput}
                                    parser={parseAmountInput}
                                    align="right"
                                />

                                {!isPixMode ? (
                                    <PaymentInstallmentSelector
                                        value={installments}
                                        onChange={setInstallments}
                                        align="right"
                                        options={filteredInstallmentOptions}
                                    />
                                ) : null}
                            </>
                        )}
                    </Space>
                </Card>
            </Col>

            <Col xs={24} lg={14}>
                <Card className="spa-table-card" title={planName} extra={<DollarOutlined />}>
                    <Space direction="vertical" size={18} style={{ width: '100%' }}>
                        {loading ? (
                            <Spin tip="Carregando resultado" />
                        ) : error ? (
                            <Alert type="error" showIcon message="Falha ao carregar dados" description={error} />
                        ) : !hasAvailableData ? (
                            <Alert
                                type="warning"
                                showIcon
                                message={isPixMode ? 'Nenhuma taxa PIX disponível' : 'Nenhuma bandeira disponível'}
                                description={
                                    isPixMode
                                        ? 'O plano contratado não trouxe taxa PIX para simulação.'
                                        : 'O plano contratado não trouxe taxas de crédito para simulação.'
                                }
                            />
                        ) : (
                            <>
                                <div className="spa-sim-result-header">
                                    <div className="spa-sim-result-title">
                                        {!isPixMode ? (
                                            <Space align="center" size={12} wrap>
                                                <Typography.Text type="secondary">Quem paga a taxa:</Typography.Text>
                                                <Radio.Group
                                                    value={interest}
                                                    onChange={(event) => setInterest(event.target.value)}
                                                    optionType="button"
                                                    buttonStyle="solid"
                                                    size="small"
                                                >
                                                    {payerOptions.map((option) => (
                                                        <Radio.Button key={option.value} value={option.value}>
                                                            {option.label}
                                                        </Radio.Button>
                                                    ))}
                                                </Radio.Group>
                                            </Space>
                                        ) : (
                                            <Typography.Text type="secondary">Simulação de recebimento por PIX</Typography.Text>
                                        )}
                                    </div>

                                    <Space wrap className="spa-sim-result-tags">
                                        {modeTags.map((tag) => (
                                            <Tag key={tag.label} color={tag.color} className="spa-sim-result-tag">
                                                {tag.label}
                                            </Tag>
                                        ))}
                                    </Space>
                                </div>

                                <Row gutter={[12, 12]} className="spa-sim-result-metrics">
                                    <Col xs={24} sm={12} xl={isPixMode ? 8 : 6}>
                                        <Card size="small" className="spa-sim-result-metric-card">
                                            <ResultMetric label="Valor da compra" value={formatCurrency(parsedAmount)} />
                                        </Card>
                                    </Col>
                                    <Col xs={24} sm={12} xl={isPixMode ? 8 : 6}>
                                        <Card size="small" className="spa-sim-result-metric-card">
                                            <ResultMetric label="Taxa aplicada" value={`${selectedRate.toFixed(2).replace('.', ',')}%`} />
                                        </Card>
                                    </Col>
                                    {!isPixMode ? (
                                        <Col xs={24} sm={12} xl={6}>
                                            <Card size="small" className="spa-sim-result-metric-card">
                                                <ResultMetric label="Parcela" value={formatCurrency(installmentAmount)} hint={installments} />
                                            </Card>
                                        </Col>
                                    ) : null}
                                    <Col xs={24} sm={12} xl={isPixMode ? 8 : 6}>
                                        <Card size="small" className="spa-sim-result-metric-card">
                                            <ResultMetric label="Valor a receber" value={formatCurrency(sellerNetAmount)} />
                                        </Card>
                                    </Col>
                                </Row>
                            </>
                        )}
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

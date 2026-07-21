import { CreditCardOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Checkbox, Col, Empty, Row, Select, Space, Spin, Table, Tag, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import PaymentAmountField from '../../components/payment-simulator/PaymentAmountField';
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

function formatRate(value) {
    return `${value.toFixed(2).replace('.', ',')}%`;
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

const minimumCardInstallmentAmount = 5;
const minimumCardInstallmentCount = 2;

function SimulationField({ label, note, children }) {
    return (
        <div className="spa-sim-control">
            <Typography.Text strong className="spa-sim-control-label">
                {label}
            </Typography.Text>

            <div className="spa-sim-control-input">{children}</div>

            {note ? (
                <Typography.Text type="secondary" className="spa-sim-control-note">
                    {note}
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
                    throw new Error('Nao foi possivel carregar o plano contratado.');
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
    const hasAvailableData = isPixMode ? bacenFlag !== null : flagOptions.length > 0;

    const simulationRows = useMemo(() => {
        if (!hasAvailableData) {
            return [];
        }

        if (isPixMode) {
            return [
                {
                    key: 'pix',
                    paymentMethod: 'PIX',
                    customerPays: formatCurrency(parsedAmount),
                    total: formatCurrency(parsedAmount),
                    youReceive: formatCurrency(sellerNetAmount),
                },
            ];
        }

        return filteredInstallmentOptions.map((option) => {
            const optionInstallmentCount = Number.parseInt(option.value, 10);
            const optionRate = resolveRate(selectedFlag, option.value);
            const optionTaxAmount = parsedAmount * (optionRate / 100);
            const customerCharge = interest === 'CLIENT' ? parsedAmount + optionTaxAmount : parsedAmount;
            const sellerReceive = interest === 'CLIENT' ? parsedAmount : parsedAmount - optionTaxAmount;
            const installmentValue = optionInstallmentCount > 0 ? customerCharge / optionInstallmentCount : 0;

            return {
                key: option.value,
                paymentMethod: `CRÉDITO ${option.label}`,
                customerPays: `${optionInstallmentCount} x ${formatCurrency(installmentValue)}`,
                total: formatCurrency(customerCharge),
                youReceive: formatCurrency(sellerReceive),
            };
        });
    }, [filteredInstallmentOptions, hasAvailableData, interest, isPixMode, parsedAmount, sellerNetAmount, selectedFlag]);

    const simulationColumns = useMemo(
        () => [
            {
                title: 'Forma de pagamento',
                dataIndex: 'paymentMethod',
                key: 'paymentMethod',
            },
            {
                title: 'Cliente pagará',
                dataIndex: 'customerPays',
                key: 'customerPays',
            },
            {
                title: 'Total',
                dataIndex: 'total',
                key: 'total',
            },
            {
                title: 'Você receberá',
                dataIndex: 'youReceive',
                key: 'youReceive',
                render: (value) => (
                    <Typography.Text strong style={{ color: '#58c36b' }}>
                        {value}
                    </Typography.Text>
                ),
            },
        ],
        [],
    );

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
            { color: 'gold', label: formatRate(selectedRate) },
        ]
        : [
            { color: 'blue', label: selectedFlagLabel },
            { color: 'gold', label: installments },
        ];

    return (
        <div className="spa-sim-page">
            <Space size={8} className="spa-sim-method-group">
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

            <Card className="spa-table-card spa-sim-toolbar-card" bordered={false}>
                {loading ? (
                    <Spin tip="Carregando plano contratado" />
                ) : error ? (
                    <Alert type="error" showIcon message="Falha ao carregar dados" description={error} />
                ) : (
                    <Space direction="vertical" size={18} style={{ width: '100%' }}>
                        <Row gutter={[16, 16]} className="spa-sim-toolbar-grid">
                            {!isPixMode ? (
                                <Col xs={24} lg={12}>
                                    <SimulationField label="Bandeira">
                                        <Select
                                            value={selectedFlagId}
                                            onChange={setSelectedFlagId}
                                            options={flagOptions}
                                            size="large"
                                            placeholder="Selecione..."
                                            aria-label="Selecionar bandeira do cartão"
                                        />
                                    </SimulationField>
                                </Col>
                            ) : null}

                            <Col xs={24} lg={isPixMode ? 12 : 12}>
                                <SimulationField label="Valor">
                                    <PaymentAmountField value={amount} onChange={setAmount} />
                                </SimulationField>
                            </Col>
                        </Row>

                        {!isPixMode ? (
                            <div className="spa-sim-interest-row">
                                <Checkbox
                                    checked={interest === 'CLIENT'}
                                    onChange={(event) => setInterest(event.target.checked ? 'CLIENT' : 'ESTABLISHMENT')}
                                >
                                    Repassar os juros para o cliente
                                </Checkbox>
                            </div>
                        ) : null}
                    </Space>
                )}
            </Card>

            <Card
                className="spa-table-card spa-sim-table-card"
                title={planName}
                extra={
                    <Space wrap className="spa-sim-result-tags">
                        {modeTags.map((tag) => (
                            <Tag key={tag.label} color={tag.color} className="spa-sim-result-tag">
                                {tag.label}
                            </Tag>
                        ))}
                    </Space>
                }
                bordered={false}
            >
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
                    <Space direction="vertical" size={16} style={{ width: '100%' }}>
                        <Typography.Text type="secondary" className="spa-sim-table-note">
                            {isPixMode
                                ? 'Recebimento via PIX com base na taxa BACEN do plano.'
                                : 'A tabela abaixo mostra o valor que o cliente pagará em cada quantidade de parcelas disponível.'}
                        </Typography.Text>

                        <Table
                            rowKey="key"
                            columns={simulationColumns}
                            dataSource={simulationRows}
                            pagination={false}
                            className="spa-table spa-sim-table"
                            rowClassName={(record) => (!isPixMode && record.key === installments ? 'spa-table-row-selected' : '')}
                            locale={{
                                emptyText: <Empty description="Sem opções de simulação para o momento." />,
                            }}
                            scroll={{ x: 720 }}
                        />
                    </Space>
                )}
            </Card>
        </div>
    );
}

import {
    BankOutlined,
    CheckCircleFilled,
    ClockCircleFilled,
    CreditCardOutlined,
    DownOutlined,
    HomeOutlined,
    MinusCircleFilled,
    ThunderboltOutlined,
    ReloadOutlined,
    RiseOutlined,
    WalletOutlined,
} from '@ant-design/icons';
import { Alert, Button, Card, Col, Row, Select, Space, Spin, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

const monthOptions = [
    { value: 1, label: 'Janeiro' },
    { value: 2, label: 'Fevereiro' },
    { value: 3, label: 'Março' },
    { value: 4, label: 'Abril' },
    { value: 5, label: 'Maio' },
    { value: 6, label: 'Junho' },
    { value: 7, label: 'Julho' },
    { value: 8, label: 'Agosto' },
    { value: 9, label: 'Setembro' },
    { value: 10, label: 'Outubro' },
    { value: 11, label: 'Novembro' },
    { value: 12, label: 'Dezembro' },
];

const currentDate = new Date();

const defaultPayload = {
    user: {
        name: 'Usuário',
        nivel_label: '',
    },
    period: {
        month: currentDate.getMonth() + 1,
        year: currentDate.getFullYear(),
        label: '',
    },
    overview_cards: [],
    distribution_sections: [],
    status_sections: [],
    summary: {
        total_establishments: 0,
        active_establishments: 0,
        blocked_establishments: 0,
        total_transactions: 0,
        pending_transactions: 0,
        today_transactions: 0,
        total_revenue: 'R$ 0,00',
    },
    rows: [],
    selected: null,
    recent_transactions: [],
    actions: [],
};

const toneClasses = {
    blue: 'tone-blue',
    cyan: 'tone-cyan',
    amber: 'tone-amber',
    slate: 'tone-slate',
    green: 'tone-green',
    dark: 'tone-dark',
    success: 'tone-success',
    danger: 'tone-danger',
    warning: 'tone-warning',
};

const iconByTone = {
    blue: <WalletOutlined />,
    cyan: <ClockCircleFilled />,
    amber: <MinusCircleFilled />,
    slate: <RiseOutlined />,
    green: <CreditCardOutlined />,
    dark: <BankOutlined />,
    success: <CheckCircleFilled />,
    danger: <MinusCircleFilled />,
    warning: <ReloadOutlined />,
};

function MetricTile({ value, label, tone, icon }) {
    return (
        <div className="spa-metric-tile">
            <div className="spa-metric-copy">
                <Typography.Title level={4} className="spa-metric-value">
                    {value}
                </Typography.Title>
                <Typography.Text className="spa-metric-label">
                    <span className="spa-metric-label-icon">i</span>
                    {label}
                </Typography.Text>
            </div>
            <div className={`spa-metric-icon ${toneClasses[tone] ?? toneClasses.blue}`}>
                {icon}
            </div>
        </div>
    );
}

function SectionCard({ title, collapsed, onToggle, children }) {
    return (
        <Card
            className="spa-dashboard-section"
            title={<Typography.Title level={4} className="spa-dashboard-section-title">{title}</Typography.Title>}
            extra={
                <Button
                    type="text"
                    icon={<DownOutlined rotate={collapsed ? 180 : 0} />}
                    onClick={onToggle}
                    className="spa-section-toggle"
                />
            }
        >
            {!collapsed ? children : null}
        </Card>
    );
}

export default function HomePage() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState(defaultPayload);
    const [month, setMonth] = useState(currentDate.getMonth() + 1);
    const [year, setYear] = useState(currentDate.getFullYear());
    const [refreshNonce, setRefreshNonce] = useState(0);
    const [overviewCollapsed, setOverviewCollapsed] = useState(false);
    const [distributionCollapsed, setDistributionCollapsed] = useState(false);
    const [statusCollapsed, setStatusCollapsed] = useState(false);
    const showBankAccountLink = payload.user?.nivel_acesso === 'vendedor';

    useEffect(() => {
        const controller = new AbortController();

        async function loadHome() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch(`/api/spa/dashboard?mes=${month}&ano=${year}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar o painel.');
                }

                const data = await response.json();
                setPayload((current) => ({
                    ...current,
                    ...data,
                    period: data.period ?? current.period,
                    overview_cards: data.overview_cards ?? [],
                    distribution_sections: data.distribution_sections ?? [],
                    status_sections: data.status_sections ?? [],
                    summary: data.summary ?? current.summary,
                    user: data.user ?? current.user,
                    rows: data.rows ?? [],
                    selected: data.selected ?? data.rows?.[0] ?? null,
                    recent_transactions: data.recent_transactions ?? [],
                    actions: data.actions ?? [],
                }));
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar o painel.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadHome();

        return () => controller.abort();
    }, [month, year, refreshNonce]);

    const years = useMemo(() => {
        const baseYear = currentDate.getFullYear();

        return Array.from({ length: 4 }, (_, index) => baseYear - index);
    }, []);

    return (
        <Space direction="vertical" size={20} className="spa-home-dashboard">
            <div className="spa-dashboard-toolbar">
                <div className="spa-dashboard-toolbar-breadcrumb">
                    <HomeOutlined />
                    <Typography.Text className="spa-dashboard-toolbar-link">Dashboard</Typography.Text>
                    <span className="spa-dashboard-toolbar-separator">/</span>
                    <Typography.Text className="spa-dashboard-toolbar-current">
                        {payload.user?.nivel_label || 'Administração'}
                    </Typography.Text>
                </div>

                {showBankAccountLink ? (
                    <Link to="/perfil" className="spa-dashboard-toolbar-center">
                        Acessar Conta Bancária
                    </Link>
                ) : (
                    <span />
                )}

                <div className="spa-dashboard-toolbar-filters">
                    <Select
                        value={month}
                        options={monthOptions}
                        onChange={setMonth}
                        className="spa-toolbar-select"
                    />
                    <Select
                        value={year}
                        options={years.map((value) => ({ value, label: String(value) }))}
                        onChange={setYear}
                        className="spa-toolbar-select"
                    />
                </div>
            </div>

            {error ? <Alert type="error" showIcon message="Falha ao carregar o painel" description={error} /> : null}

            <SectionCard
                title="Visão geral de transações"
                collapsed={overviewCollapsed}
                onToggle={() => setOverviewCollapsed((current) => !current)}
            >
                {loading ? (
                    <div className="spa-loading-shell">
                        <Spin size="large" />
                    </div>
                ) : (
                    <Row gutter={[16, 16]}>
                        {payload.overview_cards.map((card) => (
                            <Col xs={24} md={12} xl={8} key={card.key}>
                                <MetricTile
                                    value={card.value}
                                    label={card.label}
                                    tone={card.tone}
                                    icon={iconByTone[card.tone] ?? iconByTone.blue}
                                />
                            </Col>
                        ))}
                    </Row>
                )}
            </SectionCard>

            <SectionCard
                title="Distribuição por meio de pagamento"
                collapsed={distributionCollapsed}
                onToggle={() => setDistributionCollapsed((current) => !current)}
            >
                <Space direction="vertical" size={16} className="spa-section-stack">
                    {payload.distribution_sections.map((section) => (
                        <div key={section.key} className="spa-section-group">
                            <Row gutter={[16, 16]}>
                                {section.cards.map((card) => (
                                    <Col xs={24} md={12} xl={8} key={`${section.key}-${card.kind}`}>
                                        <MetricTile
                                            value={card.value}
                                            label={card.label ?? section.label}
                                            tone={card.tone ?? section.tone}
                                            icon={
                                                section.icon === 'bank' ? <BankOutlined /> :
                                                section.icon === 'bolt' ? <ThunderboltOutlined /> :
                                                section.icon === 'document' ? <CreditCardOutlined /> :
                                                <CreditCardOutlined />
                                        }
                                        />
                                    </Col>
                                ))}
                            </Row>
                        </div>
                    ))}
                </Space>
            </SectionCard>

            <SectionCard
                title="Visão de status de pagamentos"
                collapsed={statusCollapsed}
                onToggle={() => setStatusCollapsed((current) => !current)}
            >
                <Space direction="vertical" size={16} className="spa-section-stack">
                    {payload.status_sections.map((section) => (
                        <div key={section.key} className="spa-section-group">
                            <Row gutter={[16, 16]}>
                                {section.cards.map((card) => (
                                    <Col xs={24} md={12} xl={8} key={`${section.key}-${card.kind}`}>
                                        <MetricTile
                                            value={card.value}
                                            label={card.label ?? section.label}
                                            tone={card.tone ?? section.tone}
                                            icon={
                                                (card.tone ?? section.tone) === 'success' ? <CheckCircleFilled /> :
                                                (card.tone ?? section.tone) === 'danger' ? <MinusCircleFilled /> :
                                                <ReloadOutlined />
                                            }
                                        />
                                    </Col>
                                ))}
                            </Row>
                        </div>
                    ))}
                </Space>
            </SectionCard>

            <Button
                type="primary"
                shape="circle"
                icon={<ReloadOutlined />}
                className="spa-fab"
                onClick={() => setRefreshNonce((current) => current + 1)}
            />
        </Space>
    );
}

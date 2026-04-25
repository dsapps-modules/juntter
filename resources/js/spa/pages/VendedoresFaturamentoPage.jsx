import { ReloadOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, Divider, Empty, InputNumber, Row, Select, Space, Skeleton, Statistic, Table, Tag, Typography } from 'antd';
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

const defaultPayload = {
    summary: {
        total_registros: 0,
        total_bruto: 0,
        total_taxas: 0,
        total_liquido: 0,
        transacoes: 0,
    },
    rows: [],
    period: {
        mes: new Date().getMonth() + 1,
        ano: new Date().getFullYear(),
        label: '',
    },
    actions: [],
};

function formatCurrency(valueInCents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format((Number(valueInCents) || 0) / 100);
}

export default function VendedoresFaturamentoPage() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState(defaultPayload);
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth() + 1);
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
    const [selectedRow, setSelectedRow] = useState(null);

    useEffect(() => {
        const controller = new AbortController();

        async function loadOverview() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch(`/api/spa/vendedores/faturamento?mes=${selectedMonth}&ano=${selectedYear}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar o faturamento.');
                }

                const data = await response.json();
                setPayload((current) => ({
                    ...current,
                    ...data,
                    summary: data.summary ?? current.summary,
                    rows: data.rows ?? [],
                    period: data.period ?? current.period,
                    actions: data.actions ?? [],
                }));
                setSelectedRow(data.rows?.[0] ?? null);
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar o faturamento.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadOverview();

        return () => controller.abort();
    }, [selectedMonth, selectedYear]);

    const columns = [
        {
            title: '#',
            render: (_, __, index) => `${index + 1}º`,
        },
        {
            title: 'Vendedor',
            dataIndex: 'nome',
            render: (value) => <Typography.Text strong>{value}</Typography.Text>,
        },
        {
            title: 'Estabelecimento ID',
            dataIndex: 'estabelecimento_id',
        },
        {
            title: 'Qtd. transações',
            dataIndex: 'qtd',
        },
        {
            title: 'Total bruto',
            dataIndex: 'total_bruto',
            render: (value) => formatCurrency(value),
        },
        {
            title: 'Taxas',
            dataIndex: 'total_taxas',
            render: (value) => <Tag color="volcano">{formatCurrency(value)}</Tag>,
        },
        {
            title: 'Total líquido',
            dataIndex: 'total_liquido',
            render: (value) => <Tag color="green">{formatCurrency(value)}</Tag>,
        },
    ];

    const quickStats = useMemo(() => {
        return [
            { title: 'Registros', value: payload.summary.total_registros },
            { title: 'Transações', value: payload.summary.transacoes },
            { title: 'Bruto', value: formatCurrency(payload.summary.total_bruto) },
            { title: 'Líquido', value: formatCurrency(payload.summary.total_liquido) },
        ];
    }, [payload.summary]);

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} xl={16}>
                <Row gutter={[20, 20]}>
                    <Col span={24}>
                        <Card className="spa-hero-card">
                            <Space direction="vertical" size={18} className="spa-hero-stack">
                                <div>
                                    <Typography.Text className="spa-brand-kicker">Vendedores</Typography.Text>
                                    <Typography.Title level={2} className="spa-hero-title">
                                        Faturamento por loja
                                    </Typography.Title>
                                    <Typography.Paragraph className="spa-hero-description">
                                        Acompanhe volume bruto, taxas e líquido por estabelecimento no período selecionado.
                                    </Typography.Paragraph>
                                </div>

                                {error ? <Alert type="error" showIcon message={error} /> : null}

                                <Row gutter={16} style={{ width: '100%' }}>
                                    {quickStats.map((item) => (
                                        <Col xs={24} md={6} key={item.title}>
                                            <Statistic title={item.title} value={item.value} />
                                        </Col>
                                    ))}
                                </Row>

                                <Space wrap>
                                    <Select
                                        value={selectedMonth}
                                        onChange={setSelectedMonth}
                                        options={monthOptions}
                                        style={{ minWidth: 180 }}
                                        size="large"
                                    />
                                    <InputNumber
                                        value={selectedYear}
                                        onChange={(value) => setSelectedYear(Number(value) || new Date().getFullYear())}
                                        style={{ width: 140 }}
                                        size="large"
                                    />
                                    <Button
                                        icon={<ReloadOutlined />}
                                        onClick={() => {
                                            setSelectedMonth(new Date().getMonth() + 1);
                                            setSelectedYear(new Date().getFullYear());
                                        }}
                                    >
                                        Período atual
                                    </Button>
                                </Space>
                            </Space>
                        </Card>
                    </Col>

                    <Col span={24}>
                        <Card className="spa-table-card" title={`Ranking ${payload.period.label || ''}`}>
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 6 }} />
                            ) : payload.rows.length === 0 ? (
                                <Empty description="Nenhum registro encontrado para este período." />
                            ) : (
                                <Table
                                    rowKey={(record) => `${record.estabelecimento_id}-${record.nome}`}
                                    columns={columns}
                                    dataSource={payload.rows}
                                    pagination={false}
                                    className="spa-table"
                                    onRow={(record) => ({
                                        onClick: () => setSelectedRow(record),
                                    })}
                                    rowClassName={(record) =>
                                        record.estabelecimento_id === selectedRow?.estabelecimento_id ? 'spa-table-row-selected' : ''
                                    }
                                />
                            )}
                        </Card>
                    </Col>
                </Row>
            </Col>

            <Col xs={24} xl={8}>
                <Card className="spa-quick-view-card" title={selectedRow ? `Quick View: ${selectedRow.nome}` : 'Quick View'}>
                    {!selectedRow ? (
                        <Empty description="Selecione uma linha para ver os detalhes" />
                    ) : (
                        <>
                            <Space wrap>
                                <Tag color="gold">Período {payload.period.label}</Tag>
                                <Tag color="green">{selectedRow.qtd} transações</Tag>
                            </Space>

                            <Divider />

                            <Space direction="vertical" size={10} className="spa-detail-stack">
                                <Typography.Text strong>{selectedRow.nome}</Typography.Text>
                                <Typography.Text type="secondary">Estabelecimento {selectedRow.estabelecimento_id}</Typography.Text>
                                <Typography.Text type="secondary">Bruto {formatCurrency(selectedRow.total_bruto)}</Typography.Text>
                                <Typography.Text type="secondary">Taxas {formatCurrency(selectedRow.total_taxas)}</Typography.Text>
                                <Typography.Text type="secondary">Líquido {formatCurrency(selectedRow.total_liquido)}</Typography.Text>
                            </Space>

                            <Divider />

                            <Typography.Title level={4} className="spa-section-title">
                                Ações
                            </Typography.Title>

                            <Space direction="vertical" size={12} style={{ width: '100%' }}>
                                <Link to="/vendedores/acesso">Gerenciar acessos</Link>
                                <Link to="/vendedores">Voltar para visão geral</Link>
                            </Space>
                        </>
                    )}
                </Card>

                <Card className="spa-quick-view-card" title="Resumo do período" style={{ marginTop: 20 }}>
                    <Space direction="vertical" size={12} className="spa-detail-stack">
                        <Typography.Text type="secondary">Líquido consolidado</Typography.Text>
                        <Typography.Title level={3} className="spa-section-title">
                            {formatCurrency(payload.summary.total_liquido)}
                        </Typography.Title>
                        <Typography.Text type="secondary">Bruto consolidado {formatCurrency(payload.summary.total_bruto)}</Typography.Text>
                        <Typography.Text type="secondary">Taxas {formatCurrency(payload.summary.total_taxas)}</Typography.Text>
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

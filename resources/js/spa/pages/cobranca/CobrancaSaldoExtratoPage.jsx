import {
    BankOutlined,
    CalendarOutlined,
    LineChartOutlined,
    SwapOutlined,
    WalletOutlined,
} from '@ant-design/icons';
import { Card, Col, Empty, Row, Select, Space, Statistic, Table, Tag, Typography } from 'antd';
import { useState } from 'react';

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

function getMonthLabel(month) {
    return monthOptions.find((option) => option.value === month)?.label ?? 'Janeiro';
}

function getYearOptions() {
    const currentYear = new Date().getFullYear();

    return Array.from({ length: 5 }, (_, index) => currentYear - 2 + index);
}

const tableColumns = [
    {
        title: 'Data',
        dataIndex: 'date',
        width: 140,
    },
    {
        title: 'Descrição',
        dataIndex: 'description',
        render: (value) => <Typography.Text strong>{value}</Typography.Text>,
    },
    {
        title: 'Categoria',
        dataIndex: 'category',
        width: 180,
        render: (value) => <Tag color="gold">{value}</Tag>,
    },
    {
        title: 'Tipo',
        dataIndex: 'type',
        width: 140,
        render: (value) => <Tag color={value === 'Crédito' ? 'green' : 'volcano'}>{value}</Tag>,
    },
    {
        title: 'Valor',
        dataIndex: 'amount',
        width: 160,
        align: 'right',
        render: (value) => <Typography.Text strong>{value}</Typography.Text>,
    },
];

const accountSummary = [
    {
        title: 'Saldo atual',
        value: 'R$ 0,00',
        description: 'Disponível para uso imediato',
        icon: <WalletOutlined />,
    },
    {
        title: 'Lançamentos futuros',
        value: 'R$ 0,00',
        description: 'Previsão de créditos e débitos',
        icon: <SwapOutlined />,
    },
    {
        title: 'Saldo projetado',
        value: 'R$ 0,00',
        description: 'Cenário considerando os próximos lançamentos',
        icon: <LineChartOutlined />,
    },
    {
        title: 'Conta principal',
        value: 'Conta operacional',
        description: 'Perfil operacional da cobrança',
        icon: <BankOutlined />,
    },
];

const futureEntries = [
    {
        label: 'Boletos a liquidar',
        amount: 'R$ 0,00',
        tone: 'gold',
    },
];

export default function CobrancaSaldoExtratoPage() {
    const now = new Date();
    const [selectedMonth, setSelectedMonth] = useState(now.getMonth() + 1);
    const [selectedYear, setSelectedYear] = useState(now.getFullYear());

    const periodLabel = `${getMonthLabel(selectedMonth)} de ${selectedYear}`;
    const yearOptions = getYearOptions();

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} xl={16}>
                <Card
                    className="spa-table-card spa-saldoextrato-table-card"
                    title="Extrato do período"
                    extra={
                        <Space wrap size={12}>
                            <CalendarOutlined />
                            <Select
                                value={selectedMonth}
                                onChange={setSelectedMonth}
                                options={monthOptions}
                                style={{ minWidth: 160 }}
                                aria-label="Selecionar mês"
                            />
                            <Select
                                value={selectedYear}
                                onChange={setSelectedYear}
                                options={yearOptions.map((year) => ({ value: year, label: String(year) }))}
                                style={{ width: 120 }}
                                aria-label="Selecionar ano"
                            />
                        </Space>
                    }
                >
                    <Space direction="vertical" size={16} style={{ width: '100%' }}>
                        <Typography.Text type="secondary">
                            Visualização placeholder da conta corrente para {periodLabel}.
                        </Typography.Text>

                        <Table
                            rowKey="id"
                            columns={tableColumns}
                            dataSource={[]}
                            pagination={false}
                            className="spa-table spa-saldoextrato-table"
                            locale={{
                                emptyText: <Empty description="Sem lançamentos para o período selecionado." />,
                            }}
                        />
                    </Space>
                </Card>
            </Col>

            <Col xs={24} xl={8}>
                <Card className="spa-quick-view-card spa-saldoextrato-sidebar-card" title="Resumo financeiro">
                    <Space direction="vertical" size={16} style={{ width: '100%' }}>
                        <Row gutter={[12, 12]}>
                            {accountSummary.map((item) => (
                                <Col xs={24} sm={12} key={item.title}>
                                    <Card size="small" bordered={false} className="spa-saldoextrato-mini-stat-card">
                                        <Space direction="vertical" size={4} style={{ width: '100%' }}>
                                            <Space size={8} align="center">
                                                {item.icon}
                                                <Typography.Text type="secondary">{item.title}</Typography.Text>
                                            </Space>
                                            <Statistic value={item.value} valueStyle={{ fontSize: 24 }} />
                                            <Typography.Text type="secondary">{item.description}</Typography.Text>
                                        </Space>
                                    </Card>
                                </Col>
                            ))}
                        </Row>

                        <Card size="small" bordered={false}>
                            <Space direction="vertical" size={12} style={{ width: '100%' }}>
                                {futureEntries.map((item) => (
                                    <Space key={item.label} align="center" style={{ justifyContent: 'space-between', width: '100%' }}>
                                        <Space direction="vertical" size={0}>
                                            <Typography.Text strong>{item.label}</Typography.Text>
                                            <Typography.Text type="secondary">Movimentação prevista</Typography.Text>
                                        </Space>
                                        <Tag color={item.tone}>{item.amount}</Tag>
                                    </Space>
                                ))}
                            </Space>
                        </Card>

                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

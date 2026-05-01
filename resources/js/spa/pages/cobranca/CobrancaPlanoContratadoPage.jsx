import { CheckCircleFilled, ClockCircleOutlined, CrownOutlined, GlobalOutlined, HomeOutlined } from '@ant-design/icons';
import { Alert, Breadcrumb, Button, Card, Col, Descriptions, Empty, Row, Skeleton, Space, Tag, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

const defaultPayload = {
    seller_name: 'Vendedor',
    establishment: null,
    plan: null,
    message: '',
};

function formatText(value, fallback = 'N/A') {
    return value === null || value === undefined || value === '' ? fallback : value;
}

function formatPlanTagColor(plan) {
    if (!plan) {
        return 'default';
    }

    return plan.active ? 'green' : 'gold';
}

function SummaryTile({ label, value, icon, color }) {
    return (
        <div className="spa-metric-tile">
            <div className="spa-metric-copy">
                <Typography.Title level={4} className="spa-metric-value">
                    {value}
                </Typography.Title>
                <Typography.Text className="spa-metric-label">
                    <span className="spa-metric-label-icon">{icon}</span>
                    {label}
                </Typography.Text>
            </div>
            <div className={`spa-metric-icon tone-${color}`}>
                <CrownOutlined />
            </div>
        </div>
    );
}

export default function CobrancaPlanoContratadoPage() {
    const { planoId } = useParams();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState(defaultPayload);

    useEffect(() => {
        const controller = new AbortController();

        async function loadPlan() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch(`/api/spa/cobranca/planos${planoId ? `/${planoId}` : ''}`, {
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
                setPayload({
                    seller_name: data.seller_name ?? defaultPayload.seller_name,
                    establishment: data.establishment ?? null,
                    plan: data.plan ?? null,
                    message: data.message ?? '',
                });
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar o plano contratado.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadPlan();

        return () => controller.abort();
    }, [planoId]);

    const plan = payload.plan;

    const planHighlights = useMemo(() => {
        if (!plan) {
            return [];
        }

        return [
            {
                label: 'Tipo',
                value: formatText(plan.type),
                icon: 'Tp',
            },
            {
                label: 'Modalidade',
                value: formatText(plan.modality_label ?? plan.modality),
                icon: 'Md',
            },
            {
                label: 'Antecipação',
                value: formatText(plan.allow_anticipation_label),
                icon: 'An',
            },
            {
                label: 'Status',
                value: formatText(plan.status_label),
                icon: 'St',
            },
        ];
    }, [plan]);

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card className="spa-toolbar-card">
                    <Space direction="vertical" size={14} style={{ width: '100%' }}>
                        <Breadcrumb
                            items={[
                                { title: <Link to="/home">Dashboard</Link> },
                                { title: <Link to="/cobranca">Cobrança</Link> },
                                { title: 'Plano contratado' },
                            ]}
                        />

                        <Row gutter={[16, 16]} align="middle" justify="space-between">
                            <Col xs={24} md={16}>
                                <Typography.Text className="spa-brand-kicker">Plano contratado</Typography.Text>
                                <Typography.Title level={2} style={{ margin: '6px 0 0' }}>
                                    {plan ? plan.name : 'Nenhum plano localizado'}
                                </Typography.Title>
                                <Typography.Paragraph type="secondary" style={{ marginBottom: 0 }}>
                                    {plan
                                        ? 'Resumo do plano comercial ativo da empresa na interface SPA.'
                                        : 'A página exibe o estado atual do plano comercial contratado pela sua empresa.'}
                                </Typography.Paragraph>
                            </Col>

                            <Col xs={24} md={8}>
                                <Space wrap style={{ width: '100%', justifyContent: 'flex-end' }}>
                                    <Button
                                        type="primary"
                                        icon={<HomeOutlined />}
                                        onClick={() => navigate('/home')}
                                        aria-label="Ir para a home"
                                    />
                                </Space>
                            </Col>
                        </Row>
                    </Space>
                </Card>
            </Col>

            <Col xs={24}>
                {loading ? (
                    <Card className="spa-table-card">
                        <Skeleton active paragraph={{ rows: 8 }} />
                    </Card>
                ) : error ? (
                    <Card className="spa-table-card">
                        <Alert type="error" message="Falha ao carregar dados" description={error} showIcon />
                    </Card>
                ) : !plan ? (
                    <Card className="spa-table-card">
                        <Empty description={payload.message || 'Nenhum plano comercial contratado foi localizado.'} />
                    </Card>
                ) : (
                    <Space direction="vertical" size={20} style={{ width: '100%' }}>
                        <Card className="spa-table-card" title="Resumo do plano" extra={<CrownOutlined />}>
                            <Row gutter={[16, 16]}>
                                {planHighlights.map((item) => (
                                    <Col xs={24} md={12} key={item.label}>
                                        <SummaryTile
                                            label={item.label}
                                            value={item.value}
                                            icon={item.icon}
                                            color={plan.active ? 'green' : 'amber'}
                                        />
                                    </Col>
                                ))}
                            </Row>
                        </Card>

                        <Card className="spa-table-card" title="Informações do plano" extra={<GlobalOutlined />}>
                            <Descriptions bordered column={{ xs: 1, md: 2 }} size="small">
                                <Descriptions.Item label="ID">{formatText(plan.id)}</Descriptions.Item>
                                <Descriptions.Item label="Nome">{formatText(plan.name)}</Descriptions.Item>
                                <Descriptions.Item label="Descrição">{formatText(plan.description, 'Sem descrição cadastrada')}</Descriptions.Item>
                                <Descriptions.Item label="Gateway">{formatText(plan.gateway_id)}</Descriptions.Item>
                                <Descriptions.Item label="Tipo">{formatText(plan.type)}</Descriptions.Item>
                                <Descriptions.Item label="Modalidade">{formatText(plan.modality_label ?? plan.modality)}</Descriptions.Item>
                                <Descriptions.Item label="Antecipação">{formatText(plan.allow_anticipation_label)}</Descriptions.Item>
                                <Descriptions.Item label="Status">
                                    <Tag color={formatPlanTagColor(plan)}>
                                        {plan.active ? <CheckCircleFilled /> : <ClockCircleOutlined />} {formatText(plan.status_label)}
                                    </Tag>
                                </Descriptions.Item>
                                <Descriptions.Item label="Contratado em">{formatText(plan.contracted_at, 'Data indisponível')}</Descriptions.Item>
                            </Descriptions>
                        </Card>
                    </Space>
                )}
            </Col>
        </Row>
    );
}

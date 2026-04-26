import {
    ArrowLeftOutlined,
    EditOutlined,
    EnvironmentOutlined,
    FieldTimeOutlined,
    MailOutlined,
    ShopOutlined,
    SolutionOutlined,
    SafetyOutlined,
} from '@ant-design/icons';
import { Alert, Breadcrumb, Button, Card, Col, Descriptions, Empty, Row, Skeleton, Space, Tag, Timeline, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import StatusPill from '../components/StatusPill';

const defaultPayload = {
    establishment: null,
    recent_transactions: [],
};

const riskTone = {
    Baixo: 'green',
    Médio: 'gold',
    Alto: 'red',
    'N/A': 'default',
};

function formatText(value) {
    return value === null || value === undefined || value === '' ? 'N/A' : value;
}

function formatMoney(value) {
    return formatText(value);
}

export default function EstabelecimentoDetailsPage() {
    const { estabelecimentoId } = useParams();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [payload, setPayload] = useState(defaultPayload);

    useEffect(() => {
        const controller = new AbortController();

        async function loadDetails() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch(`/api/spa/estabelecimentos/${estabelecimentoId}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar os detalhes do estabelecimento.');
                }

                const data = await response.json();
                setPayload({
                    establishment: data.establishment ?? null,
                    recent_transactions: data.recent_transactions ?? [],
                });
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar os detalhes.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadDetails();

        return () => controller.abort();
    }, [estabelecimentoId]);

    const establishment = payload.establishment;

    const timelineItems = useMemo(() => {
        return (establishment?.timeline ?? []).map((item) => ({
            color: item.color,
            children: (
                <div>
                    <Typography.Text strong>{item.title}</Typography.Text>
                    <div>
                        <Typography.Text type="secondary">{item.description}</Typography.Text>
                    </div>
                </div>
            ),
        }));
    }, [establishment?.timeline]);

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card className="spa-toolbar-card">
                    <Space direction="vertical" size={14} style={{ width: '100%' }}>
                        <Breadcrumb
                            items={[
                                { title: <Link to="/home">Dashboard</Link> },
                                { title: <Link to="/estabelecimentos">Estabelecimentos</Link> },
                                { title: 'Detalhes' },
                            ]}
                        />

                        <Row gutter={[16, 16]} align="middle" justify="space-between">
                            <Col xs={24} md={16}>
                                <Typography.Text className="spa-brand-kicker">Detalhes do estabelecimento</Typography.Text>
                                <Typography.Title level={2} style={{ margin: '6px 0 0' }}>
                                    {establishment?.display_name ?? 'Estabelecimento'}
                                </Typography.Title>
                                <Typography.Paragraph type="secondary" style={{ marginBottom: 0 }}>
                                    {formatText(establishment?.document)} · {formatText(establishment?.email)}
                                </Typography.Paragraph>
                            </Col>

                            <Col xs={24} md={8}>
                                <Space wrap style={{ width: '100%', justifyContent: 'flex-end' }}>
                                    <Button icon={<ArrowLeftOutlined />} onClick={() => navigate('/estabelecimentos')}>
                                        Voltar
                                    </Button>
                                    <Button
                                        type="primary"
                                        icon={<EditOutlined />}
                                        onClick={() => navigate(`/estabelecimentos/${estabelecimentoId}/editar`)}
                                    >
                                        Editar
                                    </Button>
                                </Space>
                            </Col>
                        </Row>
                    </Space>
                </Card>
            </Col>

            <Col xs={24} lg={16}>
                {loading ? (
                    <Card className="spa-table-card">
                        <Skeleton active paragraph={{ rows: 8 }} />
                    </Card>
                ) : error ? (
                    <Card className="spa-table-card">
                        <Alert type="error" message="Falha ao carregar dados" description={error} showIcon />
                    </Card>
                ) : !establishment ? (
                    <Card className="spa-table-card">
                        <Empty description="Nenhum estabelecimento encontrado" />
                    </Card>
                ) : (
                    <Space direction="vertical" size={20} style={{ width: '100%' }}>
                        <Card className="spa-table-card" title="Informações principais" extra={<ShopOutlined />}>
                            <Descriptions bordered column={{ xs: 1, md: 2 }} size="small">
                                <Descriptions.Item label="ID">{formatText(establishment.id)}</Descriptions.Item>
                                <Descriptions.Item label="Documento">{formatText(establishment.document)}</Descriptions.Item>
                                <Descriptions.Item label="Nome">{formatText(establishment.display_name)}</Descriptions.Item>
                                <Descriptions.Item label="Responsável">{formatText(establishment.responsible)}</Descriptions.Item>
                                <Descriptions.Item label="E-mail">{formatText(establishment.email)}</Descriptions.Item>
                                <Descriptions.Item label="Telefone">{formatText(establishment.phone_number)}</Descriptions.Item>
                                <Descriptions.Item label="Tipo de acesso">{formatText(establishment.access_type_label)}</Descriptions.Item>
                                <Descriptions.Item label="Categoria">{formatText(establishment.category)}</Descriptions.Item>
                            </Descriptions>
                        </Card>

                        <Card className="spa-table-card" title="Endereço" extra={<EnvironmentOutlined />}>
                            <Descriptions bordered column={{ xs: 1, md: 2 }} size="small">
                                <Descriptions.Item label="Rua">{formatText(establishment.address?.street)}</Descriptions.Item>
                                <Descriptions.Item label="Número">{formatText(establishment.address?.number)}</Descriptions.Item>
                                <Descriptions.Item label="Bairro">{formatText(establishment.address?.neighborhood)}</Descriptions.Item>
                                <Descriptions.Item label="Cidade">{formatText(establishment.address?.city)}</Descriptions.Item>
                                <Descriptions.Item label="Estado">{formatText(establishment.address?.state)}</Descriptions.Item>
                                <Descriptions.Item label="CEP">{formatText(establishment.address?.zip_code)}</Descriptions.Item>
                                <Descriptions.Item label="Complemento">{formatText(establishment.address?.complement)}</Descriptions.Item>
                                <Descriptions.Item label="Endereço formatado">{formatText(establishment.address?.formatted)}</Descriptions.Item>
                            </Descriptions>
                        </Card>

                        <Card className="spa-table-card" title="Informações empresariais" extra={<SolutionOutlined />}>
                            <Descriptions bordered column={{ xs: 1, md: 2 }} size="small">
                                <Descriptions.Item label="Receita">{formatMoney(establishment.financial?.revenue ?? establishment.revenue_label)}</Descriptions.Item>
                                <Descriptions.Item label="GMV">{formatText(establishment.financial?.gmv)}</Descriptions.Item>
                                <Descriptions.Item label="Formato">{formatText(establishment.financial?.format)}</Descriptions.Item>
                                <Descriptions.Item label="Nascimento">{formatText(establishment.financial?.birthdate)}</Descriptions.Item>
                            </Descriptions>
                        </Card>
                    </Space>
                )}
            </Col>

            <Col xs={24} lg={8}>
                {loading ? (
                    <Card className="spa-quick-view-card">
                        <Skeleton active paragraph={{ rows: 6 }} />
                    </Card>
                ) : error ? null : establishment ? (
                    <Space direction="vertical" size={20} style={{ width: '100%' }}>
                        <Card className="spa-quick-view-card" title="Status e risco" extra={<SafetyOutlined />}>
                            <Space direction="vertical" size={12} className="spa-detail-stack">
                                <div>
                                    <Typography.Text type="secondary">Status</Typography.Text>
                                    <div style={{ marginTop: 6 }}>
                                        <StatusPill status={establishment.status_label} />
                                    </div>
                                </div>

                                <div>
                                    <Typography.Text type="secondary">Risco</Typography.Text>
                                    <div style={{ marginTop: 6 }}>
                                        <Tag color={riskTone[establishment.risk_label] ?? 'default'}>{formatText(establishment.risk_label)}</Tag>
                                    </div>
                                </div>

                                <div>
                                    <Typography.Text type="secondary">Ativo</Typography.Text>
                                    <div style={{ marginTop: 6 }}>
                                        <Tag color={establishment.active ? 'green' : 'volcano'}>{establishment.active ? 'Sim' : 'Não'}</Tag>
                                    </div>
                                </div>

                                <div>
                                    <Typography.Text type="secondary">Receita</Typography.Text>
                                    <div style={{ marginTop: 6 }}>
                                        <Typography.Text strong>{formatMoney(establishment.revenue_label)}</Typography.Text>
                                    </div>
                                </div>
                            </Space>
                        </Card>

                        <Card className="spa-quick-view-card" title="Timeline" extra={<FieldTimeOutlined />}>
                            {timelineItems.length === 0 ? (
                                <Empty description="Nenhuma movimentação recente" />
                            ) : (
                                <Timeline items={timelineItems} className="spa-timeline" />
                            )}
                        </Card>

                        <Card className="spa-quick-view-card" title="Contato" extra={<MailOutlined />}>
                            <Space direction="vertical" size={8}>
                                <Typography.Text strong>{formatText(establishment.email)}</Typography.Text>
                                <Typography.Text type="secondary">{formatText(establishment.phone_number)}</Typography.Text>
                                <Typography.Text type="secondary">{formatText(establishment.responsible)}</Typography.Text>
                            </Space>
                        </Card>
                    </Space>
                ) : null}
            </Col>
        </Row>
    );
}

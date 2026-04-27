import { EyeOutlined, HomeOutlined, SaveOutlined } from '@ant-design/icons';
import {
    Alert,
    Button,
    Card,
    Col,
    DatePicker,
    Divider,
    Input,
    InputNumber,
    Row,
    Select,
    Space,
    Tag,
    Typography,
} from 'antd';
import dayjs from 'dayjs';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import StatusPill from '../components/StatusPill';

const formatOptions = [
    { value: 'SS', label: 'SS - Sociedade Simples' },
    { value: 'SC', label: 'SC - Sociedade Civil' },
    { value: 'SPE', label: 'SPE - Sociedade de Propósito Específico' },
    { value: 'LTDA', label: 'LTDA - Sociedade Limitada' },
    { value: 'SA', label: 'SA - Sociedade Anônima' },
    { value: 'ME', label: 'ME - Microempresa' },
    { value: 'MEI', label: 'MEI - Microempreendedor Individual' },
    { value: 'EI', label: 'EI - Empresário Individual' },
    { value: 'EIRELI', label: 'EIRELI - Empresa Individual de Responsabilidade Limitada' },
    { value: 'SLU', label: 'SLU - Sociedade Limitada Unipessoal' },
    { value: 'ESI', label: 'ESI - Empresa Simples de Inovação' },
];

const initialState = {
    access_type: 'ACQUIRER',
    first_name: '',
    last_name: '',
    phone_number: '',
    revenue: '',
    format: 'MEI',
    email: '',
    gmv: '',
    birthdate: null,
};

function formatText(value) {
    return value === null || value === undefined || value === '' ? 'N/A' : value;
}

function currencyInput(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const numericValue = Number(String(value).replace(/[^\d.]/g, ''));

    return Number.isNaN(numericValue) ? '' : numericValue;
}

export default function EstabelecimentoFormPage() {
    const navigate = useNavigate();
    const { estabelecimentoId } = useParams();
    const isEdit = Boolean(estabelecimentoId);
    const [loading, setLoading] = useState(isEdit);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [formState, setFormState] = useState(initialState);
    const [systemInfo, setSystemInfo] = useState(null);

    useEffect(() => {
        const controller = new AbortController();

        async function loadEstablishment() {
            if (!isEdit) {
                setLoading(false);
                return;
            }

            try {
                const response = await fetch(`/api/spa/estabelecimentos/${estabelecimentoId}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar o estabelecimento.');
                }

                const data = await response.json();
                const establishment = data.establishment ?? {};

                setFormState({
                    access_type: establishment.access_type ?? 'ACQUIRER',
                    first_name: establishment.first_name ?? '',
                    last_name: establishment.last_name ?? '',
                    phone_number: establishment.phone_number ?? '',
                    revenue: establishment.revenue_cents ? currencyInput(establishment.revenue_cents / 100) : currencyInput(establishment.revenue),
                    format: establishment.format ?? 'MEI',
                    email: establishment.email ?? '',
                    gmv: establishment.gmv ?? '',
                    birthdate: establishment.birthdate ? dayjs(establishment.birthdate) : null,
                });

                setSystemInfo({
                    id: establishment.id,
                    document: establishment.document,
                    status: establishment.status_label ?? establishment.status,
                    risk: establishment.risk_label ?? establishment.risk,
                });
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar o estabelecimento.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadEstablishment();

        return () => controller.abort();
    }, [estabelecimentoId, isEdit]);

    async function handleSubmit(event) {
        event.preventDefault();
        setSubmitting(true);
        setError('');
        setSuccess('');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const payload = {
                ...formState,
                birthdate: formState.birthdate ? formState.birthdate.format('YYYY-MM-DD') : null,
            };

            const response = await fetch(`/estabelecimentos/${estabelecimentoId}`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = Object.values(result.errors ?? {}).flat().shift();
                throw new Error(firstError ?? result.message ?? 'Não foi possível salvar o estabelecimento.');
            }

            setSuccess(result.message ?? 'Estabelecimento atualizado com sucesso.');
            navigate(result.redirect ?? '/estabelecimentos');
        } catch (submitError) {
            setError(submitError.message || 'Falha ao salvar o estabelecimento.');
        } finally {
            setSubmitting(false);
        }
    }

    function updateField(field, value) {
        setFormState((current) => ({ ...current, [field]: value }));
    }

    const displayName = `${formState.first_name ?? ''} ${formState.last_name ?? ''}`.trim();

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card className="spa-toolbar-card">
                    <Row gutter={[16, 16]} align="middle" justify="space-between">
                        <Col xs={24} md={16}>
                            <Typography.Text className="spa-brand-kicker">Editar Estabelecimento</Typography.Text>
                            <Typography.Title level={2} style={{ marginTop: 8, marginBottom: 2 }}>
                                {displayName || 'Estabelecimento'}
                            </Typography.Title>
                        </Col>

                        <Col xs={24} md={8}>
                            <Space wrap style={{ width: '100%', justifyContent: 'flex-end' }}>
                                <Button icon={<EyeOutlined />} onClick={() => navigate(`/estabelecimentos/${estabelecimentoId}`)}>
                                    Visualizar
                                </Button>
                                <Button icon={<HomeOutlined />} onClick={() => navigate('/home')} />
                            </Space>
                        </Col>
                    </Row>

                    {error ? <Alert style={{ marginTop: 16 }} type="error" showIcon message={error} /> : null}
                    {success ? <Alert style={{ marginTop: 16 }} type="success" showIcon message={success} /> : null}
                </Card>
            </Col>

            <Col span={24}>
                <Card className="spa-table-card">
                    {loading ? (
                        <Typography.Text type="secondary">Carregando formulário...</Typography.Text>
                    ) : (
                        <form onSubmit={handleSubmit}>
                            <Row gutter={[24, 24]}>
                                <Col xs={24} lg={13}>
                                    <Typography.Title level={4} className="spa-section-title">
                                        Informações Básicas
                                    </Typography.Title>

                                    <Space direction="vertical" size={14} style={{ width: '100%' }}>
                                        <div>
                                            <Typography.Text strong>Tipo de Acesso *</Typography.Text>
                                            <Select
                                                value={formState.access_type}
                                                onChange={(value) => updateField('access_type', value)}
                                                style={{ width: '100%' }}
                                                size="large"
                                                options={[
                                                    { value: 'ACQUIRER', label: 'ACQUIRER' },
                                                    { value: 'BANKING', label: 'BANKING' },
                                                ]}
                                            />
                                        </div>

                                        <div>
                                            <Typography.Text strong>Nome / Razão Social</Typography.Text>
                                            <Input
                                                value={formState.first_name}
                                                onChange={(event) => updateField('first_name', event.target.value)}
                                                placeholder="Razão social / nome"
                                                size="large"
                                            />
                                        </div>

                                        <div>
                                            <Typography.Text strong>Nome Fantasia / Sobrenome</Typography.Text>
                                            <Input
                                                value={formState.last_name}
                                                onChange={(event) => updateField('last_name', event.target.value)}
                                                placeholder="Nome fantasia / sobrenome"
                                                size="large"
                                            />
                                        </div>

                                        <div>
                                            <Typography.Text strong>Email *</Typography.Text>
                                            <Input
                                                value={formState.email}
                                                onChange={(event) => updateField('email', event.target.value)}
                                                placeholder="email@empresa.com"
                                                size="large"
                                            />
                                        </div>

                                        <div>
                                            <Typography.Text strong>Telefone *</Typography.Text>
                                            <Input
                                                value={formState.phone_number}
                                                onChange={(event) => updateField('phone_number', event.target.value)}
                                                placeholder="Telefone"
                                                size="large"
                                            />
                                        </div>
                                    </Space>
                                </Col>

                                <Col xs={24} lg={11}>
                                    <Typography.Title level={4} className="spa-section-title">
                                        Informações Empresariais
                                    </Typography.Title>

                                    <Space direction="vertical" size={14} style={{ width: '100%' }}>
                                        <div>
                                            <Typography.Text strong>Tipo Societário *</Typography.Text>
                                            <Select
                                                value={formState.format}
                                                onChange={(value) => updateField('format', value)}
                                                style={{ width: '100%' }}
                                                size="large"
                                                options={formatOptions}
                                            />
                                        </div>

                                        <div>
                                            <Typography.Text strong>Receita Mensal (R$) *</Typography.Text>
                                            <InputNumber
                                                value={formState.revenue}
                                                onChange={(value) => updateField('revenue', value)}
                                                style={{ width: '100%' }}
                                                placeholder="1000000"
                                                size="large"
                                            />
                                        </div>

                                        <div>
                                            <Typography.Text strong>GMV - Volume Bruto de Vendas</Typography.Text>
                                            <InputNumber
                                                value={formState.gmv}
                                                onChange={(value) => updateField('gmv', value)}
                                                style={{ width: '100%' }}
                                                placeholder="0,00"
                                                size="large"
                                            />
                                        </div>

                                        <div>
                                            <Typography.Text strong>Data de Nascimento/Fundação *</Typography.Text>
                                            <DatePicker
                                                value={formState.birthdate}
                                                onChange={(value) => updateField('birthdate', value)}
                                                style={{ width: '100%' }}
                                                size="large"
                                                format="DD/MM/YYYY"
                                            />
                                        </div>
                                    </Space>
                                </Col>
                            </Row>

                            <Divider />

                            <Typography.Title level={4} className="spa-section-title">
                                Informações do Sistema (Somente Leitura)
                            </Typography.Title>

                            <Row gutter={[16, 16]}>
                                <Col xs={24} md={6}>
                                    <Typography.Text strong>ID: </Typography.Text>
                                    <Typography.Text>{formatText(systemInfo?.id)}</Typography.Text>
                                </Col>
                                <Col xs={24} md={6}>
                                    <Typography.Text strong>Documento: </Typography.Text>
                                    <Typography.Text>{formatText(systemInfo?.document)}</Typography.Text>
                                </Col>
                                <Col xs={24} md={6}>
                                    <Typography.Text strong>Status: </Typography.Text>
                                    <StatusPill status={systemInfo?.status} />
                                </Col>
                                <Col xs={24} md={6}>
                                    <Typography.Text strong>Risco: </Typography.Text>
                                    <Tag color={systemInfo?.risk === 'Baixo' ? 'green' : systemInfo?.risk === 'Médio' ? 'gold' : 'default'}>
                                        {formatText(systemInfo?.risk)}
                                    </Tag>
                                </Col>
                            </Row>

                            <Divider />

                            <Space>
                                <Button type="primary" htmlType="submit" loading={submitting} icon={<SaveOutlined />}>
                                    Salvar Alterações
                                </Button>
                                <Button onClick={() => navigate('/estabelecimentos')}>
                                    Cancelar
                                </Button>
                            </Space>
                        </form>
                    )}
                </Card>
            </Col>
        </Row>
    );
}

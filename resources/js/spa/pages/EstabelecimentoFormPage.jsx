import { SaveOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, DatePicker, Divider, Input, InputNumber, Row, Select, Space, Typography } from 'antd';
import dayjs from 'dayjs';
import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

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

export default function EstabelecimentoFormPage() {
    const navigate = useNavigate();
    const { estabelecimentoId } = useParams();
    const isEdit = Boolean(estabelecimentoId);
    const [loading, setLoading] = useState(isEdit);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [formState, setFormState] = useState(initialState);

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
                    revenue: establishment.revenue ?? '',
                    format: establishment.format ?? 'MEI',
                    email: establishment.email ?? '',
                    gmv: establishment.gmv ?? '',
                    birthdate: establishment.birthdate ? dayjs(establishment.birthdate) : null,
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

    return (
        <Row gutter={[20, 20]} className="spa-profile-grid">
            <Col xs={24} xl={16}>
                <Card className="spa-hero-card">
                    <Space direction="vertical" size={18} className="spa-hero-stack">
                        <div>
                            <Typography.Text className="spa-brand-kicker">Estabelecimentos</Typography.Text>
                            <Typography.Title level={2} className="spa-hero-title">
                                {isEdit ? 'Editar estabelecimento' : 'Novo estabelecimento'}
                            </Typography.Title>
                            <Typography.Paragraph className="spa-hero-description">
                                Atualize os dados operacionais do cadastro com o mesmo padrão visual da interface nova.
                            </Typography.Paragraph>
                        </div>

                        {error ? <Alert type="error" showIcon message={error} /> : null}
                        {success ? <Alert type="success" showIcon message={success} /> : null}
                    </Space>
                </Card>

                <Card className="spa-table-card" title="Dados do estabelecimento">
                    {loading ? (
                        <Typography.Text type="secondary">Carregando formulário...</Typography.Text>
                    ) : (
                        <form onSubmit={handleSubmit}>
                            <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <Typography.Text strong>Tipo de acesso</Typography.Text>
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
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Typography.Text strong>Formato jurídico</Typography.Text>
                                        <Select
                                            value={formState.format}
                                            onChange={(value) => updateField('format', value)}
                                            style={{ width: '100%' }}
                                            size="large"
                                            options={formatOptions}
                                        />
                                    </Col>
                                </Row>

                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <Typography.Text strong>Nome</Typography.Text>
                                        <Input
                                            value={formState.first_name}
                                            onChange={(event) => updateField('first_name', event.target.value)}
                                            placeholder="Razão social / nome"
                                        />
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Typography.Text strong>Sobrenome / fantasia</Typography.Text>
                                        <Input
                                            value={formState.last_name}
                                            onChange={(event) => updateField('last_name', event.target.value)}
                                            placeholder="Nome fantasia / sobrenome"
                                        />
                                    </Col>
                                </Row>

                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <Typography.Text strong>E-mail</Typography.Text>
                                        <Input
                                            value={formState.email}
                                            onChange={(event) => updateField('email', event.target.value)}
                                            placeholder="email@empresa.com"
                                        />
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Typography.Text strong>Telefone</Typography.Text>
                                        <Input
                                            value={formState.phone_number}
                                            onChange={(event) => updateField('phone_number', event.target.value)}
                                            placeholder="Telefone"
                                        />
                                    </Col>
                                </Row>

                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <Typography.Text strong>Receita mensal</Typography.Text>
                                        <InputNumber
                                            value={formState.revenue}
                                            onChange={(value) => updateField('revenue', value)}
                                            style={{ width: '100%' }}
                                            placeholder="0,00"
                                        />
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Typography.Text strong>GMV</Typography.Text>
                                        <InputNumber
                                            value={formState.gmv}
                                            onChange={(value) => updateField('gmv', value)}
                                            style={{ width: '100%' }}
                                            placeholder="0,00"
                                        />
                                    </Col>
                                </Row>

                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <Typography.Text strong>Data de nascimento/fundação</Typography.Text>
                                        <DatePicker
                                            value={formState.birthdate}
                                            onChange={(value) => updateField('birthdate', value)}
                                            style={{ width: '100%' }}
                                        />
                                    </Col>
                                </Row>

                                <Divider />

                                <div className="spa-profile-actions">
                                    <Button type="primary" htmlType="submit" loading={submitting} icon={<SaveOutlined />}>
                                        Salvar estabelecimento
                                    </Button>
                                    <Button onClick={() => navigate('/estabelecimentos')}>
                                        Cancelar
                                    </Button>
                                </div>
                            </Space>
                        </form>
                    )}
                </Card>
            </Col>

            <Col xs={24} xl={8}>
                <Card className="spa-quick-view-card" title="Atalhos">
                    <Space direction="vertical" size={14} className="spa-detail-stack">
                        <Typography.Text type="secondary">Ações rápidas</Typography.Text>
                        <Link to="/estabelecimentos">Voltar para listagem</Link>
                        <Link to="/estabelecimentos/export">Exportar planilha</Link>
                        <Link to="/home">Ir para home</Link>
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

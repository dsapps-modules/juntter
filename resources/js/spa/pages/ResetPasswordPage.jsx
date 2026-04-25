import { ArrowRightOutlined, LockOutlined, MailOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, Input, Row, Space, Tag, Typography } from 'antd';
import { useState } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';

export default function ResetPasswordPage() {
    const { token } = useParams();
    const [searchParams] = useSearchParams();
    const [email, setEmail] = useState(searchParams.get('email') ?? '');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    const handleSubmit = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError('');
        setSuccess('');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const formData = new FormData();
            formData.append('token', token ?? '');
            formData.append('email', email);
            formData.append('password', password);
            formData.append('password_confirmation', passwordConfirmation);

            const response = await fetch('/reset-password', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                body: formData,
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = Object.values(payload.errors ?? {}).flat().shift();
                throw new Error(firstError ?? payload.message ?? 'Falha ao redefinir a senha.');
            }

            setSuccess('Senha redefinida com sucesso. Você já pode entrar novamente.');
        } catch (submitError) {
            setError(submitError.message || 'Falha ao redefinir a senha.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="spa-auth-page">
            <div className="spa-auth-backdrop spa-auth-backdrop-left" />
            <div className="spa-auth-backdrop spa-auth-backdrop-right" />

            <Row className="spa-auth-grid" gutter={24} align="middle">
                <Col xs={24} lg={12}>
                    <div className="spa-auth-hero">
                        <Tag color="gold" className="spa-auth-tag">
                            Nova senha
                        </Tag>
                        <Typography.Text className="spa-brand-kicker">Juntter</Typography.Text>
                        <Typography.Title level={1} className="spa-auth-title">
                            Redefina sua senha com a mesma linha visual da plataforma.
                        </Typography.Title>
                        <Typography.Paragraph className="spa-auth-description">
                            O link é validado pelo backend, enquanto a interface mantém o padrão amarelo, limpo e direto da migração.
                        </Typography.Paragraph>
                    </div>
                </Col>

                <Col xs={24} lg={10} xl={8}>
                    <Card className="spa-auth-card">
                        <Typography.Text className="spa-brand-kicker">Redefinir senha</Typography.Text>
                        <Typography.Title level={3} className="spa-auth-card-title">
                            Criar nova senha
                        </Typography.Title>
                        <Typography.Paragraph type="secondary">
                            Digite o e-mail da conta e escolha uma nova senha.
                        </Typography.Paragraph>

                        {error ? <Alert type="error" showIcon message={error} className="spa-auth-alert" /> : null}
                        {success ? <Alert type="success" showIcon message={success} className="spa-auth-alert" /> : null}

                        <form onSubmit={handleSubmit}>
                            <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                <div>
                                    <Typography.Text strong>E-mail</Typography.Text>
                                    <Input
                                        prefix={<MailOutlined />}
                                        size="large"
                                        placeholder="nome@empresa.com"
                                        value={email}
                                        onChange={(event) => setEmail(event.target.value)}
                                        autoComplete="username"
                                        className="spa-auth-input"
                                    />
                                </div>

                                <div>
                                    <Typography.Text strong>Nova senha</Typography.Text>
                                    <Input.Password
                                        prefix={<LockOutlined />}
                                        size="large"
                                        placeholder="Nova senha"
                                        value={password}
                                        onChange={(event) => setPassword(event.target.value)}
                                        autoComplete="new-password"
                                        className="spa-auth-input"
                                    />
                                </div>

                                <div>
                                    <Typography.Text strong>Confirmar senha</Typography.Text>
                                    <Input.Password
                                        prefix={<LockOutlined />}
                                        size="large"
                                        placeholder="Confirmar senha"
                                        value={passwordConfirmation}
                                        onChange={(event) => setPasswordConfirmation(event.target.value)}
                                        autoComplete="new-password"
                                        className="spa-auth-input"
                                    />
                                </div>

                                <Button
                                    htmlType="submit"
                                    type="primary"
                                    size="large"
                                    block
                                    loading={loading}
                                    className="spa-primary-button"
                                    icon={<ArrowRightOutlined />}
                                >
                                    Redefinir senha
                                </Button>
                            </Space>
                        </form>

                        <div className="spa-auth-links">
                            <Link to="/login">Voltar ao login</Link>
                            <Link to="/">Ir para a home</Link>
                        </div>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

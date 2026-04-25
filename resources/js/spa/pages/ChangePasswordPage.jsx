import { ArrowRightOutlined, LockOutlined, SafetyCertificateOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, Input, Row, Space, Tag, Typography } from 'antd';
import { useState } from 'react';
import { Link } from 'react-router-dom';

export default function ChangePasswordPage() {
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError('');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const formData = new FormData();
            formData.append('password', password);
            formData.append('password_confirmation', passwordConfirmation);

            const response = await fetch('/password/change', {
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
                throw new Error(firstError ?? payload.message ?? 'Falha ao trocar a senha.');
            }

            window.location.assign(payload.redirect ?? '/app/login');
        } catch (submitError) {
            setError(submitError.message || 'Falha ao trocar a senha.');
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
                            Primeiro acesso
                        </Tag>
                        <Typography.Text className="spa-brand-kicker">Juntter</Typography.Text>
                        <Typography.Title level={1} className="spa-auth-title">
                            Troque sua senha e volte para a nova home React.
                        </Typography.Title>
                        <Typography.Paragraph className="spa-auth-description">
                            O fluxo foi desenhado para manter a regra de segurança do backend e, ao mesmo tempo, seguir o visual da migração.
                        </Typography.Paragraph>

                        <Space wrap className="spa-auth-points">
                            <Card className="spa-auth-point-card" bordered={false}>
                                <SafetyCertificateOutlined />
                                <span>Segurança</span>
                            </Card>
                            <Card className="spa-auth-point-card" bordered={false}>
                                <ArrowRightOutlined />
                                <span>Retorno rápido</span>
                            </Card>
                        </Space>
                    </div>
                </Col>

                <Col xs={24} lg={10} xl={8}>
                    <Card className="spa-auth-card">
                        <Typography.Text className="spa-brand-kicker">Trocar senha</Typography.Text>
                        <Typography.Title level={3} className="spa-auth-card-title">
                            Nova senha
                        </Typography.Title>
                        <Typography.Paragraph type="secondary">
                            Use uma senha segura para liberar o próximo acesso.
                        </Typography.Paragraph>

                        {error ? <Alert type="error" showIcon message={error} className="spa-auth-alert" /> : null}

                        <form onSubmit={handleSubmit}>
                            <Space direction="vertical" size={16} style={{ width: '100%' }}>
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
                                    Trocar senha
                                </Button>
                            </Space>
                        </form>

                        <div className="spa-auth-links">
                            <Link to="/login">Ir para login</Link>
                            <Link to="/">Ir para a home</Link>
                        </div>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

import { ArrowRightOutlined, MailOutlined, SendOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, Input, Row, Space, Typography } from 'antd';
import { useState } from 'react';
import { Link } from 'react-router-dom';

export default function ForgotPasswordPage() {
    const [email, setEmail] = useState('');
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
            formData.append('email', email);

            const response = await fetch('/forgot-password', {
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
                throw new Error(firstError ?? payload.message ?? 'Falha ao enviar o e-mail.');
            }

            setSuccess('Se o e-mail existir, você receberá as instruções de redefinição.');
        } catch (submitError) {
            setError(submitError.message || 'Falha ao enviar o e-mail.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="spa-auth-page">
            <div className="spa-auth-logo">
                <img src="/img/logo/juntter_webp_640_174.webp" alt="Juntter" className="spa-auth-logo-image" />
            </div>
            <div className="spa-auth-backdrop spa-auth-backdrop-left" />
            <div className="spa-auth-backdrop spa-auth-backdrop-right" />

            <Row className="spa-auth-grid" gutter={24} align="middle">
                <Col xs={24} lg={12}>
                    <div className="spa-auth-hero">
                        <Typography.Title level={1} className="spa-auth-title">
                            Recupere o acesso de forma rápida e simples.
                        </Typography.Title>

                        <div className="spa-auth-description-spacer" aria-hidden="true" />

                        <Space wrap className="spa-auth-points">
                            <Card className="spa-auth-point-card" bordered={false}>
                                <SendOutlined />
                                <span>Envio direto</span>
                            </Card>
                            <Card className="spa-auth-point-card" bordered={false}>
                                <MailOutlined />
                                <span>Mensagem clara</span>
                            </Card>
                            <Card className="spa-auth-point-card" bordered={false}>
                                <ArrowRightOutlined />
                                <span>Retorno simples</span>
                            </Card>
                        </Space>
                    </div>
                </Col>

                <Col xs={24} lg={10} xl={8}>
                    <Card className="spa-auth-card">
                        <Typography.Text className="spa-brand-kicker">Esqueci a senha</Typography.Text>
                        <Typography.Title level={3} className="spa-auth-card-title">
                            Recuperar acesso
                        </Typography.Title>

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

                                <Button
                                    htmlType="submit"
                                    type="primary"
                                    size="large"
                                    block
                                    loading={loading}
                                    className="spa-primary-button"
                                    icon={<ArrowRightOutlined />}
                                >
                                    Enviar instruções
                                </Button>
                            </Space>
                        </form>

                        <div className="spa-auth-links">
                            <Link to="/login">Voltar ao login</Link>
                            <a href="/">Ir para a home</a>
                        </div>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

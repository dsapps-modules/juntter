import {
    ArrowRightOutlined,
    LockOutlined,
    MailOutlined,
    SafetyCertificateOutlined,
    ThunderboltOutlined,
} from '@ant-design/icons';
import { Alert, Button, Card, Checkbox, Col, Input, Row, Space, Typography } from 'antd';
import { useState } from 'react';
import { Link } from 'react-router-dom';

export default function LoginPage() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(true);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (event) => {
        event.preventDefault();
        setLoading(true);
        setError('');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const formData = new FormData();

            formData.append('email', email);
            formData.append('password', password);
            formData.append('remember', remember ? '1' : '0');

            const response = await fetch('/login', {
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

                throw new Error(firstError ?? payload.message ?? 'Falha ao entrar.');
            }

            window.location.assign(payload.redirect ?? '/app/home');
        } catch (submitError) {
            setError(submitError.message || 'Falha ao entrar.');
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
                            Pagamentos de forma segura e facilitada
                        </Typography.Title>

                        <Space wrap className="spa-auth-points">
                            <Card className="spa-auth-point-card" bordered={false}>
                                <SafetyCertificateOutlined />
                                <span>Segurança</span>
                            </Card>
                            <Card className="spa-auth-point-card" bordered={false}>
                                <ThunderboltOutlined />
                                <span>Acesso rápido</span>
                            </Card>
                            <Card className="spa-auth-point-card" bordered={false}>
                                <ArrowRightOutlined />
                                <span>Fluxo enxuto</span>
                            </Card>
                        </Space>
                    </div>
                </Col>

                <Col xs={24} lg={10} xl={8}>
                    <Card className="spa-auth-card">
                        <Typography.Title level={3} className="spa-auth-card-title">
                            Login
                        </Typography.Title>

                        {error ? <Alert type="error" showIcon message={error} className="spa-auth-alert" /> : null}

                        <form onSubmit={handleSubmit}>
                            <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                <div>
                                    <Typography.Text strong>E-mail</Typography.Text>
                                    <Input
                                        name="email"
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
                                    <Typography.Text strong>Senha</Typography.Text>
                                    <Input.Password
                                        name="password"
                                        prefix={<LockOutlined />}
                                        size="large"
                                        placeholder="Sua senha"
                                        value={password}
                                        onChange={(event) => setPassword(event.target.value)}
                                        autoComplete="current-password"
                                        className="spa-auth-input"
                                    />
                                </div>

                                <div className="spa-auth-row">
                                    <Checkbox checked={remember} onChange={(event) => setRemember(event.target.checked)}>
                                        Manter conectado
                                    </Checkbox>
                                    <Link to="/forgot-password" className="spa-auth-link">
                                        Esqueci a senha
                                    </Link>
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
                                    Entrar
                                </Button>
                            </Space>
                        </form>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

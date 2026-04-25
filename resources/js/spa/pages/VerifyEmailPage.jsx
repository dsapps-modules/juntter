import { ArrowRightOutlined, MailOutlined, ReloadOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, Row, Space, Tag, Typography } from 'antd';
import { useState } from 'react';
import { Link } from 'react-router-dom';

export default function VerifyEmailPage() {
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');

    const handleResend = async () => {
        setLoading(true);
        setMessage('');
        setError('');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch('/email/verification-notification', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = Object.values(payload.errors ?? {}).flat().shift();
                throw new Error(firstError ?? payload.message ?? 'Falha ao reenviar o e-mail.');
            }

            setMessage('Enviamos um novo link de verificação para o seu e-mail.');
        } catch (submitError) {
            setError(submitError.message || 'Falha ao reenviar o e-mail.');
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
                            Verificação
                        </Tag>
                        <Typography.Text className="spa-brand-kicker">Juntter</Typography.Text>
                        <Typography.Title level={1} className="spa-auth-title">
                            Confirme seu e-mail para liberar a navegação completa.
                        </Typography.Title>
                        <Typography.Paragraph className="spa-auth-description">
                            Essa tela mantém a identidade da migração e deixa claro o estado da conta sem sobrecarregar a interface.
                        </Typography.Paragraph>
                    </div>
                </Col>

                <Col xs={24} lg={10} xl={8}>
                    <Card className="spa-auth-card">
                        <Typography.Text className="spa-brand-kicker">Verifique seu e-mail</Typography.Text>
                        <Typography.Title level={3} className="spa-auth-card-title">
                            Link de confirmação
                        </Typography.Title>
                        <Typography.Paragraph type="secondary">
                            Se você ainda não recebeu o e-mail, pode pedir um novo link agora.
                        </Typography.Paragraph>

                        {error ? <Alert type="error" showIcon message={error} className="spa-auth-alert" /> : null}
                        {message ? <Alert type="success" showIcon message={message} className="spa-auth-alert" /> : null}

                        <Space direction="vertical" size={12} style={{ width: '100%' }}>
                            <Button
                                type="primary"
                                size="large"
                                block
                                loading={loading}
                                onClick={handleResend}
                                className="spa-primary-button"
                                icon={<ReloadOutlined />}
                            >
                                Reenviar e-mail
                            </Button>

                            <Button className="spa-secondary-button" block icon={<MailOutlined />}>
                                Abrir caixa de entrada
                            </Button>
                        </Space>

                        <div className="spa-auth-links">
                            <Link to="/home">Ir para a home</Link>
                            <Link to="/login">Sair</Link>
                        </div>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

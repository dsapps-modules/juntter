import {
    ArrowRightOutlined,
    LockOutlined,
    MailOutlined,
    SafetyCertificateOutlined,
    ThunderboltOutlined,
} from '@ant-design/icons';
import { Button, Card, Checkbox, Col, Divider, Form, Input, Row, Space, Tag, Typography } from 'antd';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';

export default function LoginPage() {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);

    const handleFinish = async () => {
        setLoading(true);
        window.setTimeout(() => {
            setLoading(false);
            navigate('/home');
        }, 450);
    };

    return (
        <div className="spa-auth-page">
            <div className="spa-auth-backdrop spa-auth-backdrop-left" />
            <div className="spa-auth-backdrop spa-auth-backdrop-right" />

            <Row className="spa-auth-grid" gutter={24} align="middle">
                <Col xs={24} lg={12}>
                    <div className="spa-auth-hero">
                        <Tag color="gold" className="spa-auth-tag">
                            Acesso controlado
                        </Tag>
                        <Typography.Text className="spa-brand-kicker">Juntter</Typography.Text>
                        <Typography.Title level={1} className="spa-auth-title">
                            Entre no centro de operações.
                        </Typography.Title>
                        <Typography.Paragraph className="spa-auth-description">
                            A tela de login já usa a nova linguagem visual: fundo suave, destaque amarelo, leitura limpa e
                            hierarquia visual mais forte para o acesso.
                        </Typography.Paragraph>

                        <Space wrap className="spa-auth-points">
                            <Card className="spa-auth-point-card" bordered={false}>
                                <SafetyCertificateOutlined />
                                <span>Segurança de sessão</span>
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
                        <Typography.Text className="spa-brand-kicker">Entrar</Typography.Text>
                        <Typography.Title level={3} className="spa-auth-card-title">
                            Login da plataforma
                        </Typography.Title>
                        <Typography.Paragraph type="secondary">
                            Use suas credenciais para acessar os módulos migrados para React.
                        </Typography.Paragraph>

                        <Form layout="vertical" onFinish={handleFinish} autoComplete="off">
                            <Form.Item
                                label="E-mail"
                                name="email"
                                rules={[
                                    { required: true, message: 'Informe o e-mail' },
                                    { type: 'email', message: 'Informe um e-mail válido' },
                                ]}
                            >
                                <Input prefix={<MailOutlined />} size="large" placeholder="nome@empresa.com" />
                            </Form.Item>

                            <Form.Item
                                label="Senha"
                                name="password"
                                rules={[{ required: true, message: 'Informe a senha' }]}
                            >
                                <Input.Password prefix={<LockOutlined />} size="large" placeholder="Sua senha" />
                            </Form.Item>

                            <div className="spa-auth-row">
                                <Form.Item name="remember" valuePropName="checked" className="spa-auth-remember">
                                    <Checkbox>Manter conectado</Checkbox>
                                </Form.Item>
                                <Button type="link" className="spa-auth-link">
                                    Esqueci a senha
                                </Button>
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
                        </Form>

                        <Divider />

                        <Space direction="vertical" size={10} className="spa-auth-footer">
                            <Typography.Text className="spa-placeholder-kicker">Login visual</Typography.Text>
                            <Typography.Text type="secondary">
                                Esta tela já está pronta para a próxima etapa da validação visual.
                            </Typography.Text>
                        </Space>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

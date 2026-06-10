import { ArrowLeftOutlined, HomeOutlined, LockOutlined } from '@ant-design/icons';
import { Button, Card, Col, Row, Space, Tag, Typography } from 'antd';
import { Link } from 'react-router-dom';

export default function UnauthorizedPage() {
    return (
        <div className="spa-auth-page">
            <div className="spa-auth-backdrop spa-auth-backdrop-left" />
            <div className="spa-auth-backdrop spa-auth-backdrop-right" />

            <Row className="spa-auth-grid" gutter={24} align="middle">
                <Col xs={24} lg={12}>
                    <div className="spa-auth-hero">
                        <Tag color="gold" className="spa-auth-tag">
                            Acesso negado
                        </Tag>
                        <Typography.Text className="spa-brand-kicker">Juntter</Typography.Text>
                        <Typography.Title level={1} className="spa-auth-title">
                            Sua conta não tem permissão para abrir esta área.
                        </Typography.Title>
                        <Typography.Paragraph className="spa-auth-description">
                            O sistema identificou sua sessão, mas o nível de acesso atual não libera esse módulo. Se isso
                            estiver incorreto, fale com o administrador da conta.
                        </Typography.Paragraph>

                        <Space wrap className="spa-auth-points">
                            <Card className="spa-auth-point-card" bordered={false}>
                                <LockOutlined />
                                <span>Permissão restrita</span>
                            </Card>
                            <Card className="spa-auth-point-card" bordered={false}>
                                <HomeOutlined />
                                <span>Retorno seguro</span>
                            </Card>
                            <Card className="spa-auth-point-card" bordered={false}>
                                <ArrowLeftOutlined />
                                <span>Fluxo simples</span>
                            </Card>
                        </Space>
                    </div>
                </Col>

                <Col xs={24} lg={10} xl={8}>
                    <Card className="spa-auth-card">
                        <Typography.Text className="spa-brand-kicker">Sem acesso</Typography.Text>
                        <Typography.Title level={3} className="spa-auth-card-title">
                            Área bloqueada
                        </Typography.Title>
                        <Typography.Paragraph type="secondary">
                            Você pode voltar para a página inicial ou acessar o painel permitido pela sua conta.
                        </Typography.Paragraph>

                        <div className="spa-auth-links">
                            <Link to="/home">Ir para a home</Link>
                            <Link to="/login">Fazer login</Link>
                        </div>

                        <Space direction="vertical" size={12} style={{ width: '100%', marginTop: 16 }}>
                            <Button type="primary" size="large" block className="spa-primary-button" icon={<HomeOutlined />}>
                                Voltar para a home
                            </Button>
                            <Button className="spa-secondary-button" block icon={<ArrowLeftOutlined />}>
                                Revisar acesso
                            </Button>
                        </Space>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}

import { BankOutlined, CreditCardOutlined, QrcodeOutlined, SendOutlined } from '@ant-design/icons';
import { Button, Card, Col, Row, Space, Tag, Typography } from 'antd';
import { useNavigate } from 'react-router-dom';

const recurrenceOptions = [
    {
        title: 'Pix',
        description: 'Cobranças instantâneas com QR Code, chave Pix e link de pagamento.',
        icon: <QrcodeOutlined />,
        path: '/recorrencia/pix',
        color: 'gold',
    },
    {
        title: 'Boleto',
        description: 'Cobranças bancárias com vencimento, juros, multa e instruções.',
        icon: <BankOutlined />,
        path: '/recorrencia/boleto',
        color: 'volcano',
    },
    {
        title: 'Cartão de Crédito',
        description: 'Cobranças parceladas com recorrência, captura e descrição na fatura.',
        icon: <CreditCardOutlined />,
        path: '/recorrencia/cartao-credito',
        color: 'blue',
    },
];

export default function RecorrenciaPage() {
    const navigate = useNavigate();

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col span={24}>
                <Card
                    className="spa-toolbar-card"
                    title={(
                        <Space size={10}>
                            <SendOutlined />
                            <span>Recorrência</span>
                        </Space>
                    )}
                    extra={(
                        <Tag color="gold">
                            E-mail e WhatsApp
                        </Tag>
                    )}
                >
                    <Typography.Paragraph style={{ marginBottom: 0 }}>
                        Selecione o tipo de cobrança recorrente que será enviado para o cliente.
                        O próximo passo é preencher os dados específicos do link.
                    </Typography.Paragraph>
                </Card>
            </Col>

            {recurrenceOptions.map((option) => (
                <Col xs={24} md={8} key={option.path}>
                    <Card
                        className="spa-quick-view-card"
                        title={(
                            <Space size={10}>
                                {option.icon}
                                <span>{option.title}</span>
                            </Space>
                        )}
                    >
                        <Space direction="vertical" size={14} style={{ width: '100%' }}>
                            <Typography.Paragraph type="secondary" style={{ marginBottom: 0 }}>
                                {option.description}
                            </Typography.Paragraph>

                            <Space wrap>
                                <Tag color={option.color}>Link de pagamento</Tag>
                                <Tag>Periodicidade</Tag>
                            </Space>

                            <Button type="primary" block onClick={() => navigate(option.path)}>
                                Configurar {option.title}
                            </Button>
                        </Space>
                    </Card>
                </Col>
            ))}
        </Row>
    );
}

import {
    CopyOutlined,
    DeleteOutlined,
    EditOutlined,
    HomeOutlined,
    LinkOutlined,
    PauseOutlined,
    PlayCircleOutlined,
    QrcodeOutlined,
    ThunderboltOutlined,
} from '@ant-design/icons';
import { Alert, Button, Card, Col, Input, Row, Skeleton, Space, Tag, Typography, message } from 'antd';
import dayjs from 'dayjs';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

const defaultLink = {
    id: null,
    codigo_unico: '',
    descricao: '',
    valor: '0.00',
    status: 'ATIVO',
    juros: 'CLIENT',
    tipo_pagamento: 'PIX',
    data_expiracao: null,
    created_at: null,
    url_completa: '',
    dados_cliente_preenchidos: {},
};

export default function LinkPagamentoPixDetailPage() {
    const navigate = useNavigate();
    const { linkId } = useParams();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [link, setLink] = useState(defaultLink);

    useEffect(() => {
        const controller = new AbortController();

        async function loadLink() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch(`/api/spa/links-pagamento/${linkId}`, {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar os detalhes do link.');
                }

                const data = await response.json();
                setLink({
                    ...defaultLink,
                    ...(data.link ?? {}),
                });
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar os detalhes.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadLink();

        return () => controller.abort();
    }, [linkId]);

    async function copyText(text) {
        if (!text) {
            return;
        }

        try {
            await navigator.clipboard.writeText(text);
            message.success('Copiado para a área de transferência.');
        } catch (copyError) {
            message.error('Não foi possível copiar o texto.');
        }
    }

    async function updateStatus(nextStatus) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(`/links-pagamento-pix/${linkId}/status`, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ status: nextStatus }),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Não foi possível alterar o status.');
            }

            setLink((current) => ({
                ...current,
                status: nextStatus,
            }));

            message.success(result.message ?? 'Status alterado com sucesso.');
        } catch (statusError) {
            message.error(statusError.message || 'Falha ao alterar o status.');
        }
    }

    async function deleteLink() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(`/links-pagamento-pix/${linkId}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                credentials: 'same-origin',
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Não foi possível excluir o link.');
            }

            message.success(result.message ?? 'Link excluído com sucesso.');
            navigate('/links-pagamento');
        } catch (deleteError) {
            message.error(deleteError.message || 'Falha ao excluir o link.');
        }
    }

    const statusColor = link.status === 'ATIVO' ? 'green' : link.status === 'EXPIRADO' ? 'volcano' : 'gold';
    const expirationDate = link.data_expiracao ? dayjs(link.data_expiracao).format('DD/MM/YYYY') : 'Sem expiração';
    const createdAt = link.created_at ? dayjs(link.created_at).format('DD/MM/YYYY HH:mm') : 'Sem data';
    const clientData = link.dados_cliente_preenchidos ?? {};
    const hasClientData = Object.values(clientData).some((value) => Boolean(value));
    const taxLabel = link.juros === 'ESTABLISHMENT' ? 'Estabelecimento paga as taxas' : 'Cliente paga as taxas';
    const paymentTypeLabel = link.tipo_pagamento === 'PIX' ? 'PIX' : link.tipo_pagamento;

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} xl={16}>
                <Card className="spa-table-card spa-pix-detail-card">
                    <Space direction="vertical" size={20} style={{ width: '100%' }}>
                        {error ? <Alert type="error" message={error} showIcon /> : null}

                        <div className="spa-pix-detail-header">
                            <div>
                                <Space align="center" size={12}>
                                    <QrcodeOutlined className="spa-pix-detail-header-icon" />
                                    <Typography.Title level={2} className="spa-pix-detail-title">
                                        Link de Pagamento PIX
                                    </Typography.Title>
                                </Space>
                                <Typography.Paragraph className="spa-pix-detail-description">
                                    {link.descricao || 'Detalhes do link PIX'}
                                </Typography.Paragraph>
                            </div>

                            <Space wrap>
                                <Button
                                    type="primary"
                                    icon={<EditOutlined />}
                                    onClick={() => navigate(`/links-pagamento/${linkId}/editar`)}
                                >
                                    Editar
                                </Button>
                                <Button icon={<HomeOutlined />} onClick={() => navigate('/home')} />
                            </Space>
                        </div>

                        <Card className="spa-pix-detail-info-card" title="Informações do Link" bordered={false}>
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 4 }} />
                            ) : (
                                <Row gutter={[24, 24]}>
                                    <Col xs={24} md={12}>
                                        <Typography.Text type="secondary">Valor</Typography.Text>
                                        <div>
                                            <Tag color="green">{`R$ ${Number(link.valor).toFixed(2).replace('.', ',')}`}</Tag>
                                        </div>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Typography.Text type="secondary">Status</Typography.Text>
                                        <div>
                                            <Tag color={statusColor}>{link.status === 'ATIVO' ? 'Ativo' : link.status}</Tag>
                                        </div>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Typography.Text type="secondary">Código Único</Typography.Text>
                                        <div className="spa-pix-detail-code">
                                            <Typography.Text code>{link.codigo_unico}</Typography.Text>
                                            <Button
                                                icon={<CopyOutlined />}
                                                onClick={() => copyText(link.codigo_unico)}
                                                title="Copiar código"
                                                aria-label="Copiar código"
                                            />
                                        </div>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Typography.Text type="secondary">ID</Typography.Text>
                                        <div>
                                            <Typography.Text strong>{link.id ?? '-'}</Typography.Text>
                                        </div>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Typography.Text type="secondary">Criado em</Typography.Text>
                                        <div>
                                            <Typography.Text>{createdAt}</Typography.Text>
                                        </div>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Typography.Text type="secondary">Expira em</Typography.Text>
                                        <div>
                                            <Typography.Text>{expirationDate}</Typography.Text>
                                        </div>
                                    </Col>
                                </Row>
                            )}
                        </Card>

                        <Card className="spa-pix-detail-link-card" title="Link de Pagamento PIX" bordered={false}>
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 5 }} />
                            ) : (
                                <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                    <div className="spa-pix-detail-empty-code">
                                        <QrcodeOutlined />
                                        <Typography.Text type="secondary">
                                            QR Code será gerado quando o cliente acessar o link.
                                        </Typography.Text>
                                        <Typography.Text type="secondary">
                                            Compartilhe este link com seus clientes para pagamentos PIX.
                                        </Typography.Text>
                                    </div>

                                    <div className="spa-pix-detail-url-box">
                                        <Input value={link.url_completa} readOnly size="large" />
                                        <Button
                                            icon={<CopyOutlined />}
                                            onClick={() => copyText(link.url_completa)}
                                            title="Copiar link"
                                            aria-label="Copiar link"
                                        />
                                    </div>

                                    <Space wrap>
                                        <Button type="primary" icon={<ThunderboltOutlined />} onClick={() => window.open(link.url_completa, '_blank', 'noopener,noreferrer')}>
                                            Testar Link
                                        </Button>
                                        <Button
                                            icon={link.status === 'ATIVO' ? <PauseOutlined /> : <PlayCircleOutlined />}
                                            onClick={() => updateStatus(link.status === 'ATIVO' ? 'INATIVO' : 'ATIVO')}
                                            className={link.status === 'ATIVO' ? 'spa-pix-link-button-warning' : 'spa-pix-link-button-success'}
                                        >
                                            {link.status === 'ATIVO' ? 'Desativar' : 'Ativar'}
                                        </Button>
                                        <Button danger icon={<DeleteOutlined />} onClick={deleteLink}>
                                            Excluir
                                        </Button>
                                    </Space>
                                </Space>
                            )}
                        </Card>

                        <Card className="spa-pix-detail-config-card" title="Configurações de Pagamento PIX" bordered={false}>
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 3 }} />
                            ) : (
                                <Row gutter={[24, 16]}>
                                    <Col xs={24} md={12}>
                                        <Typography.Text type="secondary">Taxas</Typography.Text>
                                        <div>
                                            <Tag color="green">{taxLabel}</Tag>
                                        </div>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Typography.Text type="secondary">Tipo de Pagamento</Typography.Text>
                                        <div>
                                            <Tag color="blue">{paymentTypeLabel}</Tag>
                                        </div>
                                    </Col>
                                </Row>
                            )}
                        </Card>

                        <Card className="spa-pix-detail-client-card" title="Dados do Cliente" bordered={false}>
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 4 }} />
                            ) : hasClientData ? (
                                <Row gutter={[24, 20]}>
                                    {[
                                        ['Nome', clientData.nome],
                                        ['Sobrenome', clientData.sobrenome],
                                        ['Email', clientData.email],
                                        ['Telefone', clientData.telefone],
                                        ['Documento', clientData.documento],
                                    ].map(([label, value]) => (
                                        <Col xs={24} md={12} key={label}>
                                            <Typography.Text type="secondary">{label}</Typography.Text>
                                            <div className="spa-pix-client-detail-item">
                                                <Typography.Text strong>{value}</Typography.Text>
                                            </div>
                                        </Col>
                                    ))}
                                </Row>
                            ) : (
                                <Empty description="Nenhum dado de cliente preenchido para este link." />
                            )}
                        </Card>
                    </Space>
                </Card>
            </Col>

            <Col xs={24} xl={8}>
                <Card className="spa-quick-view-card spa-pix-detail-side-card" bordered={false}>
                    {loading ? (
                        <Skeleton active paragraph={{ rows: 5 }} />
                    ) : (
                        <Space direction="vertical" size={16} style={{ width: '100%' }}>
                            <div className="spa-pix-detail-side-hero">
                                <LinkOutlined className="spa-pix-detail-side-icon" />
                                <Typography.Title level={4} className="spa-pix-detail-side-title">
                                    Link de Pagamento PIX
                                </Typography.Title>
                            </div>

                            <Typography.Text type="secondary">Código Único</Typography.Text>
                            <div>
                                <Typography.Text code>{link.codigo_unico}</Typography.Text>
                            </div>

                            <Typography.Text type="secondary">Criado em</Typography.Text>
                            <div>
                                <Typography.Text>{createdAt}</Typography.Text>
                            </div>

                            <Typography.Text type="secondary">Expira em</Typography.Text>
                            <div>
                                <Typography.Text>{expirationDate}</Typography.Text>
                            </div>

                            <div className="spa-pix-detail-empty-code">
                                <QrcodeOutlined />
                                <Typography.Text type="secondary">
                                    O QR Code ficará disponível para o cliente no momento do acesso.
                                </Typography.Text>
                            </div>
                        </Space>
                    )}
                </Card>
            </Col>
        </Row>
    );
}

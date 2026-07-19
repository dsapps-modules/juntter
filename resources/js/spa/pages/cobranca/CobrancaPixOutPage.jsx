import {
    CheckCircleOutlined,
    EditOutlined,
    FileTextOutlined,
    SendOutlined,
} from '@ant-design/icons';
import {
    Alert,
    Button,
    Card,
    Col,
    Descriptions,
    Form,
    Input,
    Modal,
    Row,
    Select,
    Skeleton,
    Space,
    Typography,
    message,
} from 'antd';
import { useEffect, useMemo, useState } from 'react';
import MoneyInputField from '../../components/form/MoneyInputField';

const defaultOverview = {
    seller_name: 'Vendedor',
    seller_email: '',
    establishment: null,
    balance: {
        available: 0,
        available_label: 'R$ 0,00',
        blocked: 0,
        blocked_label: 'R$ 0,00',
        total: 0,
        total_label: 'R$ 0,00',
    },
    fee: {
        cents: 0,
        label: 'R$ 0,00',
    },
    available_after_fee: {
        cents: 0,
        label: 'R$ 0,00',
    },
    electronic_signature: {
        configured: false,
        pending: false,
        verified_at: null,
        code_sent_at: null,
        code_expires_at: null,
    },
    pix_key_types: [],
    message: null,
};

const defaultFormValues = {
    amount: '',
    pix_key_type: 'PHONE',
    pix_key: '',
    description: '',
    electronic_signature: '',
};

const defaultSignatureFormValues = {
    electronic_signature: '',
    electronic_signature_confirmation: '',
    verification_code: '',
};

const defaultTransactionState = {
    review: null,
    request: null,
    receipt_url: '',
};

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function buildJsonHeaders() {
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
    };
}

export default function CobrancaPixOutPage() {
    const [form] = Form.useForm();
    const [signatureForm] = Form.useForm();
    const [transactionForm] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [signatureModalOpen, setSignatureModalOpen] = useState(false);
    const [signatureStep, setSignatureStep] = useState('form');
    const [signatureSubmitting, setSignatureSubmitting] = useState(false);
    const [transactionModalOpen, setTransactionModalOpen] = useState(false);
    const [transactionStage, setTransactionStage] = useState('review');
    const [transactionSubmitting, setTransactionSubmitting] = useState(false);
    const [overview, setOverview] = useState(defaultOverview);
    const [feedback, setFeedback] = useState(null);
    const [transactionState, setTransactionState] = useState(defaultTransactionState);

    const watchedPixKeyType = Form.useWatch('pix_key_type', form) ?? 'PHONE';
    const pixKeyPlaceholders = {
        PHONE: 'celular',
        CPF: 'CPF',
        EMAIL: 'e-mail',
        CNPJ: 'CNPJ',
        RANDOM: 'chave aleatória',
    };

    useEffect(() => {
        const controller = new AbortController();

        async function loadOverview() {
            setLoading(true);

            try {
                const response = await fetch('/api/spa/cobranca/pix-out', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar a página de envio de PIX.');
                }

                const data = await response.json();
                setOverview((current) => ({
                    ...current,
                    ...data,
                    seller_email: data.seller_email ?? current.seller_email,
                    balance: data.balance ?? current.balance,
                    fee: data.fee ?? current.fee,
                    available_after_fee: data.available_after_fee ?? current.available_after_fee,
                    electronic_signature: data.electronic_signature ?? current.electronic_signature,
                    pix_key_types: data.pix_key_types ?? [],
                }));
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setFeedback({ type: 'error', message: error.message || 'Falha ao carregar a página.' });
                }
            } finally {
                setLoading(false);
            }
        }

        loadOverview();

        return () => controller.abort();
    }, []);

    async function refreshOverview() {
        const response = await fetch('/api/spa/cobranca/pix-out', {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Não foi possível atualizar os dados do envio de PIX.');
        }

        const data = await response.json();
        setOverview((current) => ({
            ...current,
            ...data,
            seller_email: data.seller_email ?? current.seller_email,
            balance: data.balance ?? current.balance,
            fee: data.fee ?? current.fee,
            available_after_fee: data.available_after_fee ?? current.available_after_fee,
            electronic_signature: data.electronic_signature ?? current.electronic_signature,
            pix_key_types: data.pix_key_types ?? current.pix_key_types,
        }));
    }

    function openSignatureModal() {
        setSignatureModalOpen(true);
        setSignatureStep('form');
        signatureForm.resetFields();
    }

    function closeSignatureModal() {
        setSignatureModalOpen(false);
        setSignatureStep('form');
        signatureForm.resetFields();
    }

    async function handleSignatureFormSubmit(values) {
        setSignatureSubmitting(true);
        setFeedback(null);

        try {
            const response = await fetch('/api/spa/cobranca/pix-out/assinatura-eletronica', {
                method: 'POST',
                headers: buildJsonHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify(values),
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Não foi possível solicitar o código da assinatura eletrônica.');
            }

            setSignatureStep('code');
            signatureForm.setFieldsValue({ verification_code: '' });
            const sellerEmail = payload.seller_email || overview.seller_email || 'o e-mail cadastrado';
            message.success(payload.message || `Código enviado para ${sellerEmail}.`);
        } catch (error) {
            setFeedback({ type: 'error', message: error.message || 'Falha ao atualizar a assinatura eletrônica.' });
        } finally {
            setSignatureSubmitting(false);
        }
    }

    async function handleSignatureCodeSubmit(values) {
        setSignatureSubmitting(true);
        setFeedback(null);

        try {
            const response = await fetch('/api/spa/cobranca/pix-out/assinatura-eletronica/confirmar', {
                method: 'POST',
                headers: buildJsonHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify(values),
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Não foi possível validar a assinatura eletrônica.');
            }

            message.success(payload.message || 'Assinatura eletrônica atualizada.');
            closeSignatureModal();
            await refreshOverview();
        } catch (error) {
            setFeedback({ type: 'error', message: error.message || 'Falha ao confirmar a assinatura eletrônica.' });
        } finally {
            setSignatureSubmitting(false);
        }
    }

    async function handleStartTransaction(values) {
        setSubmitting(true);
        setFeedback(null);

        try {
            const response = await fetch('/api/spa/cobranca/pix-out', {
                method: 'POST',
                headers: buildJsonHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify(values),
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Não foi possível iniciar o envio do PIX.');
            }

            setTransactionState({
                review: payload.review ?? null,
                request: payload.payout_request ?? null,
                receipt_url: payload.payout_request?.receipt_url ?? '',
            });
            setTransactionStage('review');
            setTransactionModalOpen(true);
            transactionForm.resetFields();
            message.success(payload.message || 'Solicitação iniciada.');
            await refreshOverview();
        } catch (error) {
            setFeedback({ type: 'error', message: error.message || 'Falha ao iniciar o envio do PIX.' });
        } finally {
            setSubmitting(false);
        }
    }

    async function handleConfirmTransaction(values) {
        if (!transactionState.request) {
            return;
        }

        setTransactionSubmitting(true);
        setFeedback(null);

        try {
            const response = await fetch(`/api/spa/cobranca/pix-out/${transactionState.request.id}/confirmar`, {
                method: 'POST',
                headers: buildJsonHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify(values),
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Não foi possível confirmar o PIX.');
            }

            setTransactionState((current) => ({
                ...current,
                request: payload.payout_request ?? current.request,
                receipt_url: payload.payout_request?.receipt_url ?? current.receipt_url,
            }));
            setTransactionStage('success');
            message.success(payload.message || 'PIX confirmado com sucesso.');
            await refreshOverview();
        } catch (error) {
            setFeedback({ type: 'error', message: error.message || 'Falha ao confirmar o PIX.' });
        } finally {
            setTransactionSubmitting(false);
        }
    }

    function handleOpenReceipt() {
        if (transactionState.receipt_url) {
            window.open(transactionState.receipt_url, '_blank', 'noopener,noreferrer');
            return;
        }

        message.info('Comprovante ainda não disponível para esta transação.');
    }

    function handleCloseTransactionModal() {
        setTransactionModalOpen(false);
        setTransactionStage('review');
        setTransactionState(defaultTransactionState);
        transactionForm.resetFields();
    }

    const keyTypeOptions = useMemo(() => (
        (overview.pix_key_types ?? []).map((item) => ({
            value: item.value,
            label: item.label,
        }))
    ), [overview.pix_key_types]);

    return (
        <Space direction="vertical" size={20} className="spa-cobranca-pixout-page" style={{ width: '100%' }}>
            <div className="spa-pix-page-header">
                <SendOutlined className="spa-page-hero-icon" />
            </div>

            {feedback ? <Alert type={feedback.type} showIcon message={feedback.message} /> : null}

            {loading ? (
                <Card>
                    <Skeleton active paragraph={{ rows: 8 }} />
                </Card>
            ) : (
                <>
                    <Card bordered={false} className="spa-pix-summary-card spa-pix-summary-card-full">
                        <Row gutter={[20, 20]}>
                            <Col xs={24} md={8}>
                                <Typography.Text className="spa-brand-kicker">Saldo</Typography.Text>
                                <Typography.Title level={2} style={{ marginBottom: 0 }}>
                                    {overview.balance.available_label}
                                </Typography.Title>
                            </Col>
                            <Col xs={24} md={8}>
                                <Typography.Text className="spa-brand-kicker">Disponível para saque</Typography.Text>
                                <Typography.Title level={2} style={{ marginBottom: 0, color: '#1f2a88' }}>
                                    {overview.available_after_fee.label}
                                </Typography.Title>
                            </Col>
                        </Row>
                    </Card>

                    <div className="spa-pix-signature-toolbar" style={{ display: 'flex', justifyContent: 'flex-end', gap: 12 }}>
                        <Button
                            type="primary"
                            icon={<EditOutlined />}
                            onClick={openSignatureModal}
                        >
                            Cadastrar/atualizar assinatura eletrônica
                        </Button>
                    </div>

                    <Card title="Dados do envio" className="spa-table-card spa-pix-form-card">
                        <Form
                            form={form}
                            layout="vertical"
                            initialValues={defaultFormValues}
                            onFinish={handleStartTransaction}
                            disabled={submitting}
                        >
                            <Row gutter={16}>
                                <Col xs={24} md={8}>
                                    <Form.Item
                                        name="pix_key_type"
                                        label="Tipo de chave*"
                                        rules={[{ required: true, message: 'Selecione o tipo da chave.' }]}
                                    >
                                        <Select
                                            options={keyTypeOptions}
                                            placeholder="Selecione o tipo"
                                        />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={16}>
                                    <Form.Item
                                        name="pix_key"
                                        label="Chave Pix*"
                                        rules={[{ required: true, message: 'Informe a chave PIX.' }]}
                                    >
                                        <Input placeholder={`Informe a ${pixKeyPlaceholders[watchedPixKeyType] ?? 'chave PIX'}`} />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Row gutter={16}>
                                <Col xs={24} md={8}>
                                    <Form.Item
                                        name="amount"
                                        label="Valor a transferir*"
                                        rules={[{ required: true, message: 'Informe o valor.' }]}
                                    >
                                        <MoneyInputField placeholder="R$ 0,00" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item
                                        name="description"
                                        label="Informações adicionais"
                                    >
                                        <Input placeholder="Opcional" maxLength={140} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={8}>
                                    <Form.Item
                                        name="electronic_signature"
                                        label="Assinatura eletrônica*"
                                        rules={[{ required: true, message: 'Informe a assinatura eletrônica.' }]}
                                    >
                                        <Input.Password placeholder="Digite sua assinatura eletrônica" autoComplete="off" />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <div
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'space-between',
                                    gap: 16,
                                    flexWrap: 'wrap',
                                    marginTop: 24,
                                }}
                            >
                                <Space wrap>
                                    <Button
                                        type="primary"
                                        htmlType="submit"
                                        icon={<SendOutlined />}
                                        loading={submitting}
                                    >
                                        Iniciar transação
                                    </Button>
                                    <Button
                                        onClick={() => form.resetFields()}
                                        disabled={submitting}
                                    >
                                        Limpar
                                    </Button>
                                </Space>

                                <Space size={6} align="center" wrap>
                                    <Typography.Text className="spa-brand-kicker">
                                        Taxa de Transferência:
                                    </Typography.Text>
                                    <Typography.Text className="spa-brand-kicker" style={{ color: '#d14343' }}>
                                        {overview.fee.label}
                                    </Typography.Text>
                                </Space>
                            </div>
                        </Form>
                    </Card>
                </>
            )}

            <Modal
                title="Alterar assinatura eletrônica"
                open={signatureModalOpen}
                onCancel={closeSignatureModal}
                footer={null}
                destroyOnClose
                width={650}
            >
                <Space direction="vertical" size={16} style={{ width: '100%' }}>
                    <Typography.Paragraph style={{ marginBottom: 0 }}>
                        A nova assinatura eletrônica será protegida por um código enviado para o seu e-mail.
                    </Typography.Paragraph>

                    <Form
                        form={signatureForm}
                        layout="vertical"
                        initialValues={defaultSignatureFormValues}
                        onFinish={signatureStep === 'code' ? handleSignatureCodeSubmit : handleSignatureFormSubmit}
                        disabled={signatureSubmitting}
                    >
                        {signatureStep === 'form' ? (
                            <>
                                <Form.Item
                                    name="electronic_signature"
                                    label="Nova assinatura eletrônica *"
                                    rules={[{ required: true, message: 'Informe a nova assinatura eletrônica.' }]}
                                >
                                    <Input.Password placeholder="Digite sua nova assinatura eletrônica" autoComplete="off" />
                                </Form.Item>
                                <Form.Item
                                    name="electronic_signature_confirmation"
                                    label="Confirme a assinatura eletrônica *"
                                    rules={[{ required: true, message: 'Confirme a assinatura eletrônica.' }]}
                                >
                                    <Input.Password placeholder="Confirme a nova assinatura eletrônica" autoComplete="off" />
                                </Form.Item>
                            </>
                        ) : (
                            <>
                                <Alert
                                    type="info"
                                    showIcon
                                    message={`Informe o código enviado para ${overview.seller_email || 'o e-mail cadastrado'} para salvar a nova assinatura eletrônica.`}
                                />
                                <Form.Item
                                    name="verification_code"
                                    label="Código de verificação *"
                                    rules={[{ required: true, message: 'Informe o código de verificação.' }]}
                                >
                                    <Input placeholder="Digite o código de 6 dígitos" inputMode="numeric" maxLength={6} />
                                </Form.Item>
                            </>
                        )}

                        <Space wrap>
                            {signatureStep === 'form' ? (
                                <Button
                                    type="primary"
                                    htmlType="submit"
                                    loading={signatureSubmitting}
                                >
                                    Enviar código
                                </Button>
                            ) : (
                                <Button
                                    type="primary"
                                    htmlType="submit"
                                    loading={signatureSubmitting}
                                    icon={<CheckCircleOutlined />}
                                >
                                    Confirmar código
                                </Button>
                            )}
                            <Button onClick={closeSignatureModal} disabled={signatureSubmitting}>
                                Cancelar
                            </Button>
                        </Space>
                    </Form>
                </Space>
            </Modal>

            <Modal
                title="Confirmar pagamento PIX"
                open={transactionModalOpen}
                onCancel={handleCloseTransactionModal}
                footer={null}
                destroyOnClose
                width={760}
            >
                {transactionStage === 'review' ? (
                    <Space direction="vertical" size={16} style={{ width: '100%' }}>
                        {transactionState.review ? (
                            <>
                                <Alert
                                    type="info"
                                    showIcon
                                    message={`Realmente deseja fazer um Pix no valor de ${transactionState.review.amount_label}?`}
                                    description="Confira os dados antes de confirmar."
                                />
                                <Descriptions column={1} bordered size="small" title="Dados do recebedor">
                                    <Descriptions.Item label="Nome">
                                        {transactionState.review.receiver?.name ?? 'Não informado'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Documento">
                                        {transactionState.review.receiver?.document ?? 'Não informado'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Instituição">
                                        {transactionState.review.receiver?.institution ?? 'Não informado'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Chave PIX">
                                        {transactionState.review.receiver?.pix_key ?? 'Não informado'}
                                    </Descriptions.Item>
                                </Descriptions>

                                <Descriptions column={1} bordered size="small" title="Dados do devedor">
                                    <Descriptions.Item label="Nome">
                                        {transactionState.review.debtor?.name ?? 'Não informado'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Documento">
                                        {transactionState.review.debtor?.document ?? 'Não informado'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Instituição">
                                        {transactionState.review.debtor?.institution ?? 'Não informado'}
                                    </Descriptions.Item>
                                </Descriptions>

                                <Alert
                                    type="warning"
                                    showIcon
                                    message={`Este valor será descontado do saldo disponível. Taxa: ${transactionState.review.fee_label}. Valor disponível: ${transactionState.review.available_after_fee_label}.`}
                                />

                                <Form form={transactionForm} layout="vertical" onFinish={handleConfirmTransaction}>
                                    <Form.Item
                                        name="verification_code"
                                        label="Código de verificação recebido por e-mail *"
                                        rules={[{ required: true, message: 'Informe o código de verificação.' }]}
                                    >
                                        <Input placeholder="Digite o código de 6 dígitos" inputMode="numeric" maxLength={6} />
                                    </Form.Item>

                                    <Space wrap>
                                        <Button
                                            type="primary"
                                            htmlType="submit"
                                            loading={transactionSubmitting}
                                            icon={<CheckCircleOutlined />}
                                        >
                                            Sim, confirmar
                                        </Button>
                                        <Button onClick={handleCloseTransactionModal} disabled={transactionSubmitting}>
                                            Cancelar
                                        </Button>
                                    </Space>
                                </Form>
                            </>
                        ) : null}
                    </Space>
                ) : (
                    <Space direction="vertical" size={16} style={{ width: '100%' }}>
                        <Alert
                            type="success"
                            showIcon
                            message="Pix confirmado com sucesso, aguardando efetivação pelo banco."
                        />
                        <Space wrap>
                            <Button
                                type="primary"
                                icon={<FileTextOutlined />}
                                onClick={handleOpenReceipt}
                            >
                                Ver comprovante
                            </Button>
                            <Button onClick={handleCloseTransactionModal}>
                                Fazer outra transação
                            </Button>
                        </Space>
                    </Space>
                )}
            </Modal>
        </Space>
    );
}

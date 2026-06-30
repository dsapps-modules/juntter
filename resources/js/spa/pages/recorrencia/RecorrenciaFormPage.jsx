import {
    BankOutlined,
    CreditCardOutlined,
    MailOutlined,
    MessageOutlined,
    QrcodeOutlined,
} from '@ant-design/icons';
import {
    Button,
    Card,
    Col,
    DatePicker,
    Divider,
    Form,
    Input,
    InputNumber,
    Row,
    Select,
    Space,
    Switch,
    Typography,
    message,
} from 'antd';
import dayjs from 'dayjs';
import { useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import MoneyInputField from '../../components/form/MoneyInputField';
import { formatDocument, isValidDocument } from '../../documentValidation';

const frequencyOptions = [
    { label: 'Semanal', value: 'SEMANAL' },
    { label: 'Quinzenal', value: 'QUINZENAL' },
    { label: 'Mensal', value: 'MENSAL' },
    { label: 'Bimestral', value: 'BIMESTRAL' },
    { label: 'Trimestral', value: 'TRIMESTRAL' },
    { label: 'Anual', value: 'ANUAL' },
];

const paymentTypeMeta = {
    PIX: {
        title: 'Cobrança recorrente via Pix',
        icon: <QrcodeOutlined />,
        accent: 'gold',
    },
    BOLETO: {
        title: 'Cobrança recorrente via Boleto',
        icon: <BankOutlined />,
        accent: 'volcano',
    },
    CARTAO: {
        title: 'Cobrança recorrente via Cartão de Crédito',
        icon: <CreditCardOutlined />,
        accent: 'blue',
    },
};

const initialValuesByType = {
    PIX: {
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        customer_document: '',
        amount: 'R$ 0,00',
        frequency: 'MENSAL',
        charge_day: 10,
        start_date: dayjs().add(1, 'day'),
        end_date: null,
        payment_link_url: '',
        payment_type: 'PIX',
        send_via_email: true,
        recipient_email: '',
        email_subject: '',
        email_message: '',
        send_via_whatsapp: false,
        whatsapp_number: '',
        pix_key: '',
        pix_copy_paste: '',
        pix_expiration_minutes: 2,
    },
    BOLETO: {
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        customer_document: '',
        amount: 'R$ 0,00',
        frequency: 'MENSAL',
        charge_day: 10,
        start_date: dayjs().add(1, 'day'),
        end_date: null,
        payment_link_url: '',
        payment_type: 'BOLETO',
        send_via_email: true,
        recipient_email: '',
        email_subject: '',
        email_message: '',
        send_via_whatsapp: false,
        whatsapp_number: '',
        boleto_due_days: 3,
        boleto_instructions: '',
        boleto_interest: '0,00',
        boleto_fine: '0,00',
    },
    CARTAO: {
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        customer_document: '',
        amount: 'R$ 0,00',
        frequency: 'MENSAL',
        charge_day: 10,
        start_date: dayjs().add(1, 'day'),
        end_date: null,
        payment_link_url: '',
        payment_type: 'CARTAO',
        send_via_email: true,
        recipient_email: '',
        email_subject: '',
        email_message: '',
        send_via_whatsapp: false,
        whatsapp_number: '',
        card_installments: 1,
        card_descriptor: '',
        card_capture_mode: 'AUTO',
    },
};

function normalizeDigits(value) {
    return String(value ?? '').replace(/\D+/g, '');
}

function formatPhone(value) {
    const digits = normalizeDigits(value).slice(0, 11);

    if (!digits) {
        return '';
    }

    if (digits.length <= 2) {
        return `(${digits}`;
    }

    if (digits.length <= 6) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
    }

    if (digits.length <= 10) {
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
    }

    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
}

function emailValidator(_, value) {
    if (!value) {
        return Promise.resolve();
    }

    return /.+@.+\..+/.test(value)
        ? Promise.resolve()
        : Promise.reject(new Error('Informe um e-mail válido.'));
}

function documentValidator(_, value) {
    if (!value) {
        return Promise.resolve();
    }

    return isValidDocument(value)
        ? Promise.resolve()
        : Promise.reject(new Error('O documento informado é inválido.'));
}

function toIsoDate(value) {
    if (!value) {
        return null;
    }

    return dayjs.isDayjs(value) ? value.format('YYYY-MM-DD') : value;
}

function buildRequestPayload(values, paymentType) {
    return {
        send_immediately: values.send_immediately,
        send_via_email: values.send_via_email,
        send_via_whatsapp: values.send_via_whatsapp,
        customer_name: values.customer_name,
        customer_email: values.customer_email,
        customer_phone: values.customer_phone,
        customer_document: values.customer_document,
        recipient_email: values.recipient_email,
        recipient_name: values.customer_name,
        email_subject: values.email_subject,
        payment_type: paymentType,
        amount: values.amount,
        frequency: values.frequency,
        charge_day: values.charge_day,
        start_date: toIsoDate(values.start_date),
        end_date: toIsoDate(values.end_date),
        payment_link_url: values.payment_link_url,
        email_message: values.email_message,
        whatsapp_number: values.whatsapp_number,
        pix_key: values.pix_key,
        pix_copy_paste: values.pix_copy_paste,
        pix_expiration_minutes: paymentType === 'PIX'
            ? Number(values.pix_expiration_minutes ?? 0) * 1440
            : values.pix_expiration_minutes,
        boleto_due_days: values.boleto_due_days,
        boleto_instructions: values.boleto_instructions,
        boleto_interest: values.boleto_interest,
        boleto_fine: values.boleto_fine,
        card_installments: values.card_installments,
        card_descriptor: values.card_descriptor,
        card_capture_mode: values.card_capture_mode,
    };
}

export default function RecorrenciaFormPage({
    paymentType,
    selectorPath,
}) {
    const navigate = useNavigate();
    const [form] = Form.useForm();
    const [saving, setSaving] = useState(false);
    const submitModeRef = useRef('prepare');
    const sendViaEmail = Form.useWatch('send_via_email', form);

    const meta = paymentTypeMeta[paymentType] ?? paymentTypeMeta.PIX;
    const initialValues = useMemo(() => initialValuesByType[paymentType] ?? initialValuesByType.PIX, [paymentType]);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            if (submitModeRef.current === 'send' && values.send_via_email) {
                const response = await fetch('/api/spa/recorrencia/email', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(buildRequestPayload(values, paymentType)),
                });

                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Não foi possível preparar o envio por e-mail.');
                }

                message.success(payload.message || 'Recorrência salva e link enviado com sucesso.');
            } else {
                message.success('Dados da recorrência preparados.');
            }

            form.setFieldsValue(values);
        } catch (error) {
            message.error(error.message || 'Falha ao preparar a recorrência.');
        } finally {
            setSaving(false);
        }
    }

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} xl={24}>
                <Card
                    title={(
                        <Space size={10}>
                            {meta.icon}
                            <span>{meta.title}</span>
                        </Space>
                    )}
                    extra={<Button onClick={() => navigate(selectorPath)}>Voltar</Button>}
                >

                    <Form
                        form={form}
                        layout="vertical"
                        initialValues={initialValues}
                        onFinish={handleSubmit}
                    >
                        <Form.Item name="send_immediately" initialValue={false} hidden>
                            <Input type="hidden" />
                        </Form.Item>
                        <Row gutter={16}>
                            <Col xs={24} md={12}>
                                <Form.Item
                                    label="Nome do cliente"
                                    name="customer_name"
                                    rules={[{ required: true, message: 'Informe o nome do cliente.' }]}
                                >
                                    <Input placeholder="Nome completo" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item
                                    label="Documento"
                                    name="customer_document"
                                    normalize={formatDocument}
                                    rules={[{ validator: documentValidator }]}
                                >
                                    <Input placeholder="CPF ou CNPJ" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={16}>
                            <Col xs={24} md={12}>
                                <Form.Item
                                    label="E-mail do cliente"
                                    name="customer_email"
                                    rules={[
                                        { required: true, message: 'Informe o e-mail do cliente.' },
                                        { validator: emailValidator },
                                    ]}
                                >
                                    <Input prefix={<MailOutlined />} placeholder="cliente@exemplo.com" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item
                                    label="WhatsApp do cliente"
                                    name="customer_phone"
                                    normalize={formatPhone}
                                >
                                    <Input prefix={<MessageOutlined />} placeholder="(00) 00000-0000" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={16}>
                            <Col xs={24} md={12}>
                                <Form.Item
                                    label="Valor"
                                    name="amount"
                                    rules={[{ required: true, message: 'Informe o valor da cobrança.' }]}
                                >
                                    <MoneyInputField />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item
                                    label="Periodicidade"
                                    name="frequency"
                                    rules={[{ required: true, message: 'Selecione a periodicidade.' }]}
                                >
                                    <Select options={frequencyOptions} />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={16}>
                            <Col xs={24} md={8}>
                                <Form.Item
                                    label="Dia da cobrança"
                                    name="charge_day"
                                    rules={[{ required: true, message: 'Informe o dia da cobrança.' }]}
                                >
                                    <InputNumber min={1} max={31} className="w-full" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item
                                    label="Primeira cobrança"
                                    name="start_date"
                                    rules={[{ required: true, message: 'Informe a primeira cobrança.' }]}
                                >
                                    <DatePicker className="w-full" format="DD/MM/YYYY" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={8}>
                                <Form.Item label="Término" name="end_date">
                                    <DatePicker className="w-full" format="DD/MM/YYYY" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Form.Item
                            label="Link público da cobrança"
                            name="payment_link_url"
                            rules={[
                                { required: true, message: 'Informe o link de pagamento.' },
                                { type: 'url', message: 'Informe uma URL válida.' },
                            ]}
                        >
                            <Input placeholder="https://..." />
                        </Form.Item>

                        <Divider />

                        {paymentType === 'PIX' ? (
                            <>
                                <Typography.Title level={4}>Dados do Pix</Typography.Title>
                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <Form.Item label="Chave Pix" name="pix_key" rules={[{ required: true, message: 'Informe a chave Pix.' }]}>
                                            <Input placeholder="CPF, e-mail, chave aleatória ou celular" />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Form.Item label="Expiração do QR Code" name="pix_expiration_minutes" rules={[{ required: true }]}>
                                            <InputNumber min={1} max={30} className="w-full" addonAfter="dias" />
                                        </Form.Item>
                                    </Col>
                                </Row>
                                <Form.Item label="Copia e cola" name="pix_copy_paste">
                                    <Input.TextArea rows={4} placeholder="Código Pix copia e cola" />
                                </Form.Item>
                            </>
                        ) : null}

                        {paymentType === 'BOLETO' ? (
                            <>
                                <Typography.Title level={4}>Dados do boleto</Typography.Title>
                                <Row gutter={16}>
                                    <Col xs={24} md={8}>
                                        <Form.Item label="Vencimento em dias" name="boleto_due_days" rules={[{ required: true }]}>
                                            <InputNumber min={1} max={60} className="w-full" addonAfter="dias" />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={8}>
                                        <Form.Item label="Juros" name="boleto_interest">
                                            <MoneyInputField />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={8}>
                                        <Form.Item label="Multa" name="boleto_fine">
                                            <MoneyInputField />
                                        </Form.Item>
                                    </Col>
                                </Row>
                                <Form.Item label="Instruções do boleto" name="boleto_instructions">
                                    <Input.TextArea rows={4} placeholder="Mensagem exibida no boleto" />
                                </Form.Item>
                            </>
                        ) : null}

                        {paymentType === 'CARTAO' ? (
                            <>
                                <Typography.Title level={4}>Dados do cartão</Typography.Title>
                                <Row gutter={16}>
                                    <Col xs={24} md={12}>
                                        <Form.Item label="Parcelas" name="card_installments" rules={[{ required: true }]}>
                                            <InputNumber min={1} max={18} className="w-full" />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Form.Item label="Modo de captura" name="card_capture_mode" rules={[{ required: true }]}>
                                            <Select
                                                options={[
                                                    { label: 'Automática', value: 'AUTO' },
                                                    { label: 'Manual', value: 'MANUAL' },
                                                ]}
                                            />
                                        </Form.Item>
                                    </Col>
                                </Row>
                                <Form.Item label="Descrição na fatura" name="card_descriptor">
                                    <Input placeholder="Nome que aparecerá na fatura" />
                                </Form.Item>
                            </>
                        ) : null}

                        <Divider />

                        <Typography.Title level={4}>Envio por e-mail e WhatsApp</Typography.Title>
                        <Row gutter={16}>
                            <Col xs={24} md={12}>
                                <Form.Item label="Enviar por e-mail" name="send_via_email" valuePropName="checked">
                                    <Switch />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item label="Enviar por WhatsApp" name="send_via_whatsapp" valuePropName="checked">
                                    <Switch />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Row gutter={16}>
                            <Col xs={24} md={12}>
                                <Form.Item
                                    label="E-mail de envio"
                                    name="recipient_email"
                                    rules={[
                                        { required: Boolean(sendViaEmail), message: 'Informe o e-mail de envio.' },
                                        { validator: emailValidator },
                                    ]}
                                >
                                    <Input prefix={<MailOutlined />} placeholder="destinatario@exemplo.com" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item label="WhatsApp de envio" name="whatsapp_number" normalize={formatPhone}>
                                    <Input prefix={<MessageOutlined />} placeholder="(00) 00000-0000" />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Form.Item
                            label="Assunto do e-mail"
                            name="email_subject"
                            rules={[{ required: Boolean(sendViaEmail), message: 'Informe o assunto do e-mail.' }]}
                        >
                            <Input />
                        </Form.Item>

                        <Form.Item
                            label="Mensagem do e-mail"
                            name="email_message"
                            rules={[{ required: Boolean(sendViaEmail), message: 'Informe a mensagem do e-mail.' }]}
                        >
                            <Input.TextArea rows={5} />
                        </Form.Item>

                        <Space wrap>
                            <Button
                                type="primary"
                                htmlType="submit"
                                loading={saving}
                                onClick={() => {
                                    submitModeRef.current = 'prepare';
                                    form.setFieldsValue({ send_immediately: false });
                                }}
                            >
                                Preparar cobrança
                            </Button>
                            <Button
                                type="primary"
                                htmlType="submit"
                                loading={saving}
                                onClick={() => {
                                    submitModeRef.current = 'send';
                                    form.setFieldsValue({ send_immediately: true });
                                }}
                            >
                                Enviar
                            </Button>
                            <Button onClick={() => navigate(selectorPath)}>Voltar</Button>
                        </Space>
                    </Form>
                </Card>
            </Col>
        </Row>
    );
}






import { MailOutlined, LockOutlined, SaveOutlined, UploadOutlined, UserOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, Divider, Input, Row, Space, Tag, Typography } from 'antd';
import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';

const defaultProfile = {
    name: '',
    email: '',
    avatar_url: '',
    company_logo_url: '',
    nivel_acesso: '',
    nivel_label: '',
    verified: false,
    email_verified_at: '',
    created_at: '',
    must_change_password: false,
    vendedor: {
        status: '',
        sub_nivel: '',
        estabelecimento_id: '',
    },
};

function formatLabel(value, fallback) {
    return value || fallback;
}

export default function ProfilePage() {
    const [loading, setLoading] = useState(true);
    const [savingProfile, setSavingProfile] = useState(false);
    const [savingLogo, setSavingLogo] = useState(false);
    const [savingPassword, setSavingPassword] = useState(false);
    const [resendingVerification, setResendingVerification] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [logoUploadStatus, setLogoUploadStatus] = useState('');
    const [profile, setProfile] = useState(defaultProfile);
    const [companyLogoFile, setCompanyLogoFile] = useState(null);
    const [companyLogoPreviewUrl, setCompanyLogoPreviewUrl] = useState('');
    const companyLogoInputRef = useRef(null);
    const [profileForm, setProfileForm] = useState({
        name: '',
        email: '',
    });
    const [passwordForm, setPasswordForm] = useState({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    useEffect(() => {
        const controller = new AbortController();

        async function loadProfile() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch('/api/spa/perfil', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar o perfil.');
                }

                const data = await response.json();
                const nextProfile = data.profile ?? defaultProfile;

                setProfile(nextProfile);
                setProfileForm({
                    name: nextProfile.name ?? '',
                    email: nextProfile.email ?? '',
                });
                setCompanyLogoFile(null);
                setLogoUploadStatus('');
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar o perfil.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadProfile();

        return () => controller.abort();
    }, []);

    useEffect(() => {
        if (!companyLogoFile) {
            setCompanyLogoPreviewUrl(profile.avatar_url ?? profile.company_logo_url ?? '');
            return;
        }

        const objectUrl = URL.createObjectURL(companyLogoFile);
        setCompanyLogoPreviewUrl(objectUrl);

        return () => URL.revokeObjectURL(objectUrl);
    }, [companyLogoFile, profile.avatar_url, profile.company_logo_url]);

    async function submitJson(url, method, body) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken ?? '',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const firstError = Object.values(payload.errors ?? {}).flat().shift();
            throw new Error(firstError ?? payload.message ?? 'Operação não concluída.');
        }

        return payload;
    }

    async function submitProfileForm(body) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const formData = new FormData();

        formData.append('_method', 'PATCH');
        formData.append('name', body.name ?? '');
        formData.append('email', body.email ?? '');

        if (body.companyLogoFile) {
            formData.append('company_logo', body.companyLogoFile);
        }

        const response = await fetch('/profile', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken ?? '',
            },
            credentials: 'same-origin',
            body: formData,
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const firstError = Object.values(payload.errors ?? {}).flat().shift();
            throw new Error(firstError ?? payload.message ?? 'Operação não concluída.');
        }

        return payload;
    }

    async function uploadCompanyLogo(file) {
        if (!file) {
            return;
        }

        setSavingLogo(true);
        setError('');
        setLogoUploadStatus('Enviando logotipo...');

        try {
            const payload = await submitProfileForm({
                ...profileForm,
                companyLogoFile: file,
            });

            setSuccess(payload.message ?? 'Perfil atualizado com sucesso.');
            setProfile((current) => ({
                ...current,
                ...profileForm,
                avatar_url: payload.profile?.avatar_url ?? current.avatar_url,
                company_logo_url: payload.profile?.avatar_url ?? current.company_logo_url,
            }));
            setCompanyLogoFile(null);
            setCompanyLogoPreviewUrl(payload.profile?.avatar_url ?? profile.avatar_url ?? '');
            setLogoUploadStatus('Logotipo salvo e já publicado na página pública.');
        } catch (uploadError) {
            setLogoUploadStatus('');
            setError(uploadError.message || 'Falha ao enviar o logotipo.');
        } finally {
            setSavingLogo(false);
        }
    }

    async function handleProfileSubmit(event) {
        event.preventDefault();
        setSavingProfile(true);
        setError('');
        setSuccess('');

        try {
            const payload = await submitProfileForm(profileForm);
            setSuccess(payload.message ?? 'Perfil atualizado com sucesso.');

            setProfile((current) => ({
                ...current,
                ...profileForm,
                avatar_url: payload.profile?.avatar_url ?? current.avatar_url,
                company_logo_url: payload.profile?.avatar_url ?? current.company_logo_url,
            }));
            setLogoUploadStatus(companyLogoFile ? 'Logotipo salvo e já publicado na página pública.' : '');
            setCompanyLogoFile(null);
            setCompanyLogoPreviewUrl(payload.profile?.avatar_url ?? profile.avatar_url ?? '');
        } catch (submitError) {
            setError(submitError.message || 'Falha ao atualizar o perfil.');
        } finally {
            setSavingProfile(false);
        }
    }

    async function handlePasswordSubmit(event) {
        event.preventDefault();
        setSavingPassword(true);
        setError('');
        setSuccess('');

        try {
            const payload = await submitJson('/password', 'PUT', passwordForm);
            setSuccess(payload.message ?? 'Senha atualizada com sucesso.');
            setPasswordForm({
                current_password: '',
                password: '',
                password_confirmation: '',
            });
        } catch (submitError) {
            setError(submitError.message || 'Falha ao alterar a senha.');
        } finally {
            setSavingPassword(false);
        }
    }

    async function resendVerification() {
        setResendingVerification(true);
        setError('');
        setSuccess('');

        try {
            const payload = await submitJson('/email/verification-notification', 'POST', {});
            setSuccess(
                payload.message === 'verification-link-sent'
                    ? 'Novo link de verificação enviado.'
                    : 'Solicitação enviada.',
            );
        } catch (submitError) {
            setError(submitError.message || 'Falha ao reenviar o e-mail de verificação.');
        } finally {
            setResendingVerification(false);
        }
    }

    return (
        <Row gutter={[20, 20]} className="spa-profile-grid">
            <Col xs={24} xl={16}>
                <Card className="spa-hero-card">
                    <Space direction="vertical" size={18} className="spa-hero-stack">
                        {error ? <Alert type="error" showIcon message={error} /> : null}
                        {success ? <Alert type="success" showIcon message={success} /> : null}

                        <Space direction="vertical" size={20} style={{ width: '100%' }}>
                            <div>
                                <Typography.Title level={4} className="spa-section-title">
                                    Dados pessoais
                                </Typography.Title>

                                {loading ? (
                                    <Typography.Text type="secondary">Carregando perfil...</Typography.Text>
                                ) : (
                                    <form onSubmit={handleProfileSubmit}>
                                        <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                            <div>
                                                <Typography.Text strong>Nome</Typography.Text>
                                                <Input
                                                    value={profileForm.name}
                                                    onChange={(event) =>
                                                        setProfileForm((current) => ({ ...current, name: event.target.value }))
                                                    }
                                                    prefix={<UserOutlined />}
                                                    placeholder="Seu nome"
                                                    size="large"
                                                    className="spa-auth-input"
                                                />
                                            </div>

                                            <div>
                                                <Typography.Text strong>E-mail</Typography.Text>
                                                <Input
                                                    value={profileForm.email}
                                                    onChange={(event) =>
                                                        setProfileForm((current) => ({ ...current, email: event.target.value }))
                                                    }
                                                    prefix={<MailOutlined />}
                                                    placeholder="nome@empresa.com"
                                                    size="large"
                                                    className="spa-auth-input"
                                                />
                                            </div>

                                            <Space wrap>
                                                <Tag color={profile.verified ? 'green' : 'gold'}>
                                                    {profile.verified ? 'E-mail verificado' : 'E-mail pendente'}
                                                </Tag>
                                                <Tag color="default">{profile.vendedor?.sub_nivel || profile.nivel_acesso || 'Conta'}</Tag>
                                            </Space>

                                            {!profile.verified ? (
                                                <Alert
                                                    type="warning"
                                                    showIcon
                                                    message="Seu e-mail ainda não foi verificado."
                                                    description="Você pode reenviar o link de verificação sem sair da aplicação."
                                                    action={
                                                        <Button size="small" onClick={resendVerification} loading={resendingVerification}>
                                                            Reenviar link
                                                        </Button>
                                                    }
                                                />
                                            ) : null}

                                            <div className="spa-profile-actions">
                                                <Button type="primary" htmlType="submit" loading={savingProfile} icon={<SaveOutlined />}>
                                                    Salvar dados
                                                </Button>
                                            </div>
                                        </Space>
                                    </form>
                                )}
                            </div>

                            <Divider />

                            <div>
                                <Typography.Title level={4} className="spa-section-title">
                                    Alterar senha
                                </Typography.Title>

                                <form onSubmit={handlePasswordSubmit}>
                                    <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                        <Input.Password
                                            value={passwordForm.current_password}
                                            onChange={(event) =>
                                                setPasswordForm((current) => ({ ...current, current_password: event.target.value }))
                                            }
                                            prefix={<LockOutlined />}
                                            placeholder="Senha atual"
                                            size="large"
                                            className="spa-auth-input"
                                        />

                                        <Input.Password
                                            value={passwordForm.password}
                                            onChange={(event) =>
                                                setPasswordForm((current) => ({ ...current, password: event.target.value }))
                                            }
                                            prefix={<LockOutlined />}
                                            placeholder="Nova senha"
                                            size="large"
                                            className="spa-auth-input"
                                        />

                                        <Input.Password
                                            value={passwordForm.password_confirmation}
                                            onChange={(event) =>
                                                setPasswordForm((current) => ({
                                                    ...current,
                                                    password_confirmation: event.target.value,
                                                }))
                                            }
                                            prefix={<LockOutlined />}
                                            placeholder="Confirmar nova senha"
                                            size="large"
                                            className="spa-auth-input"
                                        />

                                        <div className="spa-profile-actions">
                                            <Button type="primary" htmlType="submit" loading={savingPassword} icon={<SaveOutlined />}>
                                                Atualizar senha
                                            </Button>
                                        </div>
                                    </Space>
                                </form>
                            </div>
                        </Space>
                    </Space>
                </Card>
            </Col>

            <Col xs={24} xl={8}>
                <Card className="spa-quick-view-card" title="Resumo da conta">
                    <Space direction="vertical" size={14} className="spa-detail-stack">
                        <Space wrap>
                            <Tag color="gold">{profile.nivel_label || 'Usuário'}</Tag>
                            <Tag color={profile.must_change_password ? 'volcano' : 'green'}>
                                {profile.must_change_password ? 'Senha obrigatória' : 'Senha liberada'}
                            </Tag>
                        </Space>

                        <Typography.Title level={4} className="spa-section-title">
                            {profile.name || 'Sem nome'}
                        </Typography.Title>
                        <Typography.Text type="secondary">{profile.email || 'Sem e-mail'}</Typography.Text>
                        <Typography.Text type="secondary">
                            {profile.vendedor?.status ? `Status do acesso: ${profile.vendedor.status}` : 'Sem vínculo de vendedor'}
                        </Typography.Text>
                        <Typography.Text type="secondary">
                            Conta criada: {formatLabel(profile.created_at, 'N/A')}
                        </Typography.Text>

                        <Divider />

                        <Typography.Title level={4} className="spa-section-title">
                            Ações rápidas
                        </Typography.Title>

                        <Space direction="vertical" size={8} style={{ width: '100%' }}>
                            <Link to="/home">Ir para o painel</Link>
                        </Space>

                        <Divider />

                        <Space direction="vertical" size={12} style={{ width: '100%' }}>
                            <div>
                                <Typography.Text strong>Logotipo da empresa</Typography.Text>
                                <Typography.Text type="secondary" style={{ display: 'block', marginTop: 4 }}>
                                    Tamanho ideal: 600x200. Tipos aceitos: jpg, png e webp.
                                </Typography.Text>
                            </div>

                            <input
                                ref={companyLogoInputRef}
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                hidden
                                onChange={(event) => {
                                    const file = event.target.files?.[0] ?? null;
                                    setCompanyLogoFile(file);
                                    void uploadCompanyLogo(file);
                                }}
                            />

                            <Button
                                icon={<UploadOutlined />}
                                onClick={() => companyLogoInputRef.current?.click()}
                                loading={savingLogo}
                                block
                            >
                                Selecionar logotipo
                            </Button>

                            {logoUploadStatus ? <Alert type="success" showIcon message={logoUploadStatus} /> : null}

                            {companyLogoPreviewUrl ? (
                                <div
                                    style={{
                                        padding: 16,
                                        borderRadius: 16,
                                        border: '1px solid rgba(0, 0, 0, 0.08)',
                                        background: 'rgba(255, 255, 255, 0.72)',
                                    }}
                                >
                                    <Typography.Text strong style={{ display: 'block', marginBottom: 8 }}>
                                        {companyLogoFile ? 'Pré-visualização local' : 'Logotipo ativo'}
                                    </Typography.Text>
                                    <img
                                        src={companyLogoPreviewUrl}
                                        alt="Pré-visualização do logotipo"
                                        style={{
                                            display: 'block',
                                            maxWidth: '100%',
                                            maxHeight: 92,
                                            objectFit: 'contain',
                                        }}
                                    />
                                </div>
                            ) : null}
                        </Space>
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

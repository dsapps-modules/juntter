import { DeleteOutlined, MailOutlined, SafetyOutlined, SaveOutlined } from '@ant-design/icons';
import { Alert, Button, Card, Col, Divider, Input, Row, Space, Tag, Typography } from 'antd';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

const defaultProfile = {
    name: '',
    email: '',
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

export default function ProfilePage() {
    const [loading, setLoading] = useState(true);
    const [savingProfile, setSavingProfile] = useState(false);
    const [savingPassword, setSavingPassword] = useState(false);
    const [deletingAccount, setDeletingAccount] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [profile, setProfile] = useState(defaultProfile);
    const [profileForm, setProfileForm] = useState({
        name: '',
        email: '',
    });
    const [passwordForm, setPasswordForm] = useState({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const [deletionPassword, setDeletionPassword] = useState('');

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

    async function handleProfileSubmit(event) {
        event.preventDefault();
        setSavingProfile(true);
        setError('');
        setSuccess('');

        try {
            const payload = await submitJson('/profile', 'PATCH', profileForm);
            setSuccess(payload.message ?? 'Perfil atualizado com sucesso.');

            if (payload.redirect) {
                window.location.assign(payload.redirect);
            } else {
                setProfile((current) => ({
                    ...current,
                    ...profileForm,
                }));
            }
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

    async function handleDeleteAccount(event) {
        event.preventDefault();
        setDeletingAccount(true);
        setError('');
        setSuccess('');

        try {
            const payload = await submitJson('/profile', 'DELETE', {
                password: deletionPassword,
            });

            if (payload.redirect) {
                window.location.assign(payload.redirect);
                return;
            }

            window.location.assign('/app/login');
        } catch (submitError) {
            setError(submitError.message || 'Falha ao remover a conta.');
        } finally {
            setDeletingAccount(false);
        }
    }

    async function resendVerification() {
        setError('');
        setSuccess('');

        try {
            const payload = await submitJson('/email/verification-notification', 'POST', {});
            setSuccess(payload.message === 'verification-link-sent' ? 'Novo link de verificação enviado.' : 'Solicitação enviada.');
        } catch (submitError) {
            setError(submitError.message || 'Falha ao reenviar o e-mail de verificação.');
        }
    }

    return (
        <Row gutter={[20, 20]} className="spa-profile-grid">
            <Col xs={24} xl={16}>
                <Card className="spa-hero-card">
                    <Space direction="vertical" size={18} className="spa-hero-stack">
                        <div>
                            <Typography.Text className="spa-brand-kicker">Configurações</Typography.Text>
                            <Typography.Title level={2} className="spa-hero-title">
                                Perfil e segurança da conta.
                            </Typography.Title>
                            <Typography.Paragraph className="spa-hero-description">
                                Atualize nome, e-mail, senha e o estado de verificação em uma interface única, sem sair da SPA.
                            </Typography.Paragraph>
                        </div>

                        {error ? <Alert type="error" showIcon message={error} /> : null}
                        {success ? <Alert type="success" showIcon message={success} /> : null}

                        <Row gutter={[16, 16]}>
                            <Col xs={24} md={8}>
                                <Card bordered={false} className="spa-mini-surface">
                                    <Typography.Text className="spa-placeholder-kicker">Tipo de acesso</Typography.Text>
                                    <Typography.Title level={4} className="spa-mini-title">
                                        {profile.nivel_label || 'Usuário'}
                                    </Typography.Title>
                                </Card>
                            </Col>
                            <Col xs={24} md={8}>
                                <Card bordered={false} className="spa-mini-surface">
                                    <Typography.Text className="spa-placeholder-kicker">Verificação</Typography.Text>
                                    <Typography.Title level={4} className="spa-mini-title">
                                        {profile.verified ? 'Concluída' : 'Pendente'}
                                    </Typography.Title>
                                </Card>
                            </Col>
                            <Col xs={24} md={8}>
                                <Card bordered={false} className="spa-mini-surface">
                                    <Typography.Text className="spa-placeholder-kicker">Conta criada</Typography.Text>
                                    <Typography.Title level={4} className="spa-mini-title">
                                        {profile.created_at || 'N/A'}
                                    </Typography.Title>
                                </Card>
                            </Col>
                        </Row>
                    </Space>
                </Card>

                <Card className="spa-table-card" title="Dados pessoais">
                    {loading ? (
                        <Typography.Text type="secondary">Carregando perfil...</Typography.Text>
                    ) : (
                        <form onSubmit={handleProfileSubmit}>
                            <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                <div>
                                    <Typography.Text strong>Nome</Typography.Text>
                                    <Input
                                        value={profileForm.name}
                                        onChange={(event) => setProfileForm((current) => ({ ...current, name: event.target.value }))}
                                        placeholder="Seu nome"
                                        size="large"
                                        className="spa-auth-input"
                                    />
                                </div>

                                <div>
                                    <Typography.Text strong>E-mail</Typography.Text>
                                    <Input
                                        value={profileForm.email}
                                        onChange={(event) => setProfileForm((current) => ({ ...current, email: event.target.value }))}
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

                                {! profile.verified ? (
                                    <Alert
                                        type="warning"
                                        showIcon
                                        message="Seu e-mail ainda não foi verificado."
                                        description="Você pode reenviar o link de verificação sem sair da aplicação."
                                        action={
                                            <Button size="small" onClick={resendVerification}>
                                                Reenviar link
                                            </Button>
                                        }
                                    />
                                ) : null}

                                <div className="spa-profile-actions">
                                    <Button type="primary" htmlType="submit" loading={savingProfile} icon={<SaveOutlined />}>
                                        Salvar alterações
                                    </Button>
                                    <Button onClick={() => window.location.assign('/app/change-password')} icon={<SafetyOutlined />}>
                                        Trocar senha obrigatória
                                    </Button>
                                </div>
                            </Space>
                        </form>
                    )}
                </Card>

                <Card className="spa-table-card" title="Atualizar senha">
                    <form onSubmit={handlePasswordSubmit}>
                        <Space direction="vertical" size={16} style={{ width: '100%' }}>
                            <Input.Password
                                value={passwordForm.current_password}
                                onChange={(event) =>
                                    setPasswordForm((current) => ({ ...current, current_password: event.target.value }))
                                }
                                placeholder="Senha atual"
                                size="large"
                                className="spa-auth-input"
                            />

                            <Input.Password
                                value={passwordForm.password}
                                onChange={(event) =>
                                    setPasswordForm((current) => ({ ...current, password: event.target.value }))
                                }
                                placeholder="Nova senha"
                                size="large"
                                className="spa-auth-input"
                            />

                            <Input.Password
                                value={passwordForm.password_confirmation}
                                onChange={(event) =>
                                    setPasswordForm((current) => ({ ...current, password_confirmation: event.target.value }))
                                }
                                placeholder="Confirmar nova senha"
                                size="large"
                                className="spa-auth-input"
                            />

                            <Button type="primary" htmlType="submit" loading={savingPassword} icon={<SaveOutlined />}>
                                Atualizar senha
                            </Button>
                        </Space>
                    </form>
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

                        <Divider />

                        <Typography.Title level={4} className="spa-section-title">
                            Ações rápidas
                        </Typography.Title>

                        <Space direction="vertical" size={8} style={{ width: '100%' }}>
                            <Link to="/home">Ir para o painel</Link>
                            <Link to="/estabelecimentos">Abrir estabelecimentos</Link>
                            <Link to="/change-password">Fluxo de senha obrigatória</Link>
                        </Space>

                        <Divider />

                        <form onSubmit={handleDeleteAccount}>
                            <Space direction="vertical" size={12} style={{ width: '100%' }}>
                                <Typography.Title level={5}>Zona de risco</Typography.Title>
                                <Typography.Text type="secondary">
                                    A exclusão remove o acesso da conta e encerra a sessão atual.
                                </Typography.Text>
                                <Input.Password
                                    value={deletionPassword}
                                    onChange={(event) => setDeletionPassword(event.target.value)}
                                    placeholder="Confirme a senha"
                                    size="large"
                                    className="spa-auth-input"
                                />
                                <Button danger icon={<DeleteOutlined />} htmlType="submit" loading={deletingAccount}>
                                    Excluir conta
                                </Button>
                            </Space>
                        </form>
                    </Space>
                </Card>
            </Col>
        </Row>
    );
}

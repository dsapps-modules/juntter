import {
    DeleteOutlined,
    EditOutlined,
    EyeOutlined,
    KeyOutlined,
    PlusOutlined,
    ReloadOutlined,
    TeamOutlined,
} from '@ant-design/icons';
import {
    Alert,
    Avatar,
    Button,
    Card,
    Col,
    Divider,
    Empty,
    Input,
    List,
    Modal,
    Row,
    Select,
    Space,
    Skeleton,
    Statistic,
    Table,
    Tag,
    Typography,
} from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';

const defaultPayload = {
    summary: {
        total_vendors: 0,
        active_vendors: 0,
        inactive_vendors: 0,
        admin_loja: 0,
        vendedor_loja: 0,
        must_change_password: 0,
        linked_establishments: 0,
    },
    rows: [],
    selected: null,
    recent_activity: [],
    actions: [],
};

const initialCreateForm = {
    establishment_id: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
};

const emptyEditForm = {
    name: '',
    email: '',
};

const emptyPasswordForm = {
    password: '',
    password_confirmation: '',
};

const filterMatcher = {
    Todos: () => true,
    Ativos: (item) => item.status === 'Ativo',
    Inativos: (item) => item.status === 'Inativo',
    'Senha obrigatória': (item) => item.must_change_password,
};

function formatError(response, fallbackMessage) {
    const firstError = Object.values(response.errors ?? {}).flat().shift();

    return firstError ?? response.message ?? fallbackMessage;
}

function buildHeaders(csrfToken) {
    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken ?? '',
    };
}

export default function VendedoresAcessoPage() {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [payload, setPayload] = useState(defaultPayload);
    const [filter, setFilter] = useState('Todos');
    const [tableSearchTerm, setTableSearchTerm] = useState('');
    const [establishmentSearchTerm, setEstablishmentSearchTerm] = useState('');
    const [createForm, setCreateForm] = useState(initialCreateForm);
    const [establishmentOptions, setEstablishmentOptions] = useState([]);
    const [searchingEstablishments, setSearchingEstablishments] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [passwordModalOpen, setPasswordModalOpen] = useState(false);
    const [selectedRow, setSelectedRow] = useState(null);
    const [editForm, setEditForm] = useState(emptyEditForm);
    const [passwordForm, setPasswordForm] = useState(emptyPasswordForm);
    const [activeUserId, setActiveUserId] = useState(null);

    useEffect(() => {
        const controller = new AbortController();

        async function loadOverview() {
            setLoading(true);
            setError('');

            try {
                const response = await fetch('/api/spa/vendedores/acesso', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar o acesso dos vendedores.');
                }

                const data = await response.json();
                setPayload((current) => ({
                    ...current,
                    ...data,
                    summary: data.summary ?? current.summary,
                    rows: data.rows ?? [],
                    selected: data.selected ?? data.rows?.[0] ?? null,
                    recent_activity: data.recent_activity ?? [],
                    actions: data.actions ?? [],
                }));
                setSelectedRow(data.selected ?? data.rows?.[0] ?? null);
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setError(fetchError.message || 'Falha ao carregar os vendedores.');
                }
            } finally {
                setLoading(false);
            }
        }

        loadOverview();

        return () => controller.abort();
    }, []);

    useEffect(() => {
        const controller = new AbortController();

        async function searchEstablishments() {
            if (establishmentSearchTerm.trim().length < 3) {
                setEstablishmentOptions([]);
                return;
            }

            setSearchingEstablishments(true);

            try {
                const response = await fetch(
                    `/vendedores/acesso/search?q=${encodeURIComponent(establishmentSearchTerm.trim())}`,
                    {
                        signal: controller.signal,
                        headers: {
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    }
                );

                if (!response.ok) {
                    throw new Error('Não foi possível buscar os estabelecimentos.');
                }

                const data = await response.json();
                setEstablishmentOptions((data.results ?? []).map((item) => ({
                    label: item.text,
                    value: String(item.id),
                    email: item.email ?? '',
                    nameClean: item.name_clean ?? '',
                })));
            } catch (fetchError) {
                if (fetchError.name !== 'AbortError') {
                    setEstablishmentOptions([]);
                }
            } finally {
                setSearchingEstablishments(false);
            }
        }

        const timeout = window.setTimeout(searchEstablishments, 300);

        return () => {
            controller.abort();
            window.clearTimeout(timeout);
        };
    }, [establishmentSearchTerm]);

    const visibleRows = useMemo(() => {
        return payload.rows.filter((item) => {
            const text = `${item.name} ${item.email} ${item.establishment} ${item.role}`.toLowerCase();
            return text.includes(tableSearchTerm.toLowerCase()) && (filterMatcher[filter] ?? filterMatcher.Todos)(item);
        });
    }, [filter, payload.rows, tableSearchTerm]);

    const currentRow = visibleRows.find((item) => item.id === selectedRow?.id) ?? visibleRows[0] ?? payload.selected;

    const columns = [
        {
            title: 'Usuário',
            dataIndex: 'name',
            render: (_, record) => (
                <Space size={14}>
                    <Avatar className="spa-row-avatar">{record.name?.slice(0, 2)?.toUpperCase() ?? 'VT'}</Avatar>
                    <div>
                        <Typography.Text strong>{record.name}</Typography.Text>
                        <div>
                            <Typography.Text type="secondary">{record.email}</Typography.Text>
                        </div>
                    </div>
                </Space>
            ),
        },
        {
            title: 'Perfil',
            dataIndex: 'role',
            render: (value) => <Tag color="gold">{value}</Tag>,
        },
        {
            title: 'Status',
            dataIndex: 'status',
            render: (value) => <Tag color={value === 'Ativo' ? 'green' : 'volcano'}>{value}</Tag>,
        },
        {
            title: 'Estabelecimento',
            dataIndex: 'establishment',
        },
        {
            title: 'Última atividade',
            dataIndex: 'last_activity',
        },
        {
            title: 'Ações',
            render: (_, record) => (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => openEditModal(record)}>
                        Editar
                    </Button>
                    <Button size="small" icon={<KeyOutlined />} onClick={() => openPasswordModal(record)}>
                        Senha
                    </Button>
                </Space>
            ),
        },
    ];

    function openEditModal(record) {
        setActiveUserId(record.id);
        setEditForm({
            name: record.name ?? '',
            email: record.email ?? '',
        });
        setEditModalOpen(true);
    }

    function openPasswordModal(record) {
        setActiveUserId(record.id);
        setPasswordForm(emptyPasswordForm);
        setPasswordModalOpen(true);
    }

    async function reloadOverview() {
        const response = await fetch('/api/spa/vendedores/acesso', {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Não foi possível atualizar a lista.');
        }

        const data = await response.json();
        setPayload((current) => ({
            ...current,
            ...data,
            summary: data.summary ?? current.summary,
            rows: data.rows ?? [],
            selected: data.selected ?? data.rows?.[0] ?? null,
            recent_activity: data.recent_activity ?? [],
            actions: data.actions ?? [],
        }));
        setSelectedRow(data.selected ?? data.rows?.[0] ?? null);
    }

    async function handleCreateSubmit(event) {
        event.preventDefault();
        setSaving(true);
        setError('');
        setSuccess('');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch('/vendedores/acesso', {
                method: 'POST',
                headers: buildHeaders(csrfToken),
                credentials: 'same-origin',
                body: JSON.stringify(createForm),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(formatError(result, 'Não foi possível criar o acesso.'));
            }

            setSuccess(result.message ?? 'Acesso criado com sucesso.');
            setCreateForm(initialCreateForm);
            setEstablishmentOptions([]);
            setTableSearchTerm('');
            await reloadOverview();
        } catch (submitError) {
            setError(submitError.message || 'Falha ao criar o acesso.');
        } finally {
            setSaving(false);
        }
    }

    async function handleEditSubmit() {
        setSaving(true);
        setError('');
        setSuccess('');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch(`/vendedores/acesso/${activeUserId}`, {
                method: 'PUT',
                headers: buildHeaders(csrfToken),
                credentials: 'same-origin',
                body: JSON.stringify(editForm),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(formatError(result, 'Não foi possível atualizar o acesso.'));
            }

            setSuccess(result.message ?? 'Dados do vendedor atualizados com sucesso.');
            setEditModalOpen(false);
            await reloadOverview();
        } catch (submitError) {
            setError(submitError.message || 'Falha ao atualizar o acesso.');
        } finally {
            setSaving(false);
        }
    }

    async function handlePasswordSubmit() {
        setSaving(true);
        setError('');
        setSuccess('');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await fetch(`/vendedores/acesso/${activeUserId}/senha`, {
                method: 'PATCH',
                headers: buildHeaders(csrfToken),
                credentials: 'same-origin',
                body: JSON.stringify(passwordForm),
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(formatError(result, 'Não foi possível atualizar a senha.'));
            }

            setSuccess(result.message ?? 'Senha atualizada com sucesso.');
            setPasswordModalOpen(false);
            await reloadOverview();
        } catch (submitError) {
            setError(submitError.message || 'Falha ao atualizar a senha.');
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete(record) {
        Modal.confirm({
            title: 'Remover acesso',
            content: `Tem certeza que deseja remover o acesso de ${record.name}?`,
            okText: 'Remover',
            okButtonProps: { danger: true },
            cancelText: 'Cancelar',
            onOk: async () => {
                setSaving(true);
                setError('');
                setSuccess('');

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const response = await fetch(`/vendedores/acesso/${record.id}`, {
                        method: 'DELETE',
                        headers: buildHeaders(csrfToken),
                        credentials: 'same-origin',
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(formatError(result, 'Não foi possível remover o acesso.'));
                    }

                    setSuccess(result.message ?? 'Acesso removido com sucesso.');
                    await reloadOverview();
                } catch (submitError) {
                    setError(submitError.message || 'Falha ao remover o acesso.');
                } finally {
                    setSaving(false);
                }
            },
        });
    }

    return (
        <Row gutter={[20, 20]} className="spa-board">
            <Col xs={24} xl={16}>
                <Row gutter={[20, 20]}>
                    <Col span={24}>
                        <Card className="spa-hero-card">
                            <Space direction="vertical" size={18} className="spa-hero-stack">
                                <div>
                                    <Typography.Text className="spa-brand-kicker">Vendedores</Typography.Text>
                                    <Typography.Title level={2} className="spa-hero-title">
                                        Gestão de acesso
                                    </Typography.Title>
                                    <Typography.Paragraph className="spa-hero-description">
                                        Cadastre, edite e remova acessos sem sair da interface nova.
                                    </Typography.Paragraph>
                                </div>

                                {error ? <Alert type="error" showIcon message={error} /> : null}
                                {success ? <Alert type="success" showIcon message={success} /> : null}

                                <Input
                                    allowClear
                                    prefix={<TeamOutlined />}
                                    placeholder="Buscar nome, e-mail, estabelecimento ou perfil"
                                    value={tableSearchTerm}
                                    onChange={(event) => setTableSearchTerm(event.target.value)}
                                    size="large"
                                />

                                <Row gutter={16} style={{ width: '100%' }}>
                                    <Col xs={24} md={6}>
                                        <Statistic title="Vendedores" value={payload.summary.total_vendors} />
                                    </Col>
                                    <Col xs={24} md={6}>
                                        <Statistic title="Ativos" value={payload.summary.active_vendors} />
                                    </Col>
                                    <Col xs={24} md={6}>
                                        <Statistic title="Inativos" value={payload.summary.inactive_vendors} />
                                    </Col>
                                    <Col xs={24} md={6}>
                                        <Statistic title="Senha obrigatória" value={payload.summary.must_change_password} />
                                    </Col>
                                </Row>
                            </Space>
                        </Card>
                    </Col>

                    <Col span={24}>
                        <Card className="spa-table-card" title="Novo acesso">
                            <form onSubmit={handleCreateSubmit}>
                                <Space direction="vertical" size={16} style={{ width: '100%' }}>
                                    <Select
                                        showSearch
                                        allowClear
                                        value={createForm.establishment_id || undefined}
                                        placeholder="Busque pelo estabelecimento"
                                        filterOption={false}
                                        onSearch={(value) => setEstablishmentSearchTerm(value)}
                                        onChange={(value, option) => {
                                            const selectedValue = value ?? '';
                                            setCreateForm((current) => ({
                                                ...current,
                                                establishment_id: selectedValue,
                                                name: option?.nameClean || current.name,
                                                email: option?.email || current.email,
                                            }));
                                        }}
                                        onClear={() => {
                                            setEstablishmentSearchTerm('');
                                            setCreateForm(initialCreateForm);
                                        }}
                                        options={establishmentOptions}
                                        notFoundContent={searchingEstablishments ? <Skeleton active paragraph={false} /> : null}
                                        style={{ width: '100%' }}
                                        size="large"
                                    />

                                    <Row gutter={16}>
                                        <Col xs={24} md={12}>
                                            <Input
                                                value={createForm.name}
                                                onChange={(event) =>
                                                    setCreateForm((current) => ({ ...current, name: event.target.value }))
                                                }
                                                placeholder="Nome"
                                                size="large"
                                            />
                                        </Col>
                                        <Col xs={24} md={12}>
                                            <Input
                                                value={createForm.email}
                                                onChange={(event) =>
                                                    setCreateForm((current) => ({ ...current, email: event.target.value }))
                                                }
                                                placeholder="E-mail"
                                                size="large"
                                            />
                                        </Col>
                                    </Row>

                                    <Row gutter={16}>
                                        <Col xs={24} md={12}>
                                            <Input.Password
                                                value={createForm.password}
                                                onChange={(event) =>
                                                    setCreateForm((current) => ({ ...current, password: event.target.value }))
                                                }
                                                placeholder="Senha"
                                                size="large"
                                            />
                                        </Col>
                                        <Col xs={24} md={12}>
                                            <Input.Password
                                                value={createForm.password_confirmation}
                                                onChange={(event) =>
                                                    setCreateForm((current) => ({
                                                        ...current,
                                                        password_confirmation: event.target.value,
                                                    }))
                                                }
                                                placeholder="Confirmar senha"
                                                size="large"
                                            />
                                        </Col>
                                    </Row>

                                    <Space>
                                        <Button type="primary" htmlType="submit" icon={<PlusOutlined />} loading={saving}>
                                            Criar acesso
                                        </Button>
                                        <Button
                                            icon={<ReloadOutlined />}
                                            onClick={() => reloadOverview().catch((refreshError) => setError(refreshError.message))}
                                        >
                                            Atualizar lista
                                        </Button>
                                    </Space>
                                </Space>
                            </form>
                        </Card>
                    </Col>

                    <Col span={24}>
                        <Card className="spa-table-card" title="Vendedores com acesso">
                            {loading ? (
                                <Skeleton active paragraph={{ rows: 6 }} />
                            ) : visibleRows.length === 0 ? (
                                <Empty description="Nenhum vendedor encontrado" />
                            ) : (
                                <Table
                                    rowKey="id"
                                    columns={columns}
                                    dataSource={visibleRows}
                                    pagination={false}
                                    className="spa-table"
                                    onRow={(record) => ({
                                        onClick: () => setSelectedRow(record),
                                    })}
                                    rowClassName={(record) =>
                                        record.id === currentRow?.id ? 'spa-table-row-selected' : ''
                                    }
                                />
                            )}
                        </Card>
                    </Col>
                </Row>
            </Col>

            <Col xs={24} xl={8}>
                <Card className="spa-quick-view-card" title={currentRow ? `Quick View: ${currentRow.name}` : 'Quick View'}>
                    {!currentRow ? (
                        <Empty description="Selecione um vendedor para ver os detalhes" />
                    ) : (
                        <>
                            <Space wrap>
                                <Tag color={currentRow.status === 'Ativo' ? 'green' : 'volcano'}>{currentRow.status}</Tag>
                                <Tag color="gold">{currentRow.role}</Tag>
                            </Space>

                            <Divider />

                            <Space direction="vertical" size={10} className="spa-detail-stack">
                                <Typography.Text strong>{currentRow.name}</Typography.Text>
                                <Typography.Text type="secondary">{currentRow.email}</Typography.Text>
                                <Typography.Text type="secondary">{currentRow.establishment}</Typography.Text>
                                <Typography.Text type="secondary">{currentRow.phone}</Typography.Text>
                                <Typography.Text type="secondary">Última atividade {currentRow.last_activity}</Typography.Text>
                                <Typography.Text type="secondary">
                                    Comissão {currentRow.commission} • Meta {currentRow.goal}
                                </Typography.Text>
                            </Space>

                            <Divider />

                            <Space direction="vertical" size={12} style={{ width: '100%' }}>
                                <Button block icon={<EditOutlined />} onClick={() => openEditModal(currentRow)}>
                                    Editar dados
                                </Button>
                                <Button block icon={<KeyOutlined />} onClick={() => openPasswordModal(currentRow)}>
                                    Alterar senha
                                </Button>
                                <Button danger block icon={<DeleteOutlined />} onClick={() => handleDelete(currentRow)}>
                                    Remover acesso
                                </Button>
                            </Space>

                            <Divider />

                            <Typography.Title level={4} className="spa-section-title">
                                Atividade recente
                            </Typography.Title>

                            <List
                                dataSource={payload.recent_activity}
                                renderItem={(item) => (
                                    <List.Item className="spa-quick-link-item">
                                        <Space align="start" size={14}>
                                            <div className="spa-quick-link-icon">
                                                <EyeOutlined />
                                            </div>
                                            <div>
                                                <Typography.Text strong>{item.name}</Typography.Text>
                                                <div>
                                                    <Typography.Text type="secondary">
                                                        {item.role} • {item.last_activity}
                                                    </Typography.Text>
                                                </div>
                                            </div>
                                        </Space>
                                    </List.Item>
                                )}
                            />
                        </>
                    )}
                </Card>

                <Card className="spa-quick-view-card" title="Ações" style={{ marginTop: 20 }}>
                    <Space direction="vertical" size={12} className="spa-detail-stack">
                        {payload.actions.map((item) => (
                            <Link key={item.href} to={item.href}>
                                {item.title}
                            </Link>
                        ))}
                    </Space>
                </Card>
            </Col>

            <Modal
                title="Editar acesso"
                open={editModalOpen}
                onCancel={() => setEditModalOpen(false)}
                onOk={handleEditSubmit}
                okText="Salvar"
                confirmLoading={saving}
                cancelText="Cancelar"
            >
                <Space direction="vertical" size={16} style={{ width: '100%' }}>
                    <Input
                        value={editForm.name}
                        onChange={(event) => setEditForm((current) => ({ ...current, name: event.target.value }))}
                        placeholder="Nome"
                    />
                    <Input
                        value={editForm.email}
                        onChange={(event) => setEditForm((current) => ({ ...current, email: event.target.value }))}
                        placeholder="E-mail"
                    />
                </Space>
            </Modal>

            <Modal
                title="Alterar senha"
                open={passwordModalOpen}
                onCancel={() => setPasswordModalOpen(false)}
                onOk={handlePasswordSubmit}
                okText="Salvar senha"
                confirmLoading={saving}
                cancelText="Cancelar"
            >
                <Space direction="vertical" size={16} style={{ width: '100%' }}>
                    <Input.Password
                        value={passwordForm.password}
                        onChange={(event) => setPasswordForm((current) => ({ ...current, password: event.target.value }))}
                        placeholder="Nova senha"
                    />
                    <Input.Password
                        value={passwordForm.password_confirmation}
                        onChange={(event) =>
                            setPasswordForm((current) => ({ ...current, password_confirmation: event.target.value }))
                        }
                        placeholder="Confirmar nova senha"
                    />
                </Space>
            </Modal>
        </Row>
    );
}

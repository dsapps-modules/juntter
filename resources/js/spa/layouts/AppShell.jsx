import {
    AppstoreOutlined,
    BankOutlined,
    CreditCardOutlined,
    MenuOutlined,
    LogoutOutlined,
    SettingOutlined,
    TeamOutlined,
} from '@ant-design/icons';
import { Avatar, Button, Drawer, Grid, Layout, Menu, Space, Spin, Tooltip, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import {
    buildKeyPathMap,
    buildNavigationSections,
    findNavigationItem,
    sharedNavigationItems,
} from '../navigation/menu';

const { Header, Sider, Content } = Layout;

const iconByName = {
    dashboard: <AppstoreOutlined />,
    estabelecimentos: <BankOutlined />,
    lista: <TeamOutlined />,
    faturamento: <CreditCardOutlined />,
    acesso: <SettingOutlined />,
    unica: <CreditCardOutlined />,
    links: <CreditCardOutlined />,
    cartao: <CreditCardOutlined />,
    pix: <CreditCardOutlined />,
    boleto: <CreditCardOutlined />,
    simular: <CreditCardOutlined />,
    planos: <CreditCardOutlined />,
    saldo: <CreditCardOutlined />,
    credit: <CreditCardOutlined />,
    perfil: <SettingOutlined />,
};

function mapNavigationItem(item) {
    if (item.type === 'submenu' || item.type === 'group') {
        if (item.type === 'group') {
            return {
                type: 'group',
                label: item.label,
                children: item.children.map(mapNavigationItem),
            };
        }

        return {
            type: 'submenu',
            key: item.key,
            label: item.label,
            icon: iconByName[item.icon] ?? null,
            children: item.children.map(mapNavigationItem),
        };
    }

    return {
        key: item.key,
        label: item.label,
        icon: iconByName[item.icon] ?? null,
    };
}

function formatAccessLevel(accessLevel) {
    return accessLevel
        .split('_')
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
        .join(' ');
}

function getUserInitials(name) {
    const initials = name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');

    return initials || 'U';
}

export default function AppShell() {
    const navigate = useNavigate();
    const location = useLocation();
    const screens = Grid.useBreakpoint();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [accessLevel, setAccessLevel] = useState('');
    const [accessLabel, setAccessLabel] = useState('');
    const [userName, setUserName] = useState('');
    const [avatarUrl, setAvatarUrl] = useState('');

    useEffect(() => {
        const controller = new AbortController();

        async function loadProfile() {
            try {
                const response = await fetch('/api/spa/perfil', {
                    signal: controller.signal,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                setUserName(data.profile?.name ?? '');
                setAvatarUrl(data.profile?.avatar_url ?? data.profile?.photo_url ?? '');
                setAccessLevel(data.profile?.nivel_acesso ?? '');
                setAccessLabel(data.profile?.nivel_label ?? formatAccessLevel(data.profile?.nivel_acesso ?? ''));
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setUserName('');
                    setAvatarUrl('');
                    setAccessLevel('');
                    setAccessLabel('');
                }
            }
        }

        loadProfile();

        return () => controller.abort();
    }, []);

    const menuSections = useMemo(() => buildNavigationSections(accessLevel), [accessLevel]);
    const keyPathMap = useMemo(() => {
        const map = buildKeyPathMap(menuSections);

        sharedNavigationItems.forEach((item) => {
            map.set(item.key, item.path);
        });

        return map;
    }, [menuSections]);
    const menuItems = useMemo(
        () => [
            ...menuSections.map((section) => ({
                type: 'group',
                label: section.label,
                children: section.children.map(mapNavigationItem),
            })),
            ...(menuSections.length > 0
                ? [
                    {
                        type: 'divider',
                    },
                ]
                : []),
            ...sharedNavigationItems.map(mapNavigationItem),
        ],
        [menuSections],
    );

    const currentItem = useMemo(() => {
        const path = `${location.pathname.replace('/app', '')}${location.search}`;
        const sectionsWithShared = [
            ...menuSections,
            {
                children: sharedNavigationItems,
            },
        ];

        return findNavigationItem(sectionsWithShared, path) ?? menuSections[0]?.children[0] ?? sharedNavigationItems[0] ?? null;
    }, [location.pathname, location.search, menuSections]);

    const selectedKey = currentItem?.key ?? 'home.dashboard';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const logoutForm = (
        <form method="POST" action="/logout" className="spa-sider-logout-form">
            <input type="hidden" name="_token" value={csrfToken ?? ''} />
            <Button danger htmlType="submit" block icon={<LogoutOutlined />} className="spa-secondary-button">
                Sair
            </Button>
        </form>
    );

    const menu = (
        <Menu
            mode="inline"
            selectedKeys={[selectedKey]}
            items={menuItems}
            onClick={({ key }) => {
                const targetPath = keyPathMap.get(key) ?? '/home';
                navigate(targetPath);
                setMobileMenuOpen(false);
            }}
            className="spa-sider-menu"
        />
    );

    return (
        <Layout className="spa-layout">
            {screens.lg ? (
                <Sider className="spa-sider" width={276}>
                    <div className="spa-brand">
                        <Avatar size={44} className="spa-brand-mark" src={avatarUrl || undefined}>
                            {getUserInitials(userName)}
                        </Avatar>
                        <div className="spa-brand-copy">
                            <Tooltip title={userName || ''} placement="right">
                                <Typography.Text className="spa-brand-name" title={userName || ''}>
                                    {userName || <Spin size="small" />}
                                </Typography.Text>
                            </Tooltip>
                            <Typography.Text className="spa-brand-kicker">
                                {accessLabel || <Spin size="small" />}
                            </Typography.Text>
                        </div>
                    </div>
                    <div className="spa-sider-body">
                        {menu}
                        <div className="spa-sider-footer">{logoutForm}</div>
                    </div>
                </Sider>
            ) : (
                <Drawer
                    open={mobileMenuOpen}
                    onClose={() => setMobileMenuOpen(false)}
                    placement="left"
                    title={accessLabel || 'Carregando'}
                    width={280}
                    className="spa-mobile-drawer"
                >
                    <div className="spa-sider-body spa-sider-drawer-body">
                        {menu}
                        <div className="spa-sider-footer">{logoutForm}</div>
                    </div>
                </Drawer>
            )}

            <Layout className="spa-shell">
                <Header className="spa-header">
                    <Space align="center" size={16}>
                        {!screens.lg ? (
                            <Button
                                icon={<MenuOutlined />}
                                onClick={() => setMobileMenuOpen(true)}
                                className="spa-icon-button"
                            />
                        ) : null}
                        <div>
                            <Typography.Title level={3} className="spa-header-title">
                                {currentItem ? currentItem.label : <Spin size="small" />}
                            </Typography.Title>
                        </div>
                    </Space>

                    <img
                        src="/img/logo/juntter_webp_640_174.webp"
                        alt="Juntter"
                        style={{ height: 44, width: 'auto', objectFit: 'contain' }}
                    />
                </Header>

                <Content className="spa-content">
                    <Outlet />
                </Content>
            </Layout>
        </Layout>
    );
}

import {
    AppstoreOutlined,
    BankOutlined,
    CreditCardOutlined,
    MenuOutlined,
    SettingOutlined,
    TeamOutlined,
} from '@ant-design/icons';
import { Avatar, Button, Drawer, Grid, Layout, Menu, Space, Spin, Typography } from 'antd';
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
    perfil: <SettingOutlined />,
};

function mapNavigationItem(item) {
    if (item.type === 'submenu') {
        return {
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

export default function AppShell() {
    const navigate = useNavigate();
    const location = useLocation();
    const screens = Grid.useBreakpoint();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [accessLevel, setAccessLevel] = useState('');

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
                setAccessLevel(data.profile?.nivel_acesso ?? '');
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setAccessLevel('');
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
                        <Avatar size={44} className="spa-brand-mark">
                            J
                        </Avatar>
                        <div>
                            <Typography.Text className="spa-brand-kicker">Juntter</Typography.Text>
                            <Typography.Title level={4} className="spa-brand-title">
                                Control Center
                            </Typography.Title>
                        </div>
                    </div>
                    {menu}
                </Sider>
            ) : (
                <Drawer
                    open={mobileMenuOpen}
                    onClose={() => setMobileMenuOpen(false)}
                    placement="left"
                    title="Juntter"
                    width={280}
                    className="spa-mobile-drawer"
                >
                    {menu}
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
                            <Typography.Text className="spa-header-kicker">Painel operacional</Typography.Text>
                            <Typography.Title level={3} className="spa-header-title">
                                {currentItem ? currentItem.label : <Spin size="small" />}
                            </Typography.Title>
                        </div>
                    </Space>

                    <Space size={12}>
                        <Button className="spa-secondary-button">Atualizar</Button>
                        <Button type="primary" className="spa-primary-button">
                            Nova ação
                        </Button>
                    </Space>
                </Header>

                <Content className="spa-content">
                    <Outlet />
                </Content>
            </Layout>
        </Layout>
    );
}

import {
    AppstoreOutlined,
    BankOutlined,
    CreditCardOutlined,
    MenuOutlined,
    SettingOutlined,
    TeamOutlined,
} from '@ant-design/icons';
import { Avatar, Button, Drawer, Grid, Layout, Menu, Space, Typography } from 'antd';
import { useMemo, useState } from 'react';
import { Outlet, useLocation, useNavigate } from 'react-router-dom';

const { Header, Sider, Content } = Layout;

const navigationItems = [
    { key: '/home', label: 'Home', icon: <AppstoreOutlined /> },
    { key: '/estabelecimentos', label: 'Estabelecimentos', icon: <BankOutlined /> },
    { key: '/cobranca', label: 'Cobrança', icon: <CreditCardOutlined /> },
    { key: '/vendedores', label: 'Vendedores', icon: <TeamOutlined /> },
    { key: '/perfil', label: 'Configurações', icon: <SettingOutlined /> },
];

export default function AppShell() {
    const navigate = useNavigate();
    const location = useLocation();
    const screens = Grid.useBreakpoint();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const selectedKey = useMemo(() => {
        const path = location.pathname.replace('/app', '');
        const foundItem = navigationItems.find((item) => path.startsWith(item.key));

        return foundItem ? foundItem.key : '/home';
    }, [location.pathname]);

    const menu = (
        <Menu
            mode="inline"
            selectedKeys={[selectedKey]}
            items={navigationItems}
            onClick={({ key }) => {
                navigate(key);
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
                                Home operacional
                            </Typography.Title>
                        </div>
                    </Space>

                    <Space size={12}>
                        <Button className="spa-secondary-button">Importar</Button>
                        <Button type="primary" className="spa-primary-button">
                            Cadastrar
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

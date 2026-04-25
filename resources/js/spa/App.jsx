import { ConfigProvider, theme } from 'antd';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import AppShell from './layouts/AppShell';
import ComingSoonPage from './pages/ComingSoonPage';
import EstablishmentsPage from './pages/EstablishmentsPage';
import HomePage from './pages/HomePage';
import LoginPage from './pages/LoginPage';

const appTheme = {
    algorithm: theme.defaultAlgorithm,
    token: {
        colorPrimary: '#f4c400',
        colorInfo: '#f4c400',
        colorSuccess: '#1f9d55',
        colorWarning: '#eab308',
        colorError: '#d14343',
        borderRadius: 16,
        fontFamily: '"Manrope", "Segoe UI", sans-serif',
        controlHeight: 44,
    },
    components: {
        Button: {
            borderRadius: 14,
        },
        Card: {
            borderRadiusLG: 20,
        },
        Layout: {
            bodyBg: 'transparent',
            headerBg: 'transparent',
            siderBg: '#ffffff',
        },
        Menu: {
            itemBorderRadius: 14,
        },
    },
};

export default function App() {
    return (
        <ConfigProvider theme={appTheme}>
            <BrowserRouter basename="/app">
                <Routes>
                    <Route path="/" element={<Navigate replace to="/home" />} />
                    <Route path="/login" element={<LoginPage />} />
                    <Route element={<AppShell />}>
                        <Route path="home" element={<HomePage />} />
                        <Route path="painel" element={<HomePage />} />
                        <Route path="estabelecimentos" element={<EstablishmentsPage />} />
                        <Route
                            path="cobranca"
                            element={
                                <ComingSoonPage
                                    title="Cobrança"
                                    description="A lista operacional de cobranças vai seguir o mesmo padrão visual da referência, com tabela central e painel lateral de detalhe."
                                />
                            }
                        />
                        <Route
                            path="links-pagamento"
                            element={
                                <ComingSoonPage
                                    title="Links de Pagamento"
                                    description="O módulo de links será migrado para cards, tabela e formulários em Ant Design."
                                />
                            }
                        />
                        <Route
                            path="vendedores"
                            element={
                                <ComingSoonPage
                                    title="Vendedores"
                                    description="A gestão de acesso e faturamento será reescrita com o mesmo shell de navegação."
                                />
                            }
                        />
                        <Route
                            path="perfil"
                            element={
                                <ComingSoonPage
                                    title="Perfil"
                                    description="A área de perfil será convertida para React após a base de navegação estar estável."
                                />
                            }
                        />
                        <Route
                            path="*"
                            element={<Navigate replace to="/home" />}
                        />
                    </Route>
                </Routes>
            </BrowserRouter>
        </ConfigProvider>
    );
}

import { ConfigProvider, theme } from 'antd';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import AppShell from './layouts/AppShell';
import EstablishmentsPage from './pages/EstablishmentsPage';
import EstabelecimentoFormPage from './pages/EstabelecimentoFormPage';
import HomePage from './pages/HomePage';
import CobrancaPage from './pages/CobrancaPage';
import LinksPagamentoPage from './pages/LinksPagamentoPage';
import LinkPagamentoFormPage from './pages/LinkPagamentoFormPage';
import VendedoresPage from './pages/VendedoresPage';
import VendedoresAcessoPage from './pages/VendedoresAcessoPage';
import VendedoresFaturamentoPage from './pages/VendedoresFaturamentoPage';
import LoginPage from './pages/LoginPage';
import ForgotPasswordPage from './pages/ForgotPasswordPage';
import ResetPasswordPage from './pages/ResetPasswordPage';
import VerifyEmailPage from './pages/VerifyEmailPage';
import ChangePasswordPage from './pages/ChangePasswordPage';
import ProfilePage from './pages/ProfilePage';

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
                    <Route path="/forgot-password" element={<ForgotPasswordPage />} />
                    <Route path="/reset-password/:token" element={<ResetPasswordPage />} />
                    <Route path="/verify-email" element={<VerifyEmailPage />} />
                    <Route path="/change-password" element={<ChangePasswordPage />} />
                    <Route element={<AppShell />}>
                        <Route path="home" element={<HomePage />} />
                        <Route path="painel" element={<HomePage />} />
                        <Route path="estabelecimentos" element={<EstablishmentsPage />} />
                        <Route path="estabelecimentos/:estabelecimentoId/editar" element={<EstabelecimentoFormPage />} />
                        <Route path="cobranca" element={<CobrancaPage />} />
                        <Route path="links-pagamento" element={<LinksPagamentoPage />} />
                        <Route path="links-pagamento/novo" element={<LinkPagamentoFormPage />} />
                        <Route path="links-pagamento/:linkId/editar" element={<LinkPagamentoFormPage />} />
                        <Route path="vendedores" element={<VendedoresPage />} />
                        <Route path="vendedores/acesso" element={<VendedoresAcessoPage />} />
                        <Route path="vendedores/faturamento" element={<VendedoresFaturamentoPage />} />
                        <Route path="perfil" element={<ProfilePage />} />
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

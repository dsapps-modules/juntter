import { ConfigProvider, theme } from 'antd';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import AppShell from './layouts/AppShell';
import EstablishmentsPage from './pages/EstablishmentsPage';
import EstabelecimentoDetailsPage from './pages/EstabelecimentoDetailsPage';
import EstabelecimentoFormPage from './pages/EstabelecimentoFormPage';
import HomePage from './pages/HomePage';
import CobrancaPage from './pages/CobrancaPage';
import CobrancaBoletoPage from './pages/cobranca/CobrancaBoletoPage';
import CobrancaCartaoCreditoPage from './pages/cobranca/CobrancaCartaoCreditoPage';
import CobrancaCreditoVistaPage from './pages/cobranca/CobrancaCreditoVistaPage';
import CobrancaPlanoContratadoPage from './pages/cobranca/CobrancaPlanoContratadoPage';
import CobrancaSaldoExtratoPage from './pages/cobranca/CobrancaSaldoExtratoPage';
import CobrancaSimularPage from './pages/cobranca/CobrancaSimularPage';
import CobrancaPixPage from './pages/cobranca/CobrancaPixPage';
import CheckoutLinksPage from './pages/checkout/CheckoutLinksPage';
import CheckoutLinkFormPage from './pages/checkout/CheckoutLinkFormPage';
import CheckoutLinkSalesPage from './pages/checkout/CheckoutLinkSalesPage';
import CheckoutProductFormPage from './pages/checkout/CheckoutProductFormPage';
import CheckoutProductsPage from './pages/checkout/CheckoutProductsPage';
import LinksPagamentoPage from './pages/LinksPagamentoPage';
import LinkPagamentoFormPage from './pages/LinkPagamentoFormPage';
import LinkPagamentoPixDetailPage from './pages/LinkPagamentoPixDetailPage';
import VendedoresPage from './pages/VendedoresPage';
import VendedoresAcessoPage from './pages/VendedoresAcessoPage';
import VendedoresFaturamentoPage from './pages/VendedoresFaturamentoPage';
import LoginPage from './pages/LoginPage';
import ForgotPasswordPage from './pages/ForgotPasswordPage';
import ResetPasswordPage from './pages/ResetPasswordPage';
import VerifyEmailPage from './pages/VerifyEmailPage';
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
                    <Route element={<AppShell />}>
                        <Route path="home" element={<HomePage />} />
                        <Route path="painel" element={<HomePage />} />
                        <Route path="estabelecimentos" element={<EstablishmentsPage />} />
                        <Route path="estabelecimentos/:estabelecimentoId" element={<EstabelecimentoDetailsPage />} />
                        <Route path="estabelecimentos/:estabelecimentoId/editar" element={<EstabelecimentoFormPage />} />
                        <Route path="cobranca" element={<CobrancaPage />} />
                        <Route path="cobranca/pix" element={<CobrancaPixPage />} />
                        <Route path="cobranca/credito-vista" element={<CobrancaCreditoVistaPage />} />
                        <Route path="cobranca/cartao-credito" element={<CobrancaCartaoCreditoPage />} />
                        <Route path="cobranca/boleto" element={<CobrancaBoletoPage />} />
                        <Route path="cobranca/planos" element={<CobrancaPlanoContratadoPage />} />
                        <Route path="cobranca/planos/:planoId" element={<CobrancaPlanoContratadoPage />} />
                        <Route path="cobranca/saldoextrato" element={<CobrancaSaldoExtratoPage />} />
                        <Route path="cobranca/simular" element={<CobrancaSimularPage />} />
                        <Route path="links-pagamento" element={<LinksPagamentoPage />} />
                        <Route path="links-pagamento/novo" element={<LinkPagamentoFormPage />} />
                        <Route path="links-pagamento-pix/:linkId" element={<LinkPagamentoPixDetailPage />} />
                        <Route path="links-pagamento/:linkId/editar" element={<LinkPagamentoFormPage />} />
                        <Route path="seller/products" element={<CheckoutProductsPage />} />
                        <Route path="seller/products/novo" element={<CheckoutProductFormPage />} />
                        <Route path="seller/products/:productId/editar" element={<CheckoutProductFormPage />} />
                        <Route path="seller/checkout-links" element={<CheckoutLinksPage />} />
                        <Route path="seller/checkout-links/novo" element={<CheckoutLinkFormPage />} />
                        <Route path="seller/checkout-links/:checkoutLinkId/editar" element={<CheckoutLinkFormPage />} />
                        <Route path="seller/checkout-links/:checkoutLinkId/vendas" element={<CheckoutLinkSalesPage />} />
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

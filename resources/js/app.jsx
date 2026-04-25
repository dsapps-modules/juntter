import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import 'antd/dist/reset.css';
import App from './spa/App';
import SpaErrorBoundary from './spa/components/SpaErrorBoundary';

const rootElement = document.getElementById('app');

function renderRuntimeFallback(message) {
    if (!rootElement) {
        return;
    }

    rootElement.innerHTML = `
        <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 32px; font-family: 'Manrope', 'Segoe UI', sans-serif; background: linear-gradient(180deg, #ffffff 0%, #f5f2ea 100%); color: #171412;">
            <div style="max-width: 720px; width: 100%; border-radius: 24px; border: 1px solid rgba(34, 26, 11, 0.08); background: rgba(255,255,255,0.92); box-shadow: 0 24px 70px rgba(48, 38, 15, 0.08); padding: 28px;">
                <div style="letter-spacing: 0.12em; text-transform: uppercase; font-size: 11px; font-weight: 700; color: #6f6a60; margin-bottom: 12px;">Juntter SPA</div>
                <h1 style="margin: 0 0 12px; font-size: 32px; line-height: 1.1;">Não foi possível carregar a interface.</h1>
                <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.6; color: #6f6a60;">${message}</p>
                <button type="button" onclick="window.location.reload()" style="appearance: none; border: 0; border-radius: 14px; padding: 12px 18px; font-size: 15px; font-weight: 700; color: #201700; background: linear-gradient(180deg, #ffdf5d 0%, #f4c400 100%); box-shadow: 0 14px 30px rgba(244, 196, 0, 0.3); cursor: pointer;">Recarregar</button>
            </div>
        </div>
    `;
}

window.addEventListener('error', (event) => {
    renderRuntimeFallback(event.message || 'Ocorreu um erro de execução na SPA.');
});

window.addEventListener('unhandledrejection', (event) => {
    const reason = event.reason instanceof Error ? event.reason.message : 'Ocorreu uma falha inesperada na SPA.';
    renderRuntimeFallback(reason);
});

if (rootElement) {
    try {
        createRoot(rootElement).render(
            <React.StrictMode>
                <SpaErrorBoundary>
                    <App />
                </SpaErrorBoundary>
            </React.StrictMode>,
        );
    } catch (error) {
        renderRuntimeFallback(error instanceof Error ? error.message : 'Ocorreu um erro de inicialização.');
    }
}

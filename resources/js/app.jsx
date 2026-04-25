import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import 'antd/dist/reset.css';
import App from './spa/App';

const rootElement = document.getElementById('app');

if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <App />
        </React.StrictMode>,
    );
}

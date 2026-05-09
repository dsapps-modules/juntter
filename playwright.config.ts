import { defineConfig } from '@playwright/test';

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? process.env.APP_URL ?? 'http://127.0.0.1:8000';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    expect: {
        timeout: 5_000,
    },
    use: {
        baseURL,
        headless: true,
        screenshot: 'only-on-failure',
        trace: 'on-first-retry',
    },
});

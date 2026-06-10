import { expect, test } from '@playwright/test';

test('spa login page renders the Ant Design form', async ({ page }) => {
    await page.goto('/app/login');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.spa-auth-page')).toBeVisible();
    await expect(page.locator('.spa-auth-logo-image')).toBeVisible();
    await expect(page.getByText('Pagamentos de forma segura e facilitada')).toBeVisible();
    await expect(page.getByText('A Juntter oferece maquininhas com taxas competitivas com suporte em todo o Brasil')).toBeVisible();
    await expect(page.getByText('Segurança')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Login' })).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Entrar' })).toBeVisible();
    await expect(page.getByText('Recuperar senha')).toHaveCount(0);
    await expect(page.locator('a[href="/register"]')).toHaveCount(0);
    await expect(page.getByText('Ir para a home')).toHaveCount(0);
    await expect(page.getByText('Login visual')).toHaveCount(0);
    await expect(page.getByText('de sessão')).toHaveCount(0);
});

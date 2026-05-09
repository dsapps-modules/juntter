import { expect, test } from '@playwright/test';

test('spa login page renders the Ant Design form', async ({ page }) => {
    await page.goto('/app/login');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.spa-auth-page')).toBeVisible();
    await expect(page.getByText('Login da plataforma')).toBeVisible();
    await expect(page.getByLabel('E-mail')).toBeVisible();
    await expect(page.getByLabel('Senha')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Entrar' })).toBeVisible();
});

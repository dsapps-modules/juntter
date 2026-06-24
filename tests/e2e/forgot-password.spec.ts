import { expect, test } from '@playwright/test';

test('spa forgot-password page renders the recovery form', async ({ page }) => {
    await page.goto('/app/forgot-password');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.spa-auth-page')).toBeVisible();
    await expect(page.locator('.spa-auth-logo-image')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Recuperar acesso' })).toBeVisible();
    await expect(page.locator('input')).toBeVisible();
    await expect(page.getByRole('button', { name: /Enviar instru/ })).toBeVisible();
    await expect(page.getByText('RecuperaÃ§Ã£o')).toHaveCount(0);
    await expect(page.getByText('Juntter', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Informe o e-mail da conta para receber o link de redefiniÃ§Ã£o.')).toHaveCount(0);
});

test('spa login page forwards the typed email to the recovery page', async ({ page }) => {
    await page.goto('/app/login');
    await page.waitForLoadState('networkidle');

    await page.locator('input[name="email"]').fill('cliente@exemplo.com');
    await page.getByRole('link', { name: 'Esqueci a senha' }).click();

    await expect(page).toHaveURL(/\/app\/forgot-password\?email=cliente%40exemplo\.com$/);
    await expect(page.locator('input').first()).toHaveValue('cliente@exemplo.com');
});

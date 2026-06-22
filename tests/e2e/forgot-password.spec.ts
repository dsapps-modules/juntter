import { expect, test } from '@playwright/test';

test('spa forgot-password page renders the recovery form', async ({ page }) => {
    await page.goto('/app/forgot-password');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.spa-auth-page')).toBeVisible();
    await expect(page.locator('.spa-auth-logo-image')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Recuperar acesso' })).toBeVisible();
    await expect(page.locator('input')).toBeVisible();
    await expect(page.getByRole('button', { name: /Enviar instru/ })).toBeVisible();
    await expect(page.getByText('Recuperação')).toHaveCount(0);
    await expect(page.getByText('Juntter', { exact: true })).toHaveCount(0);
    await expect(page.getByText('Informe o e-mail da conta para receber o link de redefinição.')).toHaveCount(0);
});

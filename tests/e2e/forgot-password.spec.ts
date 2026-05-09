import { expect, test } from '@playwright/test';

test('spa forgot-password page renders the recovery form', async ({ page }) => {
    await page.goto('/app/forgot-password');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.spa-auth-page')).toBeVisible();
    await expect(page.getByText('Recuperar acesso')).toBeVisible();
    await expect(page.getByText('E-mail')).toBeVisible();
    await expect(page.getByRole('button', { name: /Enviar instru/ })).toBeVisible();
});

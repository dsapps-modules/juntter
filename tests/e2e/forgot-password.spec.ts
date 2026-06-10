import { expect, test } from '@playwright/test';

test('spa forgot-password page renders the recovery form', async ({ page }) => {
    await page.goto('/app/forgot-password');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('.spa-auth-page')).toBeVisible();
    await expect(page.locator('.spa-auth-logo-image')).toBeVisible();
    await expect(page.getByText('Recuperar acesso')).toBeVisible();
    await expect(page.locator('input').first()).toBeVisible();
    await expect(page.getByRole('button', { name: /Enviar instru/ })).toBeVisible();
    await expect(page.getByText('sem sair da nova experiência visual')).toHaveCount(0);
    await expect(page.getByText('O fluxo de recuperação')).toHaveCount(0);
});

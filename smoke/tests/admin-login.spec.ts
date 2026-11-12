import { expect, test } from '@playwright/test';

test('admin login loads', async ({ page }) => {
  const res = await page.goto('http://localhost:4322/login');
  expect(res?.status()).toBe(200);
  await expect(page.locator('#staff-login-form')).toBeVisible();
  await expect(page.locator('input[name="email"]')).toHaveValue('admin@wibustore.vn');
});

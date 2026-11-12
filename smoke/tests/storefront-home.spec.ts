import { expect, test } from '@playwright/test';

test('storefront home loads', async ({ page }) => {
  const res = await page.goto('http://localhost:4321/');
  expect(res?.status()).toBe(200);
  await expect(page).toHaveTitle(/.+/);
  await expect(page.locator('#site-header')).toBeVisible();
  await expect(page.locator('body')).toContainText(/Wibu/i);
});

import { expect, test } from '@playwright/test';

test('product page loads', async ({ page }) => {
  const res = await page.goto('http://localhost:4321/products/cosplay-50-seed');
  expect(res?.status()).toBe(200);
  await expect(page.locator('body')).toContainText(/Tanjiro/);
});

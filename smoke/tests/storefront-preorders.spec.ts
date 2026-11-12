import { expect, test } from '@playwright/test';

test('pre-orders page loads', async ({ page }) => {
  const res = await page.goto('http://localhost:4321/pre-orders');
  expect(res?.status()).toBe(200);
  await expect(page.locator('body')).toContainText(/đặt trước|dat truoc|pre-order/i);
});

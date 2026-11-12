import { expect, test } from '@playwright/test';

// Unauthenticated → requireStaff redirects to /login.
test('admin orders requires login', async ({ page }) => {
  await page.goto('http://localhost:4322/orders');
  await expect(page).toHaveURL(/\/login/);
  await expect(page.locator('#staff-login-form')).toBeVisible();
});

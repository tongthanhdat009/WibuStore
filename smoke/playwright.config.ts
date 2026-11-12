import { defineConfig } from '@playwright/test';

// Smoke only: static load checks against preview servers on :4321/:4322.
// Uses host google-chrome (channel: 'chrome') so no browser download needed.
// No webServer here: start previews first (frontend/admin `pnpm preview`).
export default defineConfig({
  outputDir: '/tmp/wibu-smoke-results',
  reporter: 'list',
  retries: 0,
  testDir: './tests',
  timeout: 30000,
  use: { channel: 'chrome', headless: true },
});

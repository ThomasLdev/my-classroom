import { defineConfig, devices } from '@playwright/test';

// baseURL targets the running app over the compose network (the `php` service
// listens on :80). Override with PLAYWRIGHT_BASE_URL when running elsewhere.
export default defineConfig({
  testDir: './tests',
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  // In CI also emit an HTML report (uploaded as an artifact) alongside line output.
  reporter: process.env.CI
    ? [['line'], ['html', { open: 'never' }]]
    : 'list',
  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://php',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});

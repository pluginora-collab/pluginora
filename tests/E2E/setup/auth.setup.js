import fs from 'node:fs/promises';
import path from 'node:path';
import { test as setup, expect } from '@playwright/test';
import { requireEnv } from '../helpers/pluginora.js';

const authFile = 'tests/E2E/.auth/admin.json';

setup('authenticate as a WordPress administrator', async ({ page }) => {
    const username = requireEnv('PLUGINORA_E2E_ADMIN_USERNAME');
    const password = requireEnv('PLUGINORA_E2E_ADMIN_PASSWORD');

    await fs.mkdir(path.dirname(authFile), { recursive: true });

    await page.goto('/wp-login.php');
    await page.getByLabel('Username or Email Address').fill(username);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Log In' }).click();

    await page.waitForURL(/wp-admin/);
    await expect(page.locator('body')).toContainText(/Dashboard|Pluginora|WooCommerce/);
    await page.context().storageState({ path: authFile });
});
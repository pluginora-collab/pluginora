import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { config as loadEnv } from 'dotenv';
import { defineConfig, devices } from '@playwright/test';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

loadEnv({ path: path.join(__dirname, 'tests/E2E/.env') });
loadEnv({ path: path.join(__dirname, 'tests/E2E/.env.local'), override: true });

export default defineConfig({
    testDir: './tests/E2E/specs',
    fullyParallel: false,
    timeout: 90000,
    expect: {
        timeout: 10000,
    },
    reporter: [
        ['list'],
        ['html', { open: 'never', outputFolder: 'playwright-report' }],
    ],
    outputDir: 'test-results/playwright',
    use: {
        baseURL: process.env.PLUGINORA_E2E_BASE_URL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'setup',
            testMatch: /.*\.setup\.js/,
        },
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                storageState: 'tests/E2E/.auth/admin.json',
            },
            dependencies: ['setup'],
        },
    ],
});
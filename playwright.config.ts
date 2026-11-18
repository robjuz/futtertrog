import { defineConfig, devices } from '@playwright/test';
import { existsSync } from 'node:fs';

export default defineConfig({
    testDir: './tests/Browser',
    outputDir: './test-results',
    fullyParallel: false,
    workers: 1,
    timeout: 60_000,
    use: {
        actionTimeout: 10_000,
        baseURL: 'http://127.0.0.1:8764',
        trace: 'retain-on-failure',
        launchOptions: {
            executablePath: process.env.PLAYWRIGHT_CHROME ?? (existsSync('/usr/bin/google-chrome') ? '/usr/bin/google-chrome' : `${process.env.HOME}/.cache/ms-playwright/chromium-1187/chrome-linux/chrome`),
            args: ['--no-sandbox', '--disable-background-networking', '--host-resolver-rules=MAP * ~NOTFOUND, EXCLUDE 127.0.0.1'],
        },
    },
    projects: [
        { name: 'desktop', use: { ...devices['Desktop Chrome'] } },
        { name: 'mobile', use: { ...devices['Pixel 7'], defaultBrowserType: 'chromium' } },
    ],
    webServer: {
        command: 'php tests/Browser/seed.php && php artisan serve --host=127.0.0.1 --port=8764 --no-interaction',
        url: 'http://127.0.0.1:8764/login',
        reuseExistingServer: false,
        timeout: 60_000,
        env: {
            APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: 'http://127.0.0.1:8764',
            DB_CONNECTION: 'sqlite', DB_DATABASE: `${process.cwd()}/storage/panel-browser.sqlite`,
            MAIL_MAILER: 'array', QUEUE_CONNECTION: 'sync', SESSION_DRIVER: 'file', CACHE_STORE: 'file',
            FILAMENT_ADMIN_PATH: 'admin',
            HOLZKE_ENABLED: 'false', GOURMETTA_ENABLED: 'false', CALL_A_PIZZA_ENABLED: 'false', FLASCHENPOST_ENABLED: 'false',
        },
    },
});

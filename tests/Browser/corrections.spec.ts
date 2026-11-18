import { test, expect, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';

const date = new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10);

test.beforeEach(() => {
    execFileSync('php', ['tests/Browser/seed.php', '--ordering', '--english'], {
        env: { ...process.env, APP_ENV: 'testing', DB_CONNECTION: 'sqlite',
            DB_DATABASE: `${process.cwd()}/storage/panel-browser.sqlite`, MAIL_MAILER: 'array',
            HOLZKE_ENABLED: 'false', GOURMETTA_ENABLED: 'false', CALL_A_PIZZA_ENABLED: 'false', FLASCHENPOST_ENABLED: 'false' },
    });
});

async function login(page: Page) {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill('ui-user@example.test');
    await page.locator('input[name="password"]').fill('browser-secret-123');
    await page.locator('button[type="submit"]').click();
    await expect(page).toHaveURL(/\/$/);
}

async function chooseMeal(page: Page) {
    await page.getByRole('link', { name: 'Order meals', exact: true }).click();
    await page.getByLabel('Date', { exact: true }).fill(date);
    await page.getByRole('button', { name: 'Show selection', exact: true }).click();
    return page.getByRole('article').filter({ has: page.getByRole('heading', { name: 'Browser-Pasta', exact: true }) });
}

test('English navigation, ordering, errors, profile and area switches follow the saved language', async ({ page, context, isMobile }) => {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    await login(page);
    await expect(page.getByRole('heading', { name: 'Overview', exact: true })).toBeVisible();
    await expect(page.getByRole('navigation', { name: 'Main navigation' })).toBeVisible();
    const card = await chooseMeal(page);
    await card.getByLabel('Quantity').fill('2');
    await page.route('**/order_items', route => route.fulfill({ status: 403, contentType: 'application/json', body: '{}' }));
    await card.getByRole('button', { name: 'Order meals', exact: true }).click();
    await expect(card.getByRole('alert')).toContainText('The ordering deadline has passed');
    await page.unroute('**/order_items');
    await card.getByRole('button', { name: 'Order meals', exact: true }).click();
    await expect(card.getByText('Order confirmed by the server.', { exact: true })).toBeVisible();
    await expect(card.getByText(/Order status:/)).toContainText('Not yet ordered from the supplier');
    await page.getByRole('link', { name: 'My orders', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'My orders', exact: true })).toBeVisible();
    await page.getByRole('link', { name: 'My balance', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Current balance', exact: true })).toBeVisible();
    await page.getByRole('link', { name: 'My profile', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Account details', exact: true })).toBeVisible();
    await page.getByRole('link', { name: 'Administration', exact: true }).click();
    await expect(page).toHaveURL(/\/admin/);
    if (isMobile) await page.getByRole('button', { name: 'Expand sidebar', exact: true }).click();
    await page.getByRole('link', { name: 'Bestellen', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Order meals', exact: true })).toBeVisible();
    await page.getByRole('link', { name: 'Overview', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Overview', exact: true })).toBeVisible();
    await page.evaluate(async () => { await navigator.serviceWorker.ready; });
    await expect.poll(() => page.evaluate(() => !!navigator.serviceWorker.controller)).toBe(true);
    await context.setOffline(true);
    await expect(page.getByRole('alert').filter({ hasText: 'You are offline' })).toBeVisible();
    await page.goto('/my/orders').catch(() => {});
    await expect(page.getByRole('heading', { name: 'You are offline', exact: true })).toBeVisible();
    await expect(page.getByText(/No new server confirmation/)).toBeVisible();
    await context.setOffline(false);
    expect(errors).toEqual([]);
});

test('password change rotates the token; ordering and logout work without reload or Fetch Metadata', async ({ page }) => {
    await login(page);
    // Keep one document throughout password change and subsequent ordering.
    await page.evaluate(() => { (window as Window & { correctionDocument?: boolean }).correctionDocument = true; });
    const staleToken = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    await page.getByRole('link', { name: /Mein Profil|My profile/, exact: true }).click();
    await page.getByRole('link', { name: /Sicherheit|Security/, exact: true }).click();
    await page.locator('#current_password').fill('browser-secret-123');
    await page.locator('#password').fill('browser-new-secret-456');
    await page.locator('#password_confirmation').fill('browser-new-secret-456');
    await page.getByRole('button', { name: /Passwort ändern|Change password/, exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: /Passwort geändert|Password changed/ })).toBeVisible();
    await expect(page.locator('meta[name="csrf-token"]')).toHaveAttribute('content', staleToken!);
    // route.fetch sends through the API client, so Chromium cannot reinsert Sec-Fetch-Site.
    // A deliberate stale-token request must fail, proving the server takes its token path.
    const probe = await page.request.post('/order_items', {
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': staleToken!, 'Sec-Fetch-Site': '' },
        data: { meal_id: 0 },
    });
    expect(probe.status()).toBe(419);
    const tokenRequests: { path: string; status: number; token: string | undefined }[] = [];
    await page.route('**/*', async route => {
        const request = route.request();
        const path = new URL(request.url()).pathname;
        if (request.method() === 'GET' || !['/order_items', '/logout'].includes(path)) return route.continue();
        const headers = { ...request.headers() };
        delete headers['sec-fetch-site'];
        const response = await route.fetch({ headers, maxRedirects: 0 });
        tokenRequests.push({ path, status: response.status(), token: headers['x-xsrf-token'] });
        await route.fulfill({ response });
    });
    await page.getByRole('link', { name: /Bestellen|Order meals/, exact: true }).click();
    await page.locator('#meal-date').fill(date);
    await page.locator('form').filter({ has: page.locator('#meal-date') }).getByRole('button', { name: /Auswahl anzeigen|Show selection/ }).click();
    const card = page.getByRole('article').filter({ has: page.getByRole('heading', { name: 'Browser-Pasta', exact: true }) });
    await card.getByRole('button', { name: /Bestellen|Order meals/, exact: true }).click();
    await expect(card.getByText(/Bestellung vom Server bestätigt\.|Order confirmed by the server\./)).toBeVisible();
    expect(await page.evaluate(() => (window as Window & { correctionDocument?: boolean }).correctionDocument)).toBe(true);
    await page.getByRole('button', { name: /Abmelden|Sign out/, exact: true }).click();
    await expect(page).toHaveURL(/\/login$/);
    expect(tokenRequests.map(request => [request.path, request.status])).toEqual([['/order_items', 201], ['/logout', 302]]);
    expect(tokenRequests.every(request => !!request.token)).toBe(true);
    await page.goto('/my/orders');
    await expect(page).toHaveURL(/\/login$/);
});

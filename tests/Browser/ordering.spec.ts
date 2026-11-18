import { test, expect, type Page } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';
import { runInNewContext } from 'node:vm';
import { readFileSync, writeFileSync } from 'node:fs';

const date = new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10);
const nextDate = new Date(Date.now() + 8 * 86400000).toISOString().slice(0, 10);
let errors: string[];

test.beforeEach(async ({ page }) => {
    execFileSync('php', ['tests/Browser/seed.php', '--ordering'], {
        env: { ...process.env, APP_ENV: 'testing', DB_CONNECTION: 'sqlite',
            DB_DATABASE: `${process.cwd()}/storage/panel-browser.sqlite`, MAIL_MAILER: 'array',
            HOLZKE_ENABLED: 'false', GOURMETTA_ENABLED: 'false', CALL_A_PIZZA_ENABLED: 'false', FLASCHENPOST_ENABLED: 'false' },
    });
    errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/login');
    await page.getByLabel('E-Mail').fill('ui-user@example.test');
    await page.locator('input[name="password"]').fill('browser-secret-123');
    await page.locator('button[type="submit"]').click();
    await expect(page.getByRole('heading', { name: 'Übersicht', exact: true })).toBeVisible();
});
test.afterEach(() => expect(errors).toEqual([]));

async function meals(page: Page) {
    await page.getByRole('link', { name: 'Bestellen', exact: true }).click();
    await page.getByLabel('Datum', { exact: true }).fill(date);
    await page.getByLabel('Anbieter', { exact: true }).selectOption('Internal');
    await page.getByRole('button', { name: 'Auswahl anzeigen' }).click();
    await expect(page).toHaveURL(new RegExp(`date=${date}.*provider=Internal`));
    return page.getByRole('article').filter({ has: page.getByRole('heading', { name: 'Browser-Pasta', exact: true }) });
}

test('navigation and selection retain URL, history, scroll, variant and server-confirmed quantity', async ({ page }) => {
    let card = await meals(page);
    await card.getByLabel('Variante').selectOption(await card.getByRole('option', { name: /Groß/ }).getAttribute('value') ?? '');
    await card.getByLabel('Menge').fill('3');
    let release!: () => void;
    const held = new Promise<void>(resolve => { release = resolve; });
    await page.route('**/order_items', async route => { await held; await route.continue(); });
    await card.getByRole('button', { name: 'Bestellen', exact: true }).click();
    await expect(card.getByRole('button', { name: 'Wird gesendet …' })).toBeDisabled();
    await expect(page.getByRole('article').filter({ has: page.getByRole('heading', { name: 'Tagesgericht 1', exact: true }) }).getByRole('button', { name: 'Bestellen', exact: true })).toBeEnabled();
    await expect(card.getByText('Bestellung vom Server bestätigt.', { exact: true })).toHaveCount(0);
    release();
    await expect(card.getByText('3 × Browser-Pasta Groß', { exact: true })).toBeVisible();
    await expect(card.getByRole('status').filter({ hasText: 'Bestellung vom Server bestätigt.' }).first()).toBeVisible();
    await page.unroute('**/order_items');
    await card.getByLabel('Menge').fill('2');
    await card.getByRole('button', { name: 'Bestellung ändern' }).click();
    await expect(card.getByText('2 × Browser-Pasta Groß', { exact: true })).toBeVisible();
    await page.evaluate(() => window.scrollTo(0, 900));
    const before = await page.evaluate(() => window.scrollY);
    await page.getByRole('link', { name: 'Meine Bestellungen', exact: true }).click();
    await expect(page).toHaveURL(/\/my\/orders$/);
    await expect(page.getByRole('heading', { name: 'Meine Bestellungen', exact: true })).toBeVisible();
    await expect(page.getByText('2 × Browser-Pasta Groß', { exact: true })).toBeVisible();
    await page.goBack();
    await expect(page).toHaveURL(new RegExp(`date=${date}.*provider=Internal`));
    await expect.poll(() => page.evaluate(() => window.scrollY)).toBeCloseTo(before, -1);
    card = page.getByRole('article').filter({ hasText: 'Browser-Pasta' });
    await expect(card.getByLabel('Menge')).toHaveValue('2');
    await expect(card.getByLabel('Variante')).toHaveValue(await card.getByRole('option', { name: /Groß/ }).getAttribute('value') ?? '');
    await page.reload();
    await expect(card.getByText('2 × Browser-Pasta Groß', { exact: true })).toBeVisible();
    await page.getByLabel('Datum', { exact: true }).fill(nextDate);
    await page.getByRole('button', { name: 'Auswahl anzeigen' }).click();
    await expect(page.getByRole('status').filter({ hasText: 'keine Gerichte' })).toBeVisible();
    await page.goBack();
    await expect(page).toHaveURL(new RegExp(`date=${date}.*provider=Internal`));
});

test('overview and cancellation show own balances and confirmed history without administrative controls', async ({ page }) => {
    await expect(page.getByText('1 × Heutiges Essen').first()).toBeVisible();
    await expect(page.getByText('Eigenes Startguthaben')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Administration', exact: true })).toHaveCount(0);
    await page.getByRole('link', { name: 'Meine Bestellungen', exact: true }).click();
    const row = page.getByRole('listitem').filter({ hasText: 'Eigene Vorbestellung' });
    await expect(page.getByRole('link', { name: /Kalender herunterladen/ })).toBeVisible();
    await row.getByRole('button', { name: 'Stornieren' }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Storno vom Server bestätigt.' })).toBeVisible();
    await expect(row).toHaveCount(0);
    await page.getByRole('link', { name: 'Mein Guthaben', exact: true }).click();
    await expect(page.getByText('Eigenes Startguthaben')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Bestellverlauf' })).toBeVisible();
    await expect(page.getByText('Browser-Startguthaben')).toHaveCount(0);
    await expect(page.locator('input[name="user_id"], input[name="is_admin"]')).toHaveCount(0);
    await page.getByRole('link', { name: 'Mein Profil', exact: true }).click();
    await page.getByRole('link', { name: 'Einstellungen', exact: true }).click();
    await expect(page.getByLabel('Vorlieben', { exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Bisherige Einstellungen und Kalenderexport öffnen' })).toBeVisible();
});

test('cleared provider filter remains empty across day and week navigation', async ({ page }) => {
    await meals(page);
    await page.getByLabel('Anbieter', { exact: true }).selectOption('');
    await page.getByRole('button', { name: 'Auswahl anzeigen' }).click();
    await expect(page).not.toHaveURL(/provider=Internal/);
    const days = page.getByRole('navigation', { name: 'Tag wählen', exact: true });
    await expect(days.getByRole('link').first()).not.toHaveAttribute('href', /provider=null/);
    await days.getByRole('link').first().click();
    await expect(page.getByRole('link', { name: 'Vorherige Woche' })).not.toHaveAttribute('href', /provider=null/);
    await page.getByRole('link', { name: 'Vorherige Woche' }).click();
    await expect(page).not.toHaveURL(/provider=(Internal|null)/);
    await expect(page.getByLabel('Anbieter', { exact: true })).toHaveValue('');
});

for (const failure of ['network', '422', '403', '401', '419', '500']) {
    test(`error and retry preserve selection without false success: ${failure}`, async ({ page }) => {
        const card = await meals(page);
        await card.getByLabel('Variante').selectOption(await card.getByRole('option', { name: /Groß/ }).getAttribute('value') ?? '');
        await card.getByLabel('Menge').fill('3');
        await page.route('**/order_items', route => failure === 'network' ? route.abort('failed') : route.fulfill({
            status: Number(failure), contentType: 'application/json', body: JSON.stringify({ errors: { quantity: ['Bitte Menge prüfen.'] } }),
        }));
        await card.getByRole('button', { name: 'Bestellen', exact: true }).click();
        await expect(card.getByRole('alert')).toBeVisible();
        await expect(card.getByText('Bestellung vom Server bestätigt.', { exact: true })).toHaveCount(0);
        await expect(card.getByLabel('Menge')).toHaveValue('3');
        if (['401', '419'].includes(failure)) await expect(card.getByRole('link', { name: /Erneut anmelden/ })).toHaveAttribute('target', '_blank');
        await page.unroute('**/order_items');
        await card.getByRole('button', { name: 'Bestellen', exact: true }).click();
        await expect(card.getByText('3 × Browser-Pasta Groß', { exact: true })).toBeVisible();
        await expect(card.getByRole('alert')).toHaveCount(0);
    });
}

test('page navigation reports loading, network and expired-session states on dashboard and histories', async ({ page, context }) => {
    let release!: () => void;
    const held = new Promise<void>(resolve => { release = resolve; });
    await page.route('**/my/balance', async route => { await held; await route.abort('failed'); });
    await page.getByRole('link', { name: 'Mein Guthaben', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Seite wird geladen' })).toBeVisible();
    release();
    await expect(page.getByRole('alert').filter({ hasText: 'Die Seite wurde nicht geladen' })).toBeVisible();
    await page.unroute('**/my/balance');
    await page.getByRole('link', { name: 'Mein Guthaben', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Mein Guthaben', exact: true })).toBeVisible();
    await page.route('**/my/orders', route => route.fulfill({ status: 419, contentType: 'application/json', body: '{}' }));
    await page.getByRole('link', { name: 'Meine Bestellungen', exact: true }).click();
    await expect(page.getByRole('alert').filter({ hasText: 'Sitzung ist abgelaufen' })).toBeVisible();
    await page.unroute('**/my/orders');
    await page.getByRole('link', { name: 'Meine Bestellungen', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Meine Bestellungen', exact: true })).toBeVisible();
    await context.clearCookies();
    await page.getByRole('link', { name: 'Mein Guthaben', exact: true }).click();
    await expect(page.getByRole('alert').filter({ hasText: 'Sitzung ist abgelaufen' })).toBeVisible();
    await expect(page.getByRole('link', { name: /Erneut anmelden/ })).toBeVisible();
});

test('accessible ordering supports keyboard, focus, labels, announcements, contrast and reduced motion', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    const card = await meals(page);
    const select = card.getByLabel('Variante');
    await select.focus();
    await page.keyboard.press('ArrowDown');
    await page.keyboard.press('Tab');
    await expect(card.getByLabel('Menge')).toBeFocused();
    await card.getByLabel('Menge').fill('2');
    await page.keyboard.press('Tab');
    await expect(card.getByRole('button', { name: 'Bestellen', exact: true })).toBeFocused();
    expect(await card.getByRole('button', { name: 'Bestellen', exact: true }).evaluate(el => getComputedStyle(el).outlineStyle)).not.toBe('none');
    await page.keyboard.press('Enter');
    await expect(card.getByRole('heading')).toBeFocused();
    await expect(card.locator('[aria-live="polite"]').filter({ hasText: 'Bestellung vom Server bestätigt.' }).last()).toBeVisible();
    expect(await page.evaluate(() => matchMedia('(prefers-reduced-motion: reduce)').matches)).toBe(true);
    expect(await card.getByRole('button', { name: 'Bestellung ändern' }).evaluate(el => getComputedStyle(el).transitionDuration)).toBe('0s');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await page.screenshot({ path: `/tmp/package5-${test.info().project.name}.png`, fullPage: true });
    for (const path of [`/meals?date=${date}`, '/', '/my/orders', '/my/balance', '/profile', '/profile/preferences']) {
        await page.goto(path);
        const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
        expect(result.violations.map(v => ({ id: v.id, nodes: v.nodes.map(n => n.target) }))).toEqual([]);
    }
});

test('offline blocks mutations, does not queue and supplies an honest navigation fallback', async ({ page, context }) => {
    const card = await meals(page);
    await page.evaluate(async () => { await navigator.serviceWorker.ready; });
    await expect.poll(() => page.evaluate(() => !!navigator.serviceWorker.controller)).toBe(true);
    let mutations = 0;
    page.on('request', request => { if (request.method() === 'POST' && request.url().endsWith('/order_items')) mutations++; });
    await context.setOffline(true);
    await expect(page.getByRole('alert').filter({ hasText: 'Sie sind offline' })).toBeVisible();
    await card.getByRole('button', { name: 'Bestellen', exact: true }).click();
    await expect(card.getByRole('alert')).toContainText('offline');
    await expect(card.getByText('Bestellung vom Server bestätigt.', { exact: true })).toHaveCount(0);
    await context.setOffline(false);
    expect(mutations).toBe(0);
    await context.setOffline(true);
    await page.goto(`/my/orders`).catch(() => {});
    await expect(page.getByRole('heading', { name: 'Sie sind offline' })).toBeVisible();
    await expect(page.getByText(/keine neue Serverbestätigung/)).toBeVisible();
    await context.setOffline(false);
});

test('legacy worker and fixed-name cache are replaced; private responses and admin assets stay out of cache', async ({ page, context }) => {
    await page.evaluate(async () => { await navigator.serviceWorker.ready; });
    await expect.poll(() => page.evaluate(() => !!navigator.serviceWorker.controller)).toBe(true);
    await page.evaluate(async () => {
        const registrations = await navigator.serviceWorker.getRegistrations();
        await Promise.all(registrations.map(registration => registration.unregister()));
    });
    const oldWorker = `self.addEventListener('install', e => e.waitUntil(caches.open('futtertrog_2022_06_07_1').then(async c => {
        await c.put('/js/app.js', new Response('window.staleAsset = true'));
        await c.put('/css/app.css', new Response('body { display:none }'));
        await c.put('/my/orders', new Response('private stale response'));
    }).then(() => self.skipWaiting())));
    self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));
    self.addEventListener('fetch', e => e.respondWith(caches.match(e.request).then(r => r || fetch(e.request))));`;
    // Chromium does not route the main service-worker script through Playwright routing.
    // Serve the old bytes briefly at the real URL; always restore before testing the update.
    const currentWorker = readFileSync('public/serviceworker.js', 'utf8');
    try {
        writeFileSync('public/serviceworker.js', oldWorker);
        expect(await (await page.request.get('/serviceworker.js')).text()).toBe(oldWorker);
        await page.evaluate(async () => {
            await caches.open('unrelated-cache');
            const registration = await navigator.serviceWorker.register('/serviceworker.js', { scope: '/', updateViaCache: 'none' });
            await registration.update();
        });
        await expect.poll(() => page.evaluate(async () => (await caches.keys()).includes('futtertrog_2022_06_07_1'))).toBe(true);
        await expect.poll(() => page.evaluate(async () => (await fetch('/js/app.js')).text())).toContain('staleAsset');
    } finally {
        writeFileSync('public/serviceworker.js', currentWorker);
    }
    await page.evaluate(async () => { const registration = await navigator.serviceWorker.getRegistration('/'); await registration?.update(); });
    await expect.poll(() => page.evaluate(async () => (await caches.keys()).filter(name => name.startsWith('futtertrog_')))).toEqual([]);
    expect(await page.evaluate(async () => (await fetch('/js/app.js')).text())).not.toContain('staleAsset');
    const worker = readFileSync('public/serviceworker.js', 'utf8');
    const listeners = new Map<string, (event: { request: Request; respondWith: () => void }) => void>();
    runInNewContext(worker, { URL, self: { location: { origin: 'http://127.0.0.1:8764' },
        addEventListener: (name: string, callback: (event: never) => void) => listeners.set(name, callback as never) } });
    for (const [path, mode] of [['/admin', 'navigate'], ['/admin/people', 'navigate'], ['/build/assets/app-version.js', 'cors'],
        ['/css/app.css', 'cors'], ['/js/app.js', 'cors'], ['/meals', 'cors'], ['/order_items', 'cors']]) {
        let intercepted = false;
        listeners.get('fetch')?.({ request: { url: `http://127.0.0.1:8764${path}`, mode } as Request,
            respondWith: () => { intercepted = true; } });
        expect(intercepted, path).toBe(false);
    }
    await page.goto('/my/balance');
    await page.evaluate(async () => { await fetch('/order_items', { headers: { Accept: 'application/json' } }); });
    await page.goto('/profile');
    const admin = await page.goto('/admin');
    expect(admin?.status()).toBe(403);
    expect(await page.evaluate(async () => {
        const names = await caches.keys();
        return (await Promise.all(names.map(async name => (await (await caches.open(name)).keys()).map(r => r.url)))).flat();
    })).toEqual([]);
    expect(await page.evaluate(async () => (await caches.keys()).includes('unrelated-cache'))).toBe(true);
    await context.setOffline(true);
    await page.goto('/admin').catch(() => {});
    await expect(page.getByRole('heading', { name: 'Sie sind offline' })).toHaveCount(0);
    await context.setOffline(false);
    const manifest = await page.request.get('/manifest.json');
    expect(new URL((await manifest.json()).start_url).pathname).toBe('/');
});

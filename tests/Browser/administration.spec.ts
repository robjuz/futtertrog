import { test, expect, type Page, type Locator } from '@playwright/test';
import { execFileSync } from 'node:child_process';

test.beforeEach(() => {
    execFileSync('php', ['tests/Browser/seed.php'], {
        env: { ...process.env, APP_ENV: 'testing', DB_CONNECTION: 'sqlite',
            DB_DATABASE: `${process.cwd()}/storage/panel-browser.sqlite`, MAIL_MAILER: 'array',
            HOLZKE_ENABLED: 'false', GOURMETTA_ENABLED: 'false', CALL_A_PIZZA_ENABLED: 'false', FLASCHENPOST_ENABLED: 'false' },
    });
});

async function login(page: Page, email = 'panel-admin@example.test') {
    await page.goto('/login');
    await page.locator('input[name="email"]').fill(email);
    await page.locator('input[name="password"]').fill('browser-secret-123');
    await page.locator('button[type="submit"]').click();
    await expect(page).not.toHaveURL(/\/login$/);
}

async function confirm(page: Page) {
    const dialog = page.getByRole('alertdialog').last();
    await dialog.getByRole('button', { name: 'Bestätigen', exact: true }).click();
    await expect(page.getByText('Vorgang erfolgreich.', { exact: true }).last()).toBeVisible();
    await expect(dialog.getByRole('button', { name: 'Bestätigen', exact: true })).not.toBeVisible();
    await page.waitForFunction(() => document.querySelectorAll('.fi-modal-open').length === 0);
}

async function choose(dialog: Locator, label: string, option: string | RegExp) {
    await dialog.getByRole('button', { name: new RegExp(`^${label}`) }).click();
    await dialog.getByRole('option', { name: option, exact: typeof option === 'string' }).click();
}

test('admin manages users, meals, orders, finances and messages with confirmation', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    await login(page);
    await page.goto('/admin');
    await expect(page.getByText('Offene Sammelbestellungen')).toBeVisible();

    await page.goto('/admin/people');
    await page.getByRole('button', { name: 'Benutzer anlegen', exact: true }).click();
    let userDialog = page.getByRole('alertdialog').last();
    await expect(userDialog.locator('input[type="password"]')).toHaveCount(0);
    await expect(userDialog.getByText(/Das Konto startet ohne lokales Passwort/)).toBeVisible();
    await userDialog.getByLabel('Name').fill(`Browserperson ${test.info().project.name}`);
    await userDialog.getByLabel('E-Mail').fill(`browser-${test.info().project.name}@example.test`);
    await confirm(page);
    await page.getByRole('link', { name: 'Passwortbestätigung senden', exact: true }).click();
    await confirm(page);
    const newRow = page.locator('tr').filter({ hasText: `browser-${test.info().project.name}@example.test` });
    await expect(newRow).toBeVisible();
    await newRow.getByRole('button', { name: 'Name / E-Mail ändern', exact: true }).click();
    userDialog = page.getByRole('alertdialog').last();
    await expect(userDialog.locator('input[type="password"]')).toHaveCount(0);
    await userDialog.getByLabel('Name').fill(`Browserperson geändert ${test.info().project.name}`);
    await confirm(page);
    await newRow.getByRole('link', { name: 'Guthaben / Verlauf', exact: true }).click();
    await expect(page.getByText('Konto und Guthaben', { exact: true })).toBeVisible();
    await expect(page.getByText('Keine Bestellungen vorhanden.', { exact: true })).toBeVisible();
    await page.goto('/admin/people');
    await page.getByRole('searchbox').fill('Panel Empfänger');
    const row = page.locator('tr').filter({ hasText: 'panel-user@example.test' });
    await expect(row).toBeVisible();
    await row.getByRole('button', { name: 'Rechte ändern', exact: true }).click();
    await confirm(page);
    await row.getByRole('button', { name: 'Deaktivieren', exact: true }).click();
    await expect(page.getByRole('alertdialog').last().getByRole('heading', { name: 'Deaktivieren' })).toBeVisible();
    await page.getByRole('alertdialog').last().getByRole('button', { name: /abbrechen|cancel/i }).click();
    await page.waitForFunction(() => document.querySelectorAll('.fi-modal-open').length === 0);
    await expect(row).toBeVisible();
    await row.getByRole('button', { name: 'Deaktivieren', exact: true }).click();
    await confirm(page);
    await page.goto('/admin/people?filters%5Btrashed%5D%5Bvalue%5D=0&search=Panel%20Empf%C3%A4nger');
    await row.getByRole('button', { name: 'Wiederherstellen', exact: true }).click();
    await confirm(page);

    await page.goto('/admin/offers');
    await page.getByRole('button', { name: 'Gericht / Variante anlegen', exact: true }).click();
    let dialog = page.getByRole('alertdialog').last();
    await dialog.getByLabel('Titel / Variantenname').fill(`Browserangebot ${test.info().project.name}`);
    await dialog.getByLabel('Preis in Cent').fill('1234');
    await dialog.getByLabel('Datum').fill('2026-11-20');
    await dialog.getByRole('switch', { name: 'Benutzer benachrichtigen' }).click();
    await confirm(page);
    await expect(page.getByText(`Browserangebot ${test.info().project.name}`, { exact: true })).toBeVisible();

    await page.goto('/admin/bookings');
    await page.getByRole('button', { name: 'Einzahlung buchen', exact: true }).click();
    dialog = page.getByRole('alertdialog').last();
    await choose(dialog, 'Benutzer', 'Panel Empfänger');
    await dialog.getByLabel('Betrag in Cent').fill('1234');
    await dialog.getByLabel('Kommentar').fill(`Browserbuchung ${test.info().project.name}`);
    await confirm(page);
    await expect(page.getByText(`Browserbuchung ${test.info().project.name}`, { exact: true })).toBeVisible();

    await page.getByRole('button', { name: 'Guthaben übertragen', exact: true }).click();
    dialog = page.getByRole('alertdialog').last();
    await choose(dialog, 'Von', 'Panel Admin');
    await choose(dialog, 'An', 'Panel Empfänger');
    await dialog.getByLabel('Betrag in Cent').fill('500');
    await dialog.getByLabel('Kommentar').fill('Browserübertrag');
    await confirm(page);

    await page.goto('/admin/order-positions');
    await page.getByRole('button', { name: 'In Stellvertretung bestellen', exact: true }).click();
    dialog = page.getByRole('alertdialog').last();
    await choose(dialog, 'Gericht', /Browsergericht/);
    await choose(dialog, 'Benutzer', 'Panel Empfänger');
    await dialog.getByLabel('Menge').fill('3');
    await confirm(page);

    await page.goto('/admin/collective-orders');
    const orderRow = page.locator('tbody tr').first();
    await orderRow.getByRole('button', { name: 'Bearbeiten / abschließen', exact: true }).click();
    dialog = page.getByRole('alertdialog').last();
    await dialog.getByLabel('Status').selectOption('ordered');
    await confirm(page);
    await expect(orderRow.getByText('Abgeschlossen / bestellt', { exact: true })).toBeVisible();
    await orderRow.getByRole('link', { name: 'Positionen', exact: true }).click();
    await expect(page.getByText('Browsergericht', { exact: true })).toBeVisible();

    await page.goto('/admin/notifications');
    await page.getByRole('button', { name: 'Mitteilung versenden', exact: true }).click();
    dialog = page.getByRole('alertdialog').last();
    await choose(dialog, 'Benutzer', 'Panel Empfänger');
    await dialog.getByLabel('Betreff').fill('Browser-Mitteilung');
    await dialog.getByLabel('Nachricht').fill('Nur Array-Mailer.');
    await confirm(page);
    expect(errors).toEqual([]);
});

test('ordinary users cannot enter panel resources or old administrative links', async ({ page }) => {
    await login(page, 'panel-user@example.test');
    for (const path of ['/admin', '/admin/people', '/admin/offers', '/admin/collective-orders', '/admin/order-positions', '/admin/bookings', '/admin/notifications', '/admin/users', '/deposits/transfer']) {
        const response = await page.goto(path);
        expect(response?.status()).toBe(403);
    }
});

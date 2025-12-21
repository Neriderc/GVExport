import { runSharedOptionsTests } from '../common/sharedOptionTests.ts'
import { test, expect } from '../../fixtures.ts';
import { loadGVExport, getIndividualTile } from '../common/utils.ts';
import { testTileClickOpensPage } from '../common/sharedOptionTests.ts';

/**
 * Run our suite of tests that are common across user and guest
 */
runSharedOptionsTests('user');

test('Add a partner', async ({ page }) => {
    await loadGVExport(page, true);
    await page.locator('#click_action_indi').selectOption('80');
    await testTileClickOpensPage(page, 'Joe BLOGGS', /add-spouse-to-individual/);
});

test('Add a parent', async ({ page }) => {
    await loadGVExport(page, true);
    await page.locator('#click_action_indi').selectOption('90');
    await testTileClickOpensPage(page, 'Joe BLOGGS', /add-parent-to-individual/);
});

test('Add to clippings cart', async ({ page }) => {
    await loadGVExport(page, true);
    await page.getByRole('button', { name: 'Clippings cart', exact: true }).click();
    await page.locator('#highlight_custom_indis').check();
    await page.locator('#click_action_indi').selectOption('100');
    const tile = await getIndividualTile(page, 'Olivia BLOGGS');
    tile.click();
    await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
    await page.waitForSelector('svg');
    await page.getByRole('button', { name: 'Clippings cart' }).click();
    await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
    await expect(page.locator('table a').nth(0)).toContainText('Olivia BLOGGS');
});
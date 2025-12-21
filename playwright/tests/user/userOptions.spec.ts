import { runSharedOptionsTests } from '../common/sharedOptionTests.ts'
import { test, expect } from '../../fixtures.ts';
import { loadGVExport, getIndividualTile } from '../common/utils.ts';
import { testTileClickOpensPage } from '../common/sharedOptionTests.ts';

/**
 * Run our suite of tests that are common across user and guest
 */
runSharedOptionsTests('user');

test.describe('user-only tests for click on indi tile', ()=>{
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
});

test.describe('user-only tests for indi tile context menu', ()=>{
    test('Add a partner', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_indi').selectOption('50');
        await testTileClickOpensPage(page, 'Joe BLOGGS', /add-spouse-to-individual/, 'Add a partner');
    });

    test('Add a parent', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_indi').selectOption('50');
        await testTileClickOpensPage(page, 'Joe BLOGGS', /add-parent-to-individual/, 'Add a parent');
    });

    test('Add to clippings cart', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#highlight_custom_indis').check();
        await page.locator('#click_action_indi').selectOption('50');
        const tile = await getIndividualTile(page, 'Olivia BLOGGS');
        tile.click();
        page.locator('.settings_ellipsis_menu_item', { hasText: 'Add to clippings cart' }).click()
        await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
        await page.waitForSelector('svg');
        await page.getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
        await expect(page.locator('table a').nth(0)).toContainText('Olivia BLOGGS');
    });
});
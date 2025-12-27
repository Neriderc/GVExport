import { runSharedOptionsTests } from '../common/sharedOptionTests.ts'
import { test, expect } from '../../fixtures.ts';
import { loadGVExport, getIndividualTile, getTileByXref } from '../common/utils.ts';
import { testTileClickOpensPage } from '../common/sharedOptionTests.ts';

/**
 * Run our suite of tests that are common across user and guest
 */
runSharedOptionsTests('user');

test('option: Show events as text if no date', async ({ page }) => {
            await loadGVExport(page, true);

            // Check not enabled and not showing in diagram
            await expect(page.locator("#show_event_text_families")).not.toBeChecked();
            let svgHtml = await page.locator('#rendering svg').innerHTML();
            expect(svgHtml).not.toContain('∞ Marriage');

            // Enable
            await page.locator('#show_divorces').check();
            await page.locator('#diagtype_combined').click();

            // Make sure SVG finishes loading after change
            const before = await page.locator('#rendering svg').innerHTML();
            await page.locator('#show_event_text_families').check();
            await expect(page.locator('#rendering svg')).not.toHaveJSProperty('innerHTML', before);

            // Check that it shows in diagram
            svgHtml = await page.locator('#rendering svg').innerHTML();
            expect(svgHtml).toContain('∞ Marriage');
            expect(svgHtml).toContain('⚮ Divorce');
        });

test.describe('option: Action when individual clicked', ()=>{
    test('Add a partner', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_indi').selectOption('80');
        await testTileClickOpensPage(page, 'X1', 'Joe BLOGGS', /add-spouse-to-individual/);
    });

    test('Add a parent', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_indi').selectOption('90');
        await testTileClickOpensPage(page, 'X1', 'Joe BLOGGS', /add-parent-to-individual/);
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

test.describe('option: Action when family clicked', () => {
    test('Open family page', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_fam').selectOption('0');
        await testTileClickOpensPage(page, 'X41', 'Joe BLOGGS + Jane Smith', /family/);
    });
    test('Add a child', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_fam').selectOption('10');
        await testTileClickOpensPage(page, 'X41', 'Joe BLOGGS + Jane Smith', /add-child-to-family/);
    });
    test('Change family members', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_fam').selectOption('50');
        await testTileClickOpensPage(page, 'X41', 'Joe BLOGGS + Jane Smith', /change-family-members/);
    });
    test('Add to clippings cart', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#highlight_custom_fams').check();
        await page.locator('#click_action_fam').selectOption('60');
        const tile = await getTileByXref(page, 'X41');
        tile.click();
        await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
        await page.waitForSelector('svg');
        await page.getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
        await expect(page.locator('table a').nth(0)).toContainText('Joe BLOGGS + Jane Smith');
    });
});

test.describe('user-only tests for indi tile context menu', ()=>{
    test('Add a partner', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_indi').selectOption('50');
        await testTileClickOpensPage(page, 'X1', 'Joe BLOGGS', /add-spouse-to-individual/, 'Add a partner');
    });

    test('Add a parent', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_indi').selectOption('50');
        await testTileClickOpensPage(page, 'X1', 'Joe BLOGGS', /add-parent-to-individual/, 'Add a parent');
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


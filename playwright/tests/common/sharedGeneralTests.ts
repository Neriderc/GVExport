import { test, expect } from '../../fixtures.ts';
import { getTileByXref, loadGVExport, toggleAdvancedPanels, toggleSettingsSubgroups } from '../common/utils.ts'

export function runSharedGeneralTests(role: 'guest' | 'user') {
    test.describe('Check all download types trigger download', () => {
        const options = ['svg', 'pdf', 'png', 'jpg', 'gif', 'ps', 'dot'];

        for (const value of options) {
        test(`Download triggers for ${value}`, async ({ page }) => {
            await loadGVExport(page);

            await page.selectOption('#output_type', value);

            const downloadPromise = page.waitForEvent('download');
            await page.getByRole('button', { name: /Download/i }).click();

            const download = await downloadPromise;
            expect(download).toBeTruthy();
        });
        }
    });

    test.describe('Test help pages load', () => {
        test('Checks help modals do not display info not found message', async ({ page }) => {
            await loadGVExport(page);
            await toggleAdvancedPanels(page);
            await toggleSettingsSubgroups(page);
            await page.locator('#show_diagram_panel').check();
            const infoIcons = page.locator('#gvexport').locator('.info-icon');
            const count = await infoIcons.count();
            expect(count).toBe(18);

            for (let i = 0; i < count; i++) {
                const icon = infoIcons.nth(i);
                await icon.click();
                await expect(page.locator('.help-sidebar'))
                    .not.toContainText('Help information not found');
                await page.locator('.hide-help').click();
            }

            await page.locator('#help-about').click();
            await expect(page.locator('.help-sidebar')).not.toContainText('Help information not found');
            await page.locator('.hide-help').click();
        });
    });

    test.describe('Test that full screen specific options work', () => {
        test('full screen opens and closes', async ( { page } ) => {
            await loadGVExport(page);
            await page.locator('#fullscreenButton').click();
            await expect.poll(() => page.evaluate(() => document.fullscreenElement !== null)).toBe(true);
            await expect(page.locator('#fullscreenClose')).toBeVisible();
            await page.locator('#fullscreenClose').click();
            await expect(page.locator('#fullscreenButton')).toBeVisible();
        });

        test('full screen search', async ( { page } ) => {
            await loadGVExport(page);
            const element = await getTileByXref(page, 'X56');
            await expect(element).not.toBeInViewport();
            await page.locator('#fullscreenButton').click();
            await expect(page.locator('#fullscreenClose')).toBeVisible();

            await page.locator('#searchButton').click();
            await page.locator('#diagram_search_box_container input.dropdown-input').fill('Dylan');
            await expect(page.locator('#diagram_search_box-ts-dropdown')).toContainText('Dylan');
            await page.locator('#diagram_search_box-ts-dropdown').click();
            await page.waitForTimeout(2000);
            await expect(element).toBeInViewport();
        });

        test('show options in fullscreen', async ( { page } ) => {
            await loadGVExport(page);
            await page.locator('#fullscreenButton').click();
            await expect(page.locator('#gvexport')).not.toBeVisible();
            await page.locator('#fullscreenShowMenu').click();
            await expect(page.locator('#gvexport')).toBeVisible();
        });

        test('show help in fullscreen', async ( { page } ) => {
            await loadGVExport(page);
            await expect(page.locator('#fullscreenShowHelp')).not.toBeVisible();
            await page.locator('#fullscreenButton').click();
            await page.locator('#fullscreenShowHelp').click();
            await page.locator('#help-content')
            await expect(page.locator('#help-content')).toContainText('This help contains detailed information');
        });
    });
}
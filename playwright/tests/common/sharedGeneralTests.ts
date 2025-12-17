import { test, expect } from '../../fixtures.ts';
import { loadGVExport, toggleAdvancedPanels, toggleSettingsSubgroups } from '../common/utils.ts'

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
            expect(count).toBe(17);

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
}
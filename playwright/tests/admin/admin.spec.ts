import { test, expect } from '../../fixtures';;
import { checkDefaults } from '../common/defaults.ts';
import { checkNonDefaults, setNonDefaults } from '../common/nondefaults.ts';
import { runDownloadTests } from '../common/sharedGeneralTests.ts';
import { loadGVExport } from '../common/utils.ts';

test.describe('control panel tests', () => {
    

    test('control panel values are saved', async ({ page }) => {
        await page.goto('/module/_GVExport_/Admin');
        await expect(page).toHaveURL('/module/_GVExport_/Admin');

        await setNonDefaults(page, true);
        await checkNonDefaults(page, true);
    });

    test.describe('Downloads work when Graphviz disabled', () => {
        test('Setup by disabling graphviz', async ({ page }) => {
            await page.goto('/module/_GVExport_/Admin');
            await page.getByRole('link', { name: 'reset to defaults' }).click();
            await page.locator('#show_debug_panel').check();
            await page.locator('#enable_graphviz').uncheck();
            await page.getByRole('button', { name: 'save' }).click();
        });
        runDownloadTests(['svg', 'pdf', 'png', 'jpg', 'dot']);
    });

    // It's important this test is run after the above which disabled Graphviz, as it restores the defaults.
    test('control panel defaults are correct', async ({ page }) => {
        await page.goto('/module/_GVExport_/Admin');
        await page.getByRole('link', { name: 'reset to defaults' }).click();
        await page.getByRole('button', { name: 'save' }).click();
        await page.goto('/module/_GVExport_/Admin');
        await checkDefaults(page, true);
    });
});

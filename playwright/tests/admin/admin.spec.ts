import { test, expect } from '../../fixtures';;
import { checkDefaults } from '../common/defaults.ts';
import { checkNonDefaults, setNonDefaults } from '../common/nondefaults.ts';

test.describe('control panel tests', () => {

    test('control panel values are saved', async ({ page }) => {
        await page.goto('/module/_GVExport_/Admin');
        await expect(page).toHaveURL('/module/_GVExport_/Admin');

        await setNonDefaults(page, true);
        await checkNonDefaults(page, true);
    });

    test('control panel defaults are correct', async ({ page }) => {
        await page.goto('/module/_GVExport_/Admin');
        await page.getByRole('link', { name: 'reset to defaults' }).click();
        await page.getByRole('button', { name: 'save' }).click();
        await page.goto('/module/_GVExport_/Admin');

        await checkDefaults(page, true);
    });
});

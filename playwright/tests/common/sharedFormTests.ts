import { test, expect } from '../../fixtures.ts';
import { checkDefaults } from './defaults.ts';
import { checkNonDefaults, setNonDefaults } from './nondefaults.ts';
import { loadGVExport, clearSavedSettingsList, toggleAdvancedPanels, toggleSettingsSubgroups } from '../common/utils.ts';

export function runSharedFormTests(role: 'guest' | 'user') {
    test.describe('Check form values', () => {
        test('form defaults are correct', async ({ page }) => {
            await loadGVExport(page);
            await checkDefaults(page, false);
        });

        test('non-default form values save', async ({ page }) => {
            await loadGVExport(page);
            await setNonDefaults(page, false);
            await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1'); 
            await checkNonDefaults(page, false);
        });
    });

    test.describe('Check notification when unsupported', () => {
        test('Notification shows for combined diagram and rounded rectangles', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#diagtype_combined').check();
            await page.locator('#indi_tile_shape').selectOption('10');
            await expect(page.locator('.toast-message').filter({ hasText: 'Rounded rectangle option is not supported for the Combined diagram type' })).toBeVisible();
        });

        test('Notification shows for rounded rectangles and combined diagram', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#indi_tile_shape').selectOption('10');
            await page.locator('#diagtype_combined').check();
            await expect(page.locator('.toast-message').filter({ hasText: 'Rounded rectangle option is not supported for the Combined diagram type' })).toBeVisible();

        });
    });
}
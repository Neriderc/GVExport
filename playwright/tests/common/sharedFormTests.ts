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
            await page.locator('li.menu-language > a.dropdown-toggle').click();
            await page.getByRole('menuitem', { name: 'British English' }).click();
            await setNonDefaults(page, false);
            await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1'); 
            await checkNonDefaults(page, false);
        });
    });    
}
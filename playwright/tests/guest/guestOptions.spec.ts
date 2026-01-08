import { runSharedOptionsTests } from '../common/sharedOptionTests.ts'
import { test, expect } from '../../fixtures.ts';
import { loadGVExport } from '../common/utils.ts';

/**
 * Run our suite of tests that are common across user and guest
 */
runSharedOptionsTests('user');

test('option: Add arrow label when pedigree type is not "birth"', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#show_pedigree_type').check();
        await expect(page.locator('#rendering svg')).toBeVisible();
        const svgHtml = await page.locator('#rendering svg').innerHTML();

        // Values not expected as private when logged out
        await expect(svgHtml).not.toContain('Radāʿ');
        await expect(svgHtml).not.toContain('Sealing');
        await expect(svgHtml).not.toContain('Foster');
        await expect(svgHtml).not.toContain('Adopted by both parents');
    });
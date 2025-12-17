import { test, expect } from '../../fixtures.ts';
import { loadGVExport, toggleAdvancedPanels, toggleSettingsSubgroups } from '../common/utils.ts'

export function runSharedDiagramTests(role: 'guest' | 'user') {
    test('diagram loads', async ({ page }) => {
        await loadGVExport(page);
        
        await page.locator('#diagtype_decorated').click();
        await expect(page.locator('#rendering svg')).toBeVisible();

        const svg = page.locator('#rendering svg');
        const before = await svg.innerHTML();
        await page.locator('#diagtype_combined').click();
        await expect(svg).not.toHaveJSProperty('innerHTML', before);
    });

    test('photos load correctly', async ({ page }) => {
        await loadGVExport(page);
        await toggleAdvancedPanels(page);
        await toggleSettingsSubgroups(page);

        // Check photos show when enabled
        let svgHtml = await page.locator('#rendering svg').innerHTML();
        await expect(svgHtml).toContain('media-thumbnail');

        // Check photos don't show when disabled
        await page.locator('#show_photos').uncheck();
        svgHtml = await page.locator('#rendering svg').innerHTML();
        expect(svgHtml).not.toContain('media-thumbnail');
    });
}
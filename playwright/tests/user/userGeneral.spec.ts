import { test, expect } from '../../fixtures.ts';
import { runSharedGeneralTests } from '../common/sharedGeneralTests.ts'
import { loadGVExport } from '../common/utils.ts'

/**
 * Auth expires and doesn't autorenew, so start by checking we are logged in
 * 
 */
test('I\'m logged in', async ({ page }) => {
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');        
        await expect(page.getByRole('link', { name: 'Sign out' })).toContainText('Sign out');
    });

runSharedGeneralTests('user');

test('option: Add all diagram items to clippings cart', async ({ page }) => {
    await loadGVExport(page, true);
    await page.getByRole('button', { name: 'Add all diagram items to clippings cart' }).click();
    await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
    await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
    await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
    await expect(page.locator('table a').nth(0)).toContainText('Joe BLOGGS + Jane Smith');
    await expect(page.locator('table a').nth(1)).toContainText('Matthew BLOGGS + Mary Black');
    await expect(page.locator('table a').nth(2)).toContainText('Steven BLOGGS + Jackie Marks');
    await expect(page.locator('table a').nth(3)).toContainText('Jimbo Marks + … …');
    await expect(page.locator('table a').nth(4)).toContainText('Jimbo Marks + Suzanne Franks');
    await expect(page.locator('table a').nth(5)).toContainText('Mary Black');
    await expect(page.locator('table a').nth(6)).toContainText('Alice BLOGGS');
    await expect(page.locator('table a').nth(7)).toContainText('Joe BLOGGS');
    await expect(page.locator('table a').nth(8)).toContainText('Liam BLOGGS');
    await expect(page.locator('table a').nth(9)).toContainText('Martin John Johnny BLOGGS');
    await expect(page.locator('table a').nth(10)).toContainText('Matthew BLOGGS');
    await expect(page.locator('table a').nth(11)).toContainText('Olivia BLOGGS');
    await expect(page.locator('table a').nth(12)).toContainText('Steven BLOGGS');
    await expect(page.locator('table a').nth(13)).toContainText('Susan BLOGGS');
    await expect(page.locator('table a').nth(14)).toContainText('Suzanne Franks');
    await expect(page.locator('table a').nth(15)).toContainText('Tony Jones');
    await expect(page.locator('table a').nth(16)).toContainText('Dylan King');
    await expect(page.locator('table a').nth(17)).toContainText('Jackie Marks');
    await expect(page.locator('table a').nth(18)).toContainText('Jimbo Marks');
    await expect(page.locator('table a').nth(19)).toContainText('Jane Smith');
    await expect(page.locator('table a').nth(20)).toContainText('Maria Young');
    await expect(page.locator('table a').nth(21)).toContainText('e859ba51699819ebcab8c94df61e3d2af20183a2.png');
    await expect(page.locator('table a').nth(22)).toContainText('e859ba51699819ebcab8c94df61e3d2af20183a2.png');
    await expect(page.locator('table a').nth(23)).toContainText('e859ba51699819ebcab8c94df61e3d2af20183a2.png');
    await expect(page.locator('table a').nth(24)).toContainText('f97b258c678ec73576b7cbadd8be16cf733150ff.png');
    await expect(page.locator('table a').nth(25)).toContainText('man 1.png');
    await expect(page.locator('table a').nth(26)).toContainText('man 4.png');
    await expect(page.locator('table a').nth(27)).toContainText('woman 1.png');
    await expect(page.locator('table a').nth(28)).toContainText('woman 4.png');
});
import { runSharedOptionsTests } from '../common/sharedOptionTests.ts'
import { Page, test, expect } from '../../fixtures.ts';
import { 
        loadGVExport, 
        clearSavedSettingsList, 
        getIndividualTile, 
        getTileByXref, 
        addFamilyToClippingsCartViaMenu, 
        checkCartIgnored, 
        checkCartUsed,
        checkCartFieldsEnabled,
        checkCartFieldsDisabled
    } from '../common/utils.ts';
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
    await expect(svgHtml).not.toContain('∞ Marriage');

    // Enable
    await page.locator('#show_divorces').check();
    await page.locator('#diagtype_combined').click();

    // Make sure SVG finishes loading after change
    const before = await page.locator('#rendering svg').innerHTML();
    await page.locator('#show_event_text_families').check();
    await expect(page.locator('#rendering svg')).not.toHaveJSProperty('innerHTML', before);

    // Check that it shows in diagram
    svgHtml = await page.locator('#rendering svg').innerHTML();
    await expect(svgHtml).toContain('∞ Marriage');
    await expect(svgHtml).toContain('⚮ Divorce');
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

    test('Toggle clippings cart status', async ({ page }) => {
        // Add to cart
        await loadGVExport(page, true);
        await page.locator('#click_action_indi').selectOption('100');
        let tile = await getIndividualTile(page, 'Olivia BLOGGS');
        await tile.click();
        await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
        await expect(page.locator('#rendering svg')).toBeVisible();
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
        await expect(page.locator('table a').nth(0)).toContainText('Olivia BLOGGS');

        // Remove from cart
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        tile = await getIndividualTile(page, 'Olivia BLOGGS');
        await tile.click();
        await expect(page.locator('.toast-message').filter({ hasText: 'Removed from clippings cart' })).toBeVisible();
        await expect(page.locator('#rendering svg')).toBeVisible();
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).nth(0).click();
        await expect(page.locator('#content')).toContainText('Your clippings cart is empty.')
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
    test('Toggle clippings cart status', async ({ page }) => {
        // Add to cart
        await loadGVExport(page, true);
        await page.locator('#click_action_fam').selectOption('60');
        let tile = await getTileByXref(page, 'X41');
        await tile.click();
        await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
        await expect(page.locator('#rendering svg')).toBeVisible();
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
        await expect(page.locator('table a').nth(0)).toContainText('Joe BLOGGS + Jane Smith');

        // Remove from cart
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        tile = await getTileByXref(page, 'X41');
        await tile.click();
        await expect(page.locator('.toast-message').filter({ hasText: 'Removed from clippings cart' })).toBeVisible();
        await expect(page.locator('#rendering svg')).toBeVisible();
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).nth(0).click();
        await expect(page.locator('table a').nth(0)).not.toContainText('Joe BLOGGS + Jane Smith');
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
        await page.locator('#click_action_indi').selectOption('50');
        const tile = await getIndividualTile(page, 'Olivia BLOGGS');
        await tile.click();
        await page.locator('.settings_ellipsis_menu_item', { hasText: 'Add to clippings cart' }).click()
        await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
        await expect(page.locator('#rendering svg')).toBeVisible();
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
        await expect(page.locator('table a').nth(0)).toContainText('Olivia BLOGGS');
    });

    test('Remove from clippings cart', async ({ page }) => {
        // Add someone to the clippings cart first
        await loadGVExport(page, true);
        await page.locator('#click_action_indi').selectOption('50');
        let tile = await getIndividualTile(page, 'Olivia BLOGGS');
        await tile.click();
        await page.locator('.settings_ellipsis_menu_item', { hasText: 'Add to clippings cart' }).click()
        await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
        await expect(page.locator('#rendering svg')).toBeVisible();
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
        await expect(page.locator('table a').nth(0)).toContainText('Olivia BLOGGS');

        // Now remove them and check it worked
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        tile = await getIndividualTile(page, 'Olivia BLOGGS');
        await tile.click();
        await page.locator('.settings_ellipsis_menu_item', { hasText: 'Remove from clippings cart' }).click();
        await expect(page.locator('.toast-message').filter({ hasText: 'Removed from clippings cart' })).toBeVisible();
        await expect(page.locator('#rendering svg')).toBeVisible();
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).nth(0).click();
        await expect(page.locator('#content')).toContainText('Your clippings cart is empty.')
    });
});

test.describe('user-only tests for family tile context menu', () => {
    test('Open family page', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_fam').selectOption('30');
        await testTileClickOpensPage(page, 'X41', 'Joe BLOGGS + Jane Smith', /family/, 'Open family page');
    });
    test('Add a child', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_fam').selectOption('30');
        await testTileClickOpensPage(page, 'X41', 'Joe BLOGGS + Jane Smith', /add-child-to-family/, 'Add a child');
    });
    test('Change family members', async ({ page }) => {
        await loadGVExport(page, true);
        await page.locator('#click_action_fam').selectOption('30');
        await testTileClickOpensPage(page, 'X41', 'Joe BLOGGS + Jane Smith', /change-family-members/, 'Change family members');
    });
    test('Add to clippings cart', async ({ page }) => {
        await addFamilyToClippingsCartViaMenu(page);
    });
    
    test('Remove from clippings cart', async ({ page }) => {
        await addFamilyToClippingsCartViaMenu(page);

        // Now remove them and check it worked
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        const tile = await getTileByXref(page, 'X41');
        await tile.click();
        await page.locator('.settings_ellipsis_menu_item', { hasText: 'Remove from clippings cart' }).click();
        await expect(page.locator('.toast-message').filter({ hasText: 'Removed from clippings cart' })).toBeVisible();
        await expect(page.locator('#rendering svg')).toBeVisible();
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).nth(0).click();
        await expect(page.locator('table a').nth(0)).not.toContainText('Joe BLOGGS + Jane Smith');
    });
});

test('option: Add arrow label when pedigree type is not "birth"', async ({ page }) => {
    await loadGVExport(page, true);
    await page.locator('#show_pedigree_type').check();
    await expect(page.locator('#rendering svg')).toBeVisible();
    const svgHtml = await page.locator('#rendering svg').innerHTML();
    await expect(svgHtml).toContain('Radāʿ');
    await expect(svgHtml).toContain('Sealing');
    await expect(svgHtml).toContain('Foster');
    await expect(svgHtml).toContain('Adopted by both parents');
});

test.describe('Test saving and loading clippings cart items to the saved settings list', () => {
    test('Test approving load of items', async ({ page }) => {

        // Load stuff to the clippings cart
        await addFamilyToClippingsCartViaMenu(page)
        
        // Save a setting
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        await page.locator('#usecart_yes').check();
        await expect(page.locator('#rendering svg')).toBeVisible();
        await clearSavedSettingsList(page);
        await expect(page.locator(".settings_list_item")).toHaveCount(0);
        await page.fill('#save_settings_name', "Clippings cart items");
        await page.locator("#save_settings_button").click();
        await expect(page.locator(".settings_list_item")).toHaveText("Clippings cart items…");

        // Clear clippings cart
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Empty the clippings cart' }).click();
        await expect(page.locator('#content')).toContainText('Your clippings cart is empty.')

        // Load the settings
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        await expect(page.locator(".settings_list_item")).toHaveCount(1);
        await page.locator(".settings_list_item").click();
        
        // Accept clippings cart items
        await page.locator('#modal-yes').click();

        // Check items added to clippings cart
        await expect(page.locator('#rendering svg')).toBeVisible();
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
        await expect(page.locator('table a').nth(0)).toContainText('Joe BLOGGS + Jane Smith');
    });
    test('Test cancelling load of items', async ({ page }) => {
        // Load stuff to the clippings cart
        await addFamilyToClippingsCartViaMenu(page)

        // Save a setting
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        await page.locator('#usecart_yes').check();
        await expect(page.locator('#rendering svg')).toBeVisible();

        await page.waitForTimeout(100);
        await clearSavedSettingsList(page);

        await expect(page.locator(".settings_list_item")).toHaveCount(0);
        await page.fill('#save_settings_name', "Clippings cart items");
        await page.locator("#save_settings_button").click();
        await expect(page.locator(".settings_list_item")).toHaveText("Clippings cart items…");
        await expect(page.locator(".settings_list_item")).toHaveCount(1);

        // Clear clippings cart
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Empty the clippings cart' }).click();
        await expect(page.locator('#content')).toContainText('Your clippings cart is empty.')

        // Load the settings
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        await expect(page.locator(".settings_list_item")).toHaveCount(1);
        await page.locator(".settings_list_item").click();
        
        // Accept clippings cart items
        await page.locator('#modal-cancel').click();
        await expect(page.locator('#modal-cancel')).toBeHidden();

        // Check items NOT added to clippings cart
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
        await expect(page.locator('#content')).toContainText('Your clippings cart is empty.')
    });

     test('Test ignoring clippings cart when saving', async ({ page }) => {

        // Load stuff to the clippings cart
        await addFamilyToClippingsCartViaMenu(page)
        
        // Save a setting
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        await page.locator('#usecart_no').check();
        await expect(page.locator('#rendering svg')).toBeVisible();
        await clearSavedSettingsList(page);
        const items = await page.locator('.settings_list_item');
        await expect(await items.count()).toBe(0);

        await page.fill('#save_settings_name', "Clippings cart items");
        await page.locator("#save_settings_button").click();
        await expect(page.locator(".settings_list_item")).toHaveText("Clippings cart items…");

        // Clear clippings cart
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Empty the clippings cart' }).click();
        await expect(page.locator('#content')).toContainText('Your clippings cart is empty.')

        // Load the settings
        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        await expect(page.locator(".settings_list_item")).toHaveCount(1);
        await page.locator(".settings_list_item").click();
        
        // Ensure no modal shows
        await expect(page.locator('#modal-yes')).toHaveCount(0, { timeout: 2000 });


        // Check items NOT added to clippings cart
        await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
        await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
        await expect(page.locator('#content')).toContainText('Your clippings cart is empty.')
    });
});


test.describe('Test use and ignore clippings cart settings', () => {
    test('Adding indi to clippings cart shows clippings cart section', async ({ page }) => {
        // Check no cart section by default
        await loadGVExport(page, true);
        await expect(await page.locator('#cart-section')).not.toBeVisible();

        // Add someone to cart
        await page.locator('#click_action_indi').selectOption('50');
        const tile = await getIndividualTile(page, 'Olivia BLOGGS');
        await tile.click();
        await page.locator('.settings_ellipsis_menu_item', { hasText: 'Add to clippings cart' }).click()
        await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
        await expect(page.locator('#rendering svg')).toBeVisible();

        // Check Cart section appeared and defaults correctly
        await checkCartIgnored(page);

        // Select to use the cart
        await page.locator('#usecart_yes').click();

        // Check options disabled fields correctly
        await checkCartUsed(page);

        // Select not to use the cart again
        await page.locator('#usecart_no').click();
        await checkCartIgnored(page);
    });

    test('Loading with cart contents then disabling it', async ({ page }) => {
        await loadGVExport(page, true);
        await expect(await page.locator('#cart-section')).not.toBeVisible();
        // Add someone to cart
        await page.locator('#click_action_indi').selectOption('50');
        const tile = await getIndividualTile(page, 'Olivia BLOGGS');
        await tile.click();
        await page.locator('.settings_ellipsis_menu_item', { hasText: 'Add to clippings cart' }).click()
        await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
        await expect(page.locator('#rendering svg')).toBeVisible();

        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        await page.locator('#usecart_no').click();
        await checkCartIgnored(page);
    });

    test('No cart on page load doesn\'t disable fields', async ({ page }) => {
        await loadGVExport(page, true);
        await expect(await page.locator('#cart-section')).not.toBeVisible();
        await checkCartFieldsEnabled(page);
    });

    test('Cart on page load disables fields', async ({ page }) => {
        await loadGVExport(page, true);
        await expect(await page.locator('#cart-section')).not.toBeVisible();
        // Add someone to cart
        await page.locator('#click_action_indi').selectOption('50');
        const tile = await getIndividualTile(page, 'Olivia BLOGGS');
        await tile.click();
        await page.locator('.settings_ellipsis_menu_item', { hasText: 'Add to clippings cart' }).click()
        await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
        await expect(page.locator('#rendering svg')).toBeVisible();

        await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1');
        await checkCartFieldsDisabled(page);
    });
});
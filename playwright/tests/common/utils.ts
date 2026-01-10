import { Page, test, expect } from '../../fixtures';

/**
 * Loads the main page, reset to defaults
 * 
 * @param page
 */
export async function loadGVExport(page: Page, expandOptions: boolean = false) {
    await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1&reset=1');
    await page.waitForURL('/module/_GVExport_/Chart/gvetest?xref=X1');

    // Sometimes our other tests leave the language in the wrong state. Reset if needed.
    const languageToggle = page.locator('li.menu-language > a.dropdown-toggle');

    if ((await languageToggle.innerText()).trim() !== 'Language') {
        await languageToggle.click();
        await page.getByRole('menuitem', { name: 'British English' }).click();
        await expect(page.locator('#rendering svg')).toBeVisible();
    }

    // If there are items in the cart from a previous run, we need to clean up
    const cart = await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' });
    if (await cart.count() > 0) {
        await cart.click();
        const clear = page.getByRole('menuitem', { name: 'Empty the clippings cart' });
        if (await clear.count() > 0) {
            await clear.click();
            await page.waitForURL('/module/clippings/Show/gvetest');
            await loadGVExport(page, true);
            return;
        }
    }
    
    if (expandOptions) {
        await toggleAdvancedPanels(page);
        await toggleSettingsSubgroups(page);
    }
};

export async function toggleAdvancedPanels(page: Page) {
    await page.locator('#people-advanced-button').click();
    await expect(await page.locator('#people-advanced')).toBeVisible();    
    await page.locator('#appearance-advanced-button').click();
    await expect(await page.locator('#appearance-advanced')).toBeVisible();    
    await page.locator('#files-advanced-button').click();
    await expect(await page.locator('#files-advanced')).toBeVisible();    
};

export async function toggleSettingsSubgroups(page: Page) {
    await page.locator('.subgroup').first().isVisible();

    const subgroups = page.locator('.subgroup');
    const sgCount = await subgroups.count();
    expect(sgCount).toBe(12);
    for (let i = 0; i < sgCount; i++) {
        await subgroups.nth(i).click();
    }
};





export async function getLogin(role = 'user', id: number) {
    if (role === 'admin') {
        return {
            username: process.env.USERNAME_ADMIN!,
            password: process.env.PASSWORD_ADMIN!
        };
    }

    type Login = {
        username: string;
        password: string;
    };

    const USER_ACCOUNTS: Login[] = JSON.parse(
        process.env.USER_ACCOUNTS_JSON!
    );

    return USER_ACCOUNTS[id % USER_ACCOUNTS.length];
};

/**
 * Deletes all saved settings
 * 
 * @param page 
 */
export async function clearSavedSettingsList(page: Page) {
    const items = await page.locator('.settings_list_item');

    while (await items.count() > 0) {
        const item = items.first();

        await item.getByText('…').click();
        const menu = item.locator('.settings_ellipsis_menu');
        await expect(menu).toBeVisible();
        await menu.getByText('Delete').click();
        await expect(item).toBeHidden();
        await page.waitForTimeout(3000);
    }
};


/**
 * Gets the tile locator for an individual based on a name
 */
export async function getIndividualTile(page, name) {
    return page.locator('svg a').filter({has: page.locator('text', { hasText: name })}).first();
};

/**
 * Gets the tile locator based on XREF
 */
export async function getTileByXref(page: Page, xref: string) {
    return page.locator(  `//*[local-name()="g" and @class="node" and ./*[local-name()="title" and normalize-space(.) = "${xref}"]]`);

};

export async function addFamilyToClippingsCartViaMenu(page: Page) {
    await loadGVExport(page, true);
    await page.locator('#click_action_fam').selectOption('60');
    const tile = await getTileByXref(page, 'X41');
    await tile.click();
    await expect(page.locator('.toast-message').filter({ hasText: 'Added to clippings cart' })).toBeVisible();
    await expect(page.locator('#rendering svg')).toBeVisible();
    await page.locator('.menu-clippings').getByRole('button', { name: 'Clippings cart' }).click();
    await page.getByRole('menuitem', { name: 'Clippings cart' }).click();
    await expect(page.locator('table a').nth(0)).toContainText('Joe BLOGGS + Jane Smith');
}
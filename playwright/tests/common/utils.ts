import { Page, test, expect } from '../../fixtures';

/**
 * Loads the main page, reset to defaults
 * 
 * @param page
 */
export async function loadGVExport(page: Page) {
    await page.goto('/module/_GVExport_/Chart/gvetest?xref=X1&reset=1');
    await page.waitForURL('/module/_GVExport_/Chart/gvetest?xref=X1');
};

export async function toggleAdvancedPanels(page: Page) {
    await page.locator('.advanced-settings-btn').first().isVisible();

    const advancedButtons = page.locator('.advanced-settings-btn');
    const count = await advancedButtons.count();
    expect(count).toBe(3);
    for (let i = 0; i < count; i++) {
        await advancedButtons.nth(i).click();   
    }
    await page.locator('.subgroup').first().isVisible();
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
    const items = page.locator('.settings_list_item');
    const count = await items.count();
    
    for (let i = 0; i < count; i++) {
        const item = items.nth(0);
        await item.getByText('…').click();
        await item
            .locator('.settings_ellipsis_menu')
            .getByText('❌')
            .click();
    }
};

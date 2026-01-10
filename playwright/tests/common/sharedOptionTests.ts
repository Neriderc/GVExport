/**
 * This file is for testing individual options on the options panel - they will typically set a setting 
 * and check that the diagram is displaying things correctly for that specific setting.
 * 
 * Only tests that need to run under both user and guest accounts go in this file.
 * 
 * Group multiple tests if they test the same function. You can tag with a PR if relevant.
 */

import { test, expect, Page, Locator } from '../../fixtures.ts';
import { checkDefaults } from './defaults.ts';
import { checkNonDefaults, setNonDefaults } from './nondefaults.ts';
import { loadGVExport, clearSavedSettingsList, toggleAdvancedPanels, toggleSettingsSubgroups, getIndividualTile, getTileByXref } from './utils.ts';

export function runSharedOptionsTests(role: 'guest' | 'user') {
    test('person select works', async ({ page }) => {
        await loadGVExport(page);
        // Check that single person from URL has been loaded
        await expect(page.locator('.indi_list_item')).toHaveCount(1);
        await page.locator('#pid-ts-control').click();
        const input = await page.locator('.dropdown-input').first();
        await input.fill('Steven');
        await page.locator('#pid-ts-dropdown .option', { hasText: 'Steven BLOGGS' }).click();
        await expect(page.locator('.indi_list_item')).toHaveCount(2);
        await expect(page.locator('.indi_list_item').nth(1)).toContainText('Steven BLOGGS');
    });



    test('loading settings from a JSON file correctly updates the settings', async ({ page }) => {
        const NON_DEFAULT_SETTINGS_JSON = '{"output_type":"png","graph_dir":"TB","diagram_type":"combined","show_photos":false,"show_birthdate":false,"birthdate_year_only":true,"show_birthplace":false,"show_death_date":false,"death_date_year_only":true,"show_death_place":false,"show_marriage_date":false,"marr_date_year_only":true,"show_marriage_place":false,"include_ancestors":false,"ancestor_levels":"1","include_siblings":false,"include_all_relatives":false,"include_descendants":false,"descendant_levels":"1","include_spouses":false,"include_all":false,"mark_not_related":true,"faster_relation_check":true,"add_links":false,"show_xref_individuals":true,"show_xref_families":true,"use_abbr_place":"10","use_abbr_name":"10","enable_debug_mode":false,"enable_graphviz":false,"dpi":"100","ranksep":"120%","nodesep":"120%","xref_list":"X2,X1","stop_xref_list":"","use_cart":false,"show_adv_people":true,"show_adv_appear":true,"show_adv_files":true,"typeface":"10","font_colour_name":"#cf0000","font_colour_details":"#4626d1","font_size":"15","font_size_name":"20","arrows_default":"#000000","arrows_related":"#000000","arrows_not_related":"#000000","arrow_colour_type":"360","male_col":"#eeaac1","female_col":"#99ccee","other_gender_col":"#eed999","unknown_gender_col":"#bbddbb","male_unrelated_col":"#ddfafa","female_unrelated_col":"#fee8e8","oth_gender_unrel_col":"#f8f0d5","unkn_gender_unrel_col":"#c3e3c3","family_col":"#fffccc","background_col":"#3333aa","indi_background_col":"#000000","highlight_custom_indis":true,"no_highlight_xref_list":"","highlight_custom_col":"#fcec93","border_col":"#303030","save_settings_name":"Settings","show_diagram_panel":true,"auto_update":false}'

        await loadGVExport(page);
        await setNonDefaults(page, false);

        await page.waitForFunction(() => {
            return typeof Form !== 'undefined';
        });

        await page.evaluate(json => {
            Form.settings.load(json);
        }, NON_DEFAULT_SETTINGS_JSON);


        
        await checkNonDefaults(page, false);

    });


    test('saving and loading works from advanced save option', async ({ page }) => {
        await loadGVExport(page);
        await setNonDefaults(page, false);
        // If we don't uncheck this, it won't save some of the settings (by design), so better uncheck it
        await page.uncheck('#only_save_diagram_settings');
        await page.getByRole('button', { name: 'Update' }).click();
        await clearSavedSettingsList(page);
        await page.fill('#save_settings_name', "Settings Name Test %");
        await page.locator("#save_settings_button").click();
        await expect(page.locator(".settings_list_item")).toHaveText("Settings Name Test %…");
        await loadGVExport(page);
        await checkDefaults(page, false);
        await expect(page.locator(".settings_list_item")).toHaveCount(1);
        await page.locator(".settings_list_item").click();
        // Loading a saved setting doesn't change the visibility of the selection panel, as selecting a setting from
        // the panel shouldn't cause the panel to disappear. As we reuse functions, it's easiest just to set the
        // expected state here before running our checks
        await page.locator("#show_diagram_panel").check();
        await page.check('#only_save_diagram_settings');

        await page.getByRole('button', { name: 'Update' }).click();
        await checkNonDefaults(page, false);
    });

    test.describe('option: Action when individual clicked', () => {
        test('Open individual\'s page', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#click_action_indi').selectOption('0');
            await testTileClickOpensPage(page, 'X1', 'Joe BLOGGS', /individual/);
        });

        test('Add individual to list of starting individuals', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#click_action_indi').selectOption('10');
            const tile = await getIndividualTile(page, 'Olivia BLOGGS');
            await tile.click();
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('.indi_list_item')).toHaveCount(2);
            await expect(page.locator('.indi_list_item').nth(0)).toContainText('Joe BLOGGS');
            await expect(page.locator('.indi_list_item').nth(1)).toContainText('Olivia BLOGGS');
        });

        test('Replace starting individuals with this individual', async ({ page }) => {
            await loadGVExport(page, true);
            await expect(page.locator('.indi_list_item')).toHaveCount(1);
            await expect(page.locator('.indi_list_item').nth(0)).toContainText('Joe BLOGGS');

            await page.locator('#click_action_indi').selectOption('20');
            const tile = await getIndividualTile(page, 'Olivia BLOGGS');
            await tile.click();
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('.indi_list_item')).toHaveCount(1);
            await expect(page.locator('.indi_list_item').nth(0)).toContainText('Olivia BLOGGS');
        });

        test('Add this individual to the list of stopping individuals', async ({ page }) => {
            await loadGVExport(page, true);

            await page.locator('#click_action_indi').selectOption('30');
            const tile = await getIndividualTile(page, 'Olivia BLOGGS');
            await tile.click();
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('#stop_xref_list')).toHaveValue('X54');
            await expect(page.locator('#stop_indi_list .indi_list_item')).toHaveCount(1);
            await expect(page.locator('#stop_indi_list .indi_list_item').nth(0)).toContainText('Olivia BLOGGS');
        });

        test('Replace stopping individuals with this individual', async ({ page }) => {
            await loadGVExport(page, true);

            // Add first individual
            await page.locator('#click_action_indi').selectOption('40');
            let tile = await getIndividualTile(page, 'Olivia BLOGGS');
            await tile.click();
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('#stop_xref_list')).toHaveValue('X54');
            await expect(page.locator('#stop_indi_list .indi_list_item')).toHaveCount(1);
            await expect(page.locator('#stop_indi_list .indi_list_item').nth(0)).toContainText('Olivia BLOGGS');

            // Add second individual and check they are replaced not added
            tile = await getIndividualTile(page, 'Liam BLOGGS');
            await tile.click();
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('#stop_xref_list')).toHaveValue('X53');
            await expect(page.locator('#stop_indi_list .indi_list_item')).toHaveCount(1);
            await expect(page.locator('#stop_indi_list .indi_list_item').nth(0)).toContainText('Liam BLOGGS');
        });

        test('Add to list of individuals to highlight', async ({ page }) => {
            await loadGVExport(page, true);
            
            await page.locator('#highlight_custom_indis').check();
            await page.locator('#click_action_indi').selectOption('70');
            const tile = await getIndividualTile(page, 'Olivia BLOGGS');
            await tile.click();
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('#highlight_list .indi_list_item')).toHaveCount(1);
            await expect(page.locator('#highlight_list .indi_list_item').nth(0)).toContainText('Olivia BLOGGS');
        });

        test('Show menu', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#click_action_indi').selectOption('50');
            await expect(page.locator('#context_list')).toBeEmpty();
            const tile = await getIndividualTile(page, 'Olivia BLOGGS');
            await tile.click();
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('#context_list')).not.toBeEmpty();
        });
    });


    test.describe('option: Action when family clicked', () => {
        test('Add to list of families to highlight', async ({ page }) => {
            await loadGVExport(page, false);
            await page.goto('/module/_GVExport_/Chart/gvetest?xref=X17&reset=1');
            await page.waitForURL('/module/_GVExport_/Chart/gvetest?xref=X17');    
            await toggleAdvancedPanels(page);
            await toggleSettingsSubgroups(page);
            
            await page.locator('#highlight_custom_fams').check();
            await expect(page.locator('#rendering svg')).toBeVisible();
            await page.locator('#graph_dir').selectOption('TB');
            await expect(page.locator('#rendering svg')).toBeVisible();
            await page.locator('#click_action_fam').selectOption('20');
            await expect(page.locator('#rendering svg')).toBeVisible();
            await page.locator('.hide-form').click();
            const tile = await getTileByXref(page, 'X20');
            await tile.click();
            await expect(page.locator('#rendering svg')).toBeVisible();
            await page.locator('.sidebar_toggle').click();
            await expect(page.locator('#gvexport')).toBeVisible();
            await expect(page.locator('#highlight_fams_list .indi_list_item')).toHaveCount(1);
            await expect(page.locator('#highlight_fams_list .indi_list_item').nth(0)).toContainText('Jimbo Marks + Suzanne Franks');
        });


        test('Show menu', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#click_action_fam').selectOption('30');
            await expect(page.locator('#context_list')).toBeEmpty();
            const tile = await getTileByXref(page, 'X41');
            await tile.click();
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('#context_list')).not.toBeEmpty();
        });
    });

    test.describe('individual context menu when clicked', () => {
        test('Open individual\'s page', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#click_action_indi').selectOption('50');
            await testTileClickOpensPage(page, 'X1', 'Joe BLOGGS', /individual/, 'Open individual\'s page');
        });

        test('Add individual to list of starting individuals', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#click_action_indi').selectOption('50');
            const tile = await getIndividualTile(page, 'Olivia BLOGGS');
            await tile.click();
            await page.locator('.settings_ellipsis_menu_item', { hasText: 'Add individual to list of starting individuals' }).click()
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('.indi_list_item')).toHaveCount(2);
            await expect(page.locator('.indi_list_item').nth(0)).toContainText('Joe BLOGGS');
            await expect(page.locator('.indi_list_item').nth(1)).toContainText('Olivia BLOGGS');
        });

        test('Replace starting individuals with this individual', async ({ page }) => {
            await loadGVExport(page, true);
            await expect(page.locator('.indi_list_item')).toHaveCount(1);
            await expect(page.locator('.indi_list_item').nth(0)).toContainText('Joe BLOGGS');
            await page.locator('#click_action_indi').selectOption('50');
            const tile = await getIndividualTile(page, 'Olivia BLOGGS');
            await tile.click();
            await page.locator('.settings_ellipsis_menu_item', { hasText: 'Replace starting individuals with this individual' }).click()
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('.indi_list_item')).toHaveCount(1);
            await expect(page.locator('.indi_list_item').nth(0)).toContainText('Olivia BLOGGS');
        });

        test('Add this individual to the list of stopping individuals', async ({ page }) => {
            await loadGVExport(page, true);

            await page.locator('#click_action_indi').selectOption('50');
            const tile = await getIndividualTile(page, 'Olivia BLOGGS');
            await tile.click();
            await page.locator('.settings_ellipsis_menu_item', { hasText: 'Add this individual to the list of stopping individuals' }).click()
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('#stop_xref_list')).toHaveValue('X54');
            await expect(page.locator('#stop_indi_list .indi_list_item')).toHaveCount(1);
            await expect(page.locator('#stop_indi_list .indi_list_item').nth(0)).toContainText('Olivia BLOGGS');
        });

        test('Replace stopping individuals with this individual', async ({ page }) => {
            await loadGVExport(page, true);

            // Add first individual
            await page.locator('#click_action_indi').selectOption('50');
            let tile = await getIndividualTile(page, 'Olivia BLOGGS');
            await tile.click();
            await page.locator('.settings_ellipsis_menu_item', { hasText: 'Replace stopping individuals with this individual' }).click()
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('#stop_xref_list')).toHaveValue('X54');
            await expect(page.locator('#stop_indi_list .indi_list_item')).toHaveCount(1);
            await expect(page.locator('#stop_indi_list .indi_list_item').nth(0)).toContainText('Olivia BLOGGS');

            // Add second individual and check they are replaced not added
            tile = await getIndividualTile(page, 'Liam BLOGGS');
            await tile.click();
            await page.locator('.settings_ellipsis_menu_item', { hasText: 'Replace stopping individuals with this individual' }).click()
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('#stop_xref_list')).toHaveValue('X53');
            await expect(page.locator('#stop_indi_list .indi_list_item')).toHaveCount(1);
            await expect(page.locator('#stop_indi_list .indi_list_item').nth(0)).toContainText('Liam BLOGGS');
        });

        test('Add to list of individuals to highlight', async ({ page }) => {
            await loadGVExport(page, true);
            
            await page.locator('#highlight_custom_indis').check();
            await page.locator('#click_action_indi').selectOption('50');
            const tile = await getIndividualTile(page, 'Olivia BLOGGS');
            await tile.click();
            await page.locator('.settings_ellipsis_menu_item', { hasText: 'Add to list of individuals to highlight' }).click()
            await expect(page.locator('#rendering svg')).toBeVisible();
            await expect(page.locator('#highlight_list .indi_list_item')).toHaveCount(1);
            await expect(page.locator('#highlight_list .indi_list_item').nth(0)).toContainText('Olivia BLOGGS');
        });
    });

    test.describe('Family context menu when clicked', () => {
        test('Add to list of families to highlight', async ({ page }) => {
            await loadGVExport(page, false);
            await page.goto('/module/_GVExport_/Chart/gvetest?xref=X17&reset=1');
            await page.waitForURL('/module/_GVExport_/Chart/gvetest?xref=X17');    
            await toggleAdvancedPanels(page);
            await toggleSettingsSubgroups(page);
            
            await page.locator('#click_action_fam').selectOption('30');
            await page.locator('#highlight_custom_fams').check();
            await page.locator('.hide-form').click();
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X20');
            await tile.click();
            await page.locator('.settings_ellipsis_menu_item', { hasText: 'Add to list of families to highlight' }).click()
            await expect(page.locator('#rendering svg')).toBeVisible();
            await page.locator('.sidebar_toggle').click();
            await expect(page.locator('#gvexport')).toBeVisible();
            await expect(page.locator('#highlight_fams_list .indi_list_item')).toHaveCount(1);
            await expect(page.locator('#highlight_fams_list .indi_list_item').nth(0)).toContainText('Jimbo Marks + Suzanne Franks');
        });
    });

    test.describe('option: Abbreviated names', () => {
        test('Full name', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_name').selectOption('0');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X58');
            await expect(tile).toContainText('Martin John "Johnny" BLOGGS');
        });
        test('Given and surname', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_name').selectOption('10');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X58');
            await expect(tile).toContainText('Martin John BLOGGS');
        });
        test('Given names', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_name').selectOption('20');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X58');
            await expect(tile).toContainText('Martin John "Johnny"');
        });
        test('First name only', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_name').selectOption('30');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X58');
            await expect(tile).toContainText('Martin');
        });
        test('Preferred given name and surname', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_name').selectOption('80');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X58');
            await expect(tile).toContainText('John BLOGGS');
        });
        test('Surnames', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_name').selectOption('40');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X58');
            await expect(tile).toContainText('BLOGGS');
        });
        test('Initials only', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_name').selectOption('50');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X58');
            await expect(tile).toContainText('MJB');
        });
        test('Given name initials and surname', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_name').selectOption('60');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X58');
            await expect(tile).toContainText('M.J. BLOGGS');
        });
        test('Don\'t show names', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_name').selectOption('70');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X58');
            await expect(tile).not.toContainText('Martin');
            await expect(tile).not.toContainText('BLOGGS');
        });
    });

    test.describe('option: Abbreviated place names', () => {
        test('Full place name', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_place').selectOption('0');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X1');
            await expect(tile).toContainText('(New Plymouth, Taranaki, NZ)');
        });

        test('City only', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_place').selectOption('5');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X1');
            await expect(tile).toContainText('(New Plymouth)');
        });

        test('City and country', async ({ page }) => {
            await loadGVExport(page, true);
            await page.locator('#use_abbr_place').selectOption('10');
            await expect(page.locator('#rendering svg')).toBeVisible();
            const tile = await getTileByXref(page, 'X1');
            await expect(tile).toContainText('(New Plymouth, New Zealand)');
        });

        test('City and 2 letter ISO country code', async ({ page }) => {
            await loadGVExport(page, true);
            let tile = await getTileByXref(page, 'X19');
            await expect(tile).toContainText('(Kerikeri, Te Tai Tokerau, New Zealand)');
            await page.locator('#use_abbr_place').selectOption('20');
            await expect(page.locator('#rendering svg')).toBeVisible();
            tile = await getTileByXref(page, 'X19');
            await expect(tile).toContainText('(Kerikeri, NZ)');
        });

        test('City and 2 letter ISO country code - convert from 3 letter', async ({ page }) => {
            await loadGVExport(page, true);
            let tile = await getTileByXref(page, 'X17');
            await expect(tile).toContainText('(Hamilton, Waikato, NZL)');
            await page.locator('#use_abbr_place').selectOption('20');
            await expect(page.locator('#rendering svg')).toBeVisible();
            tile = await getTileByXref(page, 'X17');
            await expect(tile).toContainText('(Hamilton, NZ)');
        });

        test('City and 3 letter ISO country code', async ({ page }) => {
            await loadGVExport(page, true);
            let tile = await getTileByXref(page, 'X19');
            await expect(tile).toContainText('(Kerikeri, Te Tai Tokerau, New Zealand)');
            await page.locator('#use_abbr_place').selectOption('30');
            await expect(page.locator('#rendering svg')).toBeVisible();
            tile = await getTileByXref(page, 'X19');
            await expect(tile).toContainText('(Kerikeri, NZL)');
        });


        test('City and 3 letter ISO country code - convert from 2 letter', async ({ page }) => {
            await loadGVExport(page, true);
            let tile = await getTileByXref(page, 'X1');
            await expect(tile).toContainText('(New Plymouth, Taranaki, NZ)');
            await page.locator('#use_abbr_place').selectOption('30');
            await expect(page.locator('#rendering svg')).toBeVisible();
            tile = await getTileByXref(page, 'X1');
            await expect(tile).toContainText('(New Plymouth, NZL)');
        });
     });

     
    test('option: Show sex of individuals', async ({ page }) => {
        await loadGVExport(page, true);
        let tile = await getTileByXref(page, 'X1');
        await expect(tile).not.toContainText('Male');
        await page.locator('#show_indi_sex').check();
        await expect(page.locator('#rendering svg')).toBeVisible();
        tile = await getTileByXref(page, 'X1');
        await expect(tile).toContainText('Male');
    });
   
    
    test('option: Show occupation of individuals', async ({ page }) => {
        await loadGVExport(page, true);
        let tile = await getTileByXref(page, 'X54');
        await expect(tile).not.toContainText('Circus performer');
        await page.locator('#show_indi_occupation').check();
        await expect(page.locator('#rendering svg')).toBeVisible();
        tile = await getTileByXref(page, 'X54');
        await expect(tile).toContainText('Circus performer');
    });
}

export async function testTileClickOpensPage(page: Page, xref: string, name: string, urlRegex: RegExp, menuOption: string = '') {
    let popup: Page;
    await expect(page.locator('#rendering svg')).toBeVisible();
    const link = await getTileByXref(page, xref);
    await expect(await link.count()).toBe(1);

    if (menuOption === '') {
        [popup] = await Promise.all([
            page.waitForEvent('popup'),
            link.click()
        ]);
    } else {
    // If we are going via context menu, need extra click
        await link.click();
        [popup] = await Promise.all([
            page.waitForEvent('popup'),
            page.locator('.settings_ellipsis_menu_item', { hasText: menuOption }).click()
        ]);
    }
    await expect(popup).toHaveURL(urlRegex);
    await expect(popup.locator('h2.wt-page-title')).toContainText(name);
};
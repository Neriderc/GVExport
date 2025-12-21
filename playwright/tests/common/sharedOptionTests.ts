/**
 * This file is for testing individual options on the options panel - they will typically set a setting 
 * and check that the diagram is displaying things correctly for that specific setting.
 * 
 * Only tests that need to run under both user and guest accounts go in this file.
 * 
 * Group multiple tests if they test the same function. You can tag with a PR if relevant.
 */

import { test, expect, Page } from '../../fixtures.ts';
import { checkDefaults } from './defaults.ts';
import { checkNonDefaults, setNonDefaults } from './nondefaults.ts';
import { loadGVExport, clearSavedSettingsList, toggleAdvancedPanels, toggleSettingsSubgroups, getIndividualTile } from './utils.ts';

export function runSharedOptionsTests(role: 'guest' | 'user') {
        test('person select works', async ({ page }) => {
            await loadGVExport(page);
            // Check that single person from URL has been loaded
            await expect(page.locator('.indi_list_item')).toHaveCount(1);
            await page.locator('#pid-ts-control').click();
            const input = page.locator('.dropdown-input').first();
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
            // If we don't uncheck this, it won't save some of the settings (by design), so better check it
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

        test('option: Show events as text if no date', async ({ page }) => {
            await loadGVExport(page);
            await toggleAdvancedPanels(page);
            await toggleSettingsSubgroups(page);

            // Check not enabled and not showing in diagram
            await expect(page.locator("#show_event_text_families")).not.toBeChecked();
            let svgHtml = await page.locator('#rendering svg').innerHTML();
            expect(svgHtml).not.toContain('∞ Marriage');

            // Enable
            await page.locator('#show_divorces').check();
            await page.locator('#diagtype_combined').click();

            // Make sure SVG finishes loading after change
            const before = await page.locator('#rendering svg').innerHTML();
            await page.locator('#show_event_text_families').check();
            await expect(page.locator('#rendering svg')).not.toHaveJSProperty('innerHTML', before);

            // Check that it shows in diagram
            svgHtml = await page.locator('#rendering svg').innerHTML();
            expect(svgHtml).toContain('∞ Marriage');
            expect(svgHtml).toContain('⚮ Divorce');
        });

        test.describe('option Action when individual clicked options', () => {
            test('Open individual\'s page', async ({ page }) => {
                await loadGVExport(page, true);
                await page.locator('#click_action_indi').selectOption('0');
                await testTileClickOpensPage(page, 'Joe BLOGGS', /individual/);
            });

            test('Add individual to list of starting individuals', async ({ page }) => {
                await loadGVExport(page, true);
                await page.locator('#click_action_indi').selectOption('10');
                const tile = await getIndividualTile(page, 'Olivia BLOGGS');
                tile.click();
                await page.waitForSelector('svg');
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
                tile.click();
                await page.waitForSelector('svg');
                await expect(page.locator('.indi_list_item')).toHaveCount(1);
                await expect(page.locator('.indi_list_item').nth(0)).toContainText('Olivia BLOGGS');
            });

            test('Add this individual to the list of stopping individuals', async ({ page }) => {
                await loadGVExport(page, true);

                await page.locator('#click_action_indi').selectOption('30');
                const tile = await getIndividualTile(page, 'Olivia BLOGGS');
                tile.click();
                await page.waitForSelector('svg');
                await expect(page.locator('#stop_xref_list')).toHaveValue('X54');
                await expect(page.locator('#stop_indi_list .indi_list_item')).toHaveCount(1);
                await expect(page.locator('#stop_indi_list .indi_list_item').nth(0)).toContainText('Olivia BLOGGS');
            });

            test('Add to list of individuals to highlight', async ({ page }) => {
                await loadGVExport(page, true);
                
                await page.locator('#highlight_custom_indis').check();
                await page.locator('#click_action_indi').selectOption('70');
                const tile = await getIndividualTile(page, 'Olivia BLOGGS');
                tile.click();
                await page.waitForSelector('svg');
                await expect(page.locator('#highlight_list .indi_list_item')).toHaveCount(1);
                await expect(page.locator('#highlight_list .indi_list_item').nth(0)).toContainText('Olivia BLOGGS');
            });

            test('Show menu', async ({ page }) => {
                await loadGVExport(page, true);
                await page.locator('#click_action_indi').selectOption('50');
                await expect(page.locator('#context_list')).toBeEmpty();
                const tile = await getIndividualTile(page, 'Olivia BLOGGS');
                tile.click();
                await page.waitForSelector('svg');
                await expect(page.locator('#context_list')).not.toBeEmpty();
            });
        });
    }

export async function testTileClickOpensPage(page: Page, name: string, urlRegex: RegExp) {
    await page.waitForSelector('svg');
    const link = await getIndividualTile(page, name);

    const [popup] = await Promise.all([
        page.waitForEvent('popup'),
        link.click()
    ]);

    await expect(popup).toHaveURL(urlRegex);
    await expect(popup.locator('h2.wt-page-title .NAME', { hasText: name })).toBeVisible();            
};
import { Page, expect } from '../../fixtures';
import { toggleAdvancedPanels, toggleSettingsSubgroups } from './utils';

export async function checkNonDefaults(page: Page, admin: Boolean) {

    if (admin) {
        // General GVExport settings — changed values
        await expect(page.locator('#filename')).toHaveValue('file_name');
        await expect(page.locator('#mclimit')).toHaveValue('10');
        await expect(page.locator('#birth_prefix')).toHaveValue('a');
        await expect(page.locator('#death_prefix')).toHaveValue('b');
        await expect(page.locator('#marriage_prefix')).toHaveValue('c');
        await expect(page.locator('#divorce_prefix')).toHaveValue('d');
        await expect(page.locator('#burial_prefix')).toHaveValue('e');

        await expect(page.locator('#limit_levels_visitor')).toHaveValue('50');
        await expect(page.locator('#limit_levels_member')).toHaveValue('51');
        await expect(page.locator('#limit_levels_editor')).toHaveValue('52');
        await expect(page.locator('#limit_levels_moderator')).toHaveValue('53');
        await expect(page.locator('#limit_levels_manager')).toHaveValue('54');

        await expect(page.locator('#usecart_yes')).not.toBeChecked();
        await expect(page.locator('#usecart_no')).toBeChecked();

    } else {
        // Advanced panels should be shown as set to non-default
        await expect(page.locator('#people-advanced-hidden')).toHaveAttribute('value', 'show');
        await expect(page.locator('#appearance-advanced-hidden')).toHaveAttribute('value', 'show');
        await expect(page.locator('#files-advanced-hidden')).toHaveAttribute('value', 'show');
    }

    await toggleSettingsSubgroups(page);
    // --- People to include ---
    await expect(page.locator('#include_ancestors')).not.toBeChecked();
    await expect(page.locator('#ancestor_levels')).toHaveValue('1');
    await expect(page.locator('#include_descendants')).not.toBeChecked();
    await expect(page.locator('#descendant_levels')).toHaveValue('1');
    await expect(page.locator('#include_siblings')).not.toBeChecked();
    await expect(page.locator('#include_all_relatives')).not.toBeChecked();
    await expect(page.locator('#include_spouses')).not.toBeChecked();
    // Not checked as checking this changes other options. TODO cover in a separate test
    // await expect(page.locator('#include_all')).toBeChecked();

    if (!admin) await expect(page.locator('#xref_list')).toHaveValue(/X2/); // While we set 'X2', 'X1' may be added to the list from URL

    await expect(page.locator('#mark_not_related')).toBeChecked();
    await expect(page.locator('#faster_relation_check')).toBeChecked();
    await expect(page.locator('#url_xref_treatment')).toHaveValue('add');

    // --- Appearance ---
    await expect(page.locator('#graph_dir')).toHaveValue('TB');
    await expect(page.locator('#diagtype_decorated')).not.toBeChecked();
    await expect(page.locator('#diagtype_combined')).toBeChecked();

    // Shapes
    await expect(page.locator('#indi_tile_shape')).toHaveValue('20');
    await expect(page.locator('#shape_sex_male')).toHaveValue('10');
    await expect(page.locator('#shape_sex_female')).toHaveValue('0');
    await expect(page.locator('#shape_sex_other')).toHaveValue('10');
    await expect(page.locator('#shape_sex_unknown')).toHaveValue('10');
    if (admin) await expect(page.locator('#shape_vital_dead')).toHaveValue('10');
    if (admin) await expect(page.locator('#shape_vital_living')).toHaveValue('0');

    // Photos
    await expect(page.locator('#show_photos')).not.toBeChecked();
    await expect(page.locator('#show_birth_first_image')).toBeChecked();
    await expect(page.locator('#show_marriage_first_image')).toBeChecked();
    await expect(page.locator('#show_divorce_first_image')).toBeChecked();
    await expect(page.locator('#show_death_first_image')).toBeChecked();
    await expect(page.locator('#show_burial_first_image')).toBeChecked();
    await expect(page.locator('#photo_shape')).toHaveValue('10');
    await expect(page.locator('#photo_size')).toHaveValue('90%');
    await expect(page.locator('#photo_resolution')).toHaveValue('90%');

    // Colours

    // Background colours
    await expect(page.locator('#bg_col_type')).toHaveValue('230');
    if (admin) await expect(page.locator('#indi_background_col')).toHaveValue('#eeeeee');
    if (admin) await expect(page.locator('#indi_background_dead_col')).toHaveValue('#bbbbbb');
    if (admin) await expect(page.locator('#indi_background_living_col')).toHaveValue('#eeeeee');
    await expect(page.locator('#indi_background_age_low_col')).toHaveValue('#dd4444');
    await expect(page.locator('#indi_background_age_high_col')).toHaveValue('#44dd44');
    await expect(page.locator('#indi_background_age_unknown_col')).toHaveValue('#dddddd');
    await expect(page.locator('#indi_background_age_low')).toHaveValue('5');
    await expect(page.locator('#indi_background_age_high')).toHaveValue('95');

    // Stripe colours
    await expect(page.locator('#stripe_col_type')).toHaveValue('130');
    if (admin) await expect(page.locator('#indi_stripe_dead_col')).toHaveValue('#bbbbbb');
    if (admin) await expect(page.locator('#indi_stripe_living_col')).toHaveValue('#789678');
    await expect(page.locator('#indi_stripe_age_low_col')).toHaveValue('#dd4444');
    await expect(page.locator('#indi_stripe_age_high_col')).toHaveValue('#44dd44');
    await expect(page.locator('#indi_stripe_age_unknown_col')).toHaveValue('#dddddd');
    await expect(page.locator('#indi_stripe_age_low')).toHaveValue('5');
    await expect(page.locator('#indi_stripe_age_high')).toHaveValue('95');

    // Border colours
    await expect(page.locator('#border_col_type')).toHaveValue('340');
    if (admin) await expect(page.locator('#indi_border_col')).toHaveValue('#505050');
    if (admin) await expect(page.locator('#indi_border_dead_col')).toHaveValue('#bbbbbb');
    if (admin) await expect(page.locator('#indi_border_living_col')).toHaveValue('#eeeeee');
    await expect(page.locator('#indi_border_age_low_col')).toHaveValue('#dd4444');
    await expect(page.locator('#indi_border_age_high_col')).toHaveValue('#44dd44');
    await expect(page.locator('#indi_border_age_unknown_col')).toHaveValue('#dddddd');
    await expect(page.locator('#indi_border_age_low')).toHaveValue('5');
    await expect(page.locator('#indi_border_age_high')).toHaveValue('95');

    // Shared note colour
    await expect(page.locator('#sharednote_col_enable')).toBeChecked();
    await expect(page.locator('#sharednote_col_default')).toHaveValue('#aa2222');

    // Gender colours
    await expect(page.locator('#male_col')).toHaveValue('#eeaac1');
    await expect(page.locator('#female_col')).toHaveValue('#99ccee');
    await expect(page.locator('#other_gender_col')).toHaveValue('#eed999');
    await expect(page.locator('#unknown_gender_col')).toHaveValue('#bbddbb');
    await expect(page.locator('#male_unrelated_col')).toHaveValue('#ddfafa');
    await expect(page.locator('#female_unrelated_col')).toHaveValue('#fee8e8');
    await expect(page.locator('#oth_gender_unrel_col')).toHaveValue('#f8f0d5');
    await expect(page.locator('#unkn_gender_unrel_col')).toHaveValue('#c3e3c3');

    await expect(page.locator('#highlight_custom_indis')).toBeChecked();
    await expect(page.locator('#highlight_custom_col')).toHaveValue('#fcec93');
    await expect(page.locator('#highlight_custom_fams')).toBeChecked();
    await expect(page.locator('#highlight_custom_fams_col')).toHaveValue('#fcec93');
    await expect(page.locator('#family_col')).toHaveValue('#fffccc');
    await expect(page.locator('#border_col')).toHaveValue('#303030');

    // Font
    await expect(page.locator('#typeface')).toHaveValue('10');
    await expect(page.locator('#font_size_name')).toHaveValue('20');
    await expect(page.locator('#font_size')).toHaveValue('15');
    await expect(page.locator('#font_colour_name')).toHaveValue('#cf0000');
    await expect(page.locator('#font_colour_details')).toHaveValue('#4626d1');

    // Page links
    await expect(page.locator('#add_links')).not.toBeChecked();

    // Abbreviations
    await expect(page.locator('#use_abbr_name')).toHaveValue('10');
    await expect(page.locator('#use_abbr_place')).toHaveValue('10');
    await expect(page.locator('#use_abbr_month')).toBeChecked();

    // Information on individuals
    await expect(page.locator('#show_xref_individuals')).toBeChecked();
    await expect(page.locator('#show_birthdate')).not.toBeChecked();
    await expect(page.locator('#bd_type_y')).toBeChecked();
    await expect(page.locator('#bd_type_gedcom')).not.toBeChecked();
    await expect(page.locator('#show_birthplace')).not.toBeChecked();
    await expect(page.locator('#show_death_date')).not.toBeChecked();
    await expect(page.locator('#dd_type_y')).toBeChecked();
    await expect(page.locator('#dd_type_gedcom')).not.toBeChecked();
    await expect(page.locator('#show_death_place')).not.toBeChecked();
    await expect(page.locator('#show_burial_date')).toBeChecked();
    await expect(page.locator('#show_burial_place')).toBeChecked();
    await expect(page.locator('#show_indi_sex')).toBeChecked();
    await expect(page.locator('#use_alt_events')).not.toBeChecked();

    // Information on families
    await expect(page.locator('#show_xref_families')).toBeChecked();
    await expect(page.locator('#show_marriages')).not.toBeChecked();
    await expect(page.locator('#show_marriage_date')).not.toBeChecked();
    await expect(page.locator('#md_type_y')).toBeChecked();
    await expect(page.locator('#md_type_gedcom')).not.toBeChecked();
    await expect(page.locator('#show_marriage_place')).not.toBeChecked();
    await expect(page.locator('#show_marriage_type')).toBeChecked();
    await expect(page.locator('#show_divorces')).toBeChecked();

    // DPI
    await expect(page.locator('#dpi')).toHaveValue('100');

    // Spacing
    await expect(page.locator('#ranksep')).toHaveValue('120%');
    await expect(page.locator('#nodesep')).toHaveValue('120%');

    // Layout
    await expect(page.locator('#cl_type_ss')).not.toBeChecked();
    await expect(page.locator('#cl_type_ou')).toBeChecked();

    // Diagram style
    await expect(page.locator('#arrow_style')).toHaveValue('10');
    await expect(page.locator('#arrow_colour_type')).toHaveValue('360');
    if (admin) await expect(page.locator('#arrows_default')).toHaveValue('#444444');
    if (admin) await expect(page.locator('#colour_arrow_related')).toBeChecked();
    if (admin) await expect(page.locator('#arrows_related')).toHaveValue('#3333aa');
    if (admin) await expect(page.locator('#arrows_not_related')).toHaveValue('#33aa33');
    await expect(page.locator('#show_pedigree_type')).toBeChecked();
    await expect(page.locator('#background_col')).toHaveValue('#3333aa');

    // General
    await expect(page.locator('#output_type')).toHaveValue('png');
    await expect(page.locator('#auto_update')).not.toBeChecked();
    await expect(page.locator('#click_action_indi')).toHaveValue('10');
    await expect(page.locator('#click_action_fam')).toHaveValue('10');
    await expect(page.locator('#settings_sort_order')).toHaveValue('10');
    await expect(page.locator('#show_diagram_panel')).toBeChecked();
    await expect(page.locator('#only_save_diagram_settings')).toBeChecked();

    // Debug
    if (admin) {
        await expect(page.locator('#show_debug_panel')).toBeChecked();
        await expect(page.locator('#enable_debug_mode')).toBeChecked();
        await expect(page.locator('#enable_graphviz')).not.toBeChecked();
    } else {
        await expect(page.locator('#arrow_group')).toHaveAttribute('style', 'display:none;');
    }
}

// Reusable function to set admin panel values to non-defaults
export async function setNonDefaults(page: Page, admin: Boolean) {

    if (admin) {
        // Set general GVExport settings in control panel
        await page.fill('#filename', 'file_name');
        await page.fill('#mclimit', '10');
        await page.fill('#birth_prefix', 'a');
        await page.fill('#death_prefix', 'b');
        await page.fill('#marriage_prefix', 'c');
        await page.fill('#divorce_prefix', 'd');
        await page.fill('#burial_prefix', 'e');

        await page.fill('#limit_levels_visitor', '50');
        await page.fill('#limit_levels_member', '51');
        await page.fill('#limit_levels_editor', '52');
        await page.fill('#limit_levels_moderator', '53');
        await page.fill('#limit_levels_manager', '54');

        // Only do these on the admin panel as it requires setup otherwise
        await page.check('#usecart_no');
    } else {
        await toggleAdvancedPanels(page);
    }
    
    await toggleSettingsSubgroups(page);

    // Near the top so we can update them all without generating the diagram 100 times
    await page.uncheck('#auto_update');

    // Set "People to include" settings
    await page.uncheck('#include_ancestors');
    await page.fill('#ancestor_levels', '1');
    await page.uncheck('#include_descendants');
    await page.fill('#descendant_levels', '1');
    await page.uncheck('#include_siblings');
    await page.uncheck('#include_all_relatives');
    await page.uncheck('#include_spouses');
    // Not tested here as checking it will cause above to be checked again - TODO include in another test
    // await page.check('#include_all');

    if (!admin) await page.fill('#xref_list', 'X2');

    await page.check('#mark_not_related');
    await page.check('#faster_relation_check');
    await page.selectOption('#url_xref_treatment', 'add');


    // Check "Appearance" section settings
    await page.selectOption('#graph_dir', 'TB');
    await page.check('#diagtype_combined');

    // Shapes
    await page.selectOption('#indi_tile_shape', '20');
    await page.selectOption('#shape_sex_male', '10');
    await page.selectOption('#shape_sex_female', '0');
    await page.selectOption('#shape_sex_other', '10');
    await page.selectOption('#shape_sex_unknown', '10');
    if (admin) await page.selectOption('#shape_vital_dead', '10');
    if (admin) await page.selectOption('#shape_vital_living', '0');
    //Photos
    await page.uncheck('#show_photos');
    await page.check('#show_birth_first_image');
    await page.check('#show_marriage_first_image');
    await page.check('#show_divorce_first_image');
    await page.check('#show_death_first_image');
    await page.check('#show_burial_first_image');
    await page.selectOption('#photo_shape', '10');
    await page.fill('#photo_size', '90%');
    await page.fill('#photo_resolution', '90%');
    // Colours
    await page.selectOption('#bg_col_type', '230');
    if (admin) await page.fill('#indi_background_col', '#eeeeee');
    if (admin) await page.fill('#indi_background_dead_col', '#bbbbbb');
    if (admin) await page.fill('#indi_background_living_col', '#eeeeee');
    await page.fill('#indi_background_age_low_col', '#dd4444');
    await page.fill('#indi_background_age_high_col', '#44dd44');
    await page.fill('#indi_background_age_unknown_col', '#dddddd');
    await page.fill('#indi_background_age_low', '5');
    await page.fill('#indi_background_age_high', '95');

    await page.selectOption('#stripe_col_type', '130');
    if (admin) await page.fill('#indi_stripe_dead_col', '#bbbbbb');
    if (admin) await page.fill('#indi_stripe_living_col', '#789678');
    await page.fill('#indi_stripe_age_low_col', '#dd4444');
    await page.fill('#indi_stripe_age_high_col', '#44dd44');
    await page.fill('#indi_stripe_age_unknown_col', '#dddddd');
    await page.fill('#indi_stripe_age_low', '5');
    await page.fill('#indi_stripe_age_high', '95');

    await page.selectOption('#border_col_type', '340');
    if (admin) await page.fill('#indi_border_col', '#505050');
    if (admin) await page.fill('#indi_border_dead_col', '#bbbbbb');
    if (admin) await page.fill('#indi_border_living_col', '#eeeeee');
    await page.fill('#indi_border_age_low_col', '#dd4444');
    await page.fill('#indi_border_age_high_col', '#44dd44');
    await page.fill('#indi_border_age_unknown_col', '#dddddd');
    await page.fill('#indi_border_age_low', '5');
    await page.fill('#indi_border_age_high', '95');

    await page.check('#sharednote_col_enable');
    await page.fill('#sharednote_col_default', '#aa2222');

    await page.fill('#male_col', '#eeaac1');
    await page.fill('#female_col', '#99ccee');
    await page.fill('#other_gender_col', '#eed999');
    await page.fill('#unknown_gender_col', '#bbddbb');
    await page.fill('#male_unrelated_col', '#ddfafa');
    await page.fill('#female_unrelated_col', '#fee8e8');
    await page.fill('#oth_gender_unrel_col', '#f8f0d5');
    await page.fill('#unkn_gender_unrel_col', '#c3e3c3');
    await page.check('#highlight_custom_indis');
    await page.fill('#highlight_custom_col', '#fcec93');
    await page.check('#highlight_custom_fams');
    await page.fill('#highlight_custom_fams_col', '#fcec93');
    await page.fill('#family_col', '#fffccc');
    await page.fill('#border_col', '#303030');
    
    // Font
    await page.selectOption('#typeface', '10');
    await page.fill('#font_size_name', '20');
    await page.fill('#font_size', '15');
    await page.fill('#font_colour_name', '#cf0000');
    await page.fill('#font_colour_details', '#4626d1');
    // Page links
    await page.uncheck('#add_links');
    // Abbreviations
    await page.selectOption('#use_abbr_name', '10');
    await page.selectOption('#use_abbr_place', '10');
    await page.check('#use_abbr_month');
    // Information on individuals
    await page.check('#show_xref_individuals');
    await page.check('#bd_type_y');
    await page.uncheck('#show_birthdate');
    await page.uncheck('#show_birthplace');
    await page.check('#dd_type_y');
    await page.uncheck('#show_death_date');
    await page.uncheck('#show_death_place');
    await page.check('#show_burial_date');
    await page.check('#show_burial_place');
    await page.check('#show_indi_sex');
    await page.uncheck('#use_alt_events');  
    // Information on families
    await page.check('#show_xref_families');
    await page.check('#md_type_y');
    await page.uncheck('#show_marriage_date');
    await page.uncheck('#md_type_gedcom');
    await page.uncheck('#show_marriage_place');
    await page.check('#show_marriage_type');
    await page.uncheck('#show_marriages');
    await page.check('#show_divorces');
    
    // Diagram DPI
    await page.fill('#dpi', '100');
    // Spacing
    await page.fill('#ranksep', '120%');
    await page.fill('#nodesep', '120%');
    // Layout
    await page.check('#cl_type_ou');
    // Diagram style
    await page.selectOption('#arrow_style', '10');
    await page.selectOption('#arrow_colour_type', '360');
    if (admin) await page.fill('#arrows_default', '#444444');
    if (admin) await page.check('#colour_arrow_related');
    if (admin) await page.fill('#arrows_related', '#3333aa');
    if (admin) await page.fill('#arrows_not_related', '#33aa33');
    await page.check('#show_pedigree_type');
    await page.fill('#background_col', '#3333aa');


    // General settings
    await page.selectOption('#output_type', 'png');
    await page.selectOption('#click_action_indi', '10');
    await page.selectOption('#click_action_fam', '10');
    await page.selectOption('#settings_sort_order', '10');
    await page.check('#show_diagram_panel');
    await page.check('#only_save_diagram_settings');

    if (admin) {
        // Debug settings
        await page.check('#show_debug_panel');
        await page.check('#enable_debug_mode');
        await page.uncheck('#enable_graphviz');

        // Save and update
        await page.getByRole('button', { name: 'save' }).click();
        await expect(page.getByRole('alert')).toContainText('The preferences for the module ‘GVExport’ have been updated.');
    } else {
        await page.getByRole('button', { name: 'Update' }).click();
        await expect(page.locator('.toast-message').filter({ hasText: 'Generated' })).toBeVisible();
    }
}


/*
  TODO Can't be tested in main settings test. Needs separate test to ensure the setting is being correctly saved and loaded
  needs set and check

await expect(page.locator('#shape_vital_dead')).toHaveValue('10');
await expect(page.locator('#shape_vital_living')).toHaveValue('10');


     if (admin) await page.fill('#indi_background_col', '#eeeeee');
     if (admin) await page.fill('#indi_background_dead_col', '#bbbbbb');
     if (admin) await page.fill('#indi_background_living_col', '#eeeeee');

    if (admin) await page.fill('#indi_stripe_dead_col', '#bbbbbb');
    if (admin) await page.fill('#indi_stripe_living_col', '#789678');

        if (admin) await page.fill('#indi_border_col', '#505050');
    if (admin) await page.fill('#indi_border_dead_col', '#bbbbbb');
    if (admin) await page.fill('#indi_border_living_col', '#eeeeee');

    if (admin) await page.fill('#arrows_default', '#444444');
        if (admin) await page.check('#colour_arrow_related');
if (admin) await page.fill('#arrows_related', '#3333aa');
    if (admin) await page.fill('#arrows_not_related', '#33aa33');
*/
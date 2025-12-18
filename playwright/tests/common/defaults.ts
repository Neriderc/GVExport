import { Page, expect } from '../../fixtures';;
import { toggleAdvancedPanels, toggleSettingsSubgroups } from './utils';

// Reusable function to check form defaults
export async function checkDefaults(page: Page, admin: Boolean) {
    if (admin) {
        // Check general GVExport settings in control panel
        await expect(page.locator('#filename')).toHaveValue('gvexport');
        await expect(page.locator('#mclimit')).toHaveValue('1');
        await expect(page.locator('#birth_prefix')).toHaveValue('*');
        await expect(page.locator('#death_prefix')).toHaveValue('†');
        await expect(page.locator('#divorce_prefix')).toHaveValue('⚮');
        await expect(page.locator('#burial_prefix')).toHaveValue('_');
        await expect(page.locator('#limit_levels_visitor')).toHaveValue('5');
        await expect(page.locator('#limit_levels_member')).toHaveValue('10');
        await expect(page.locator('#limit_levels_editor')).toHaveValue('15');
        await expect(page.locator('#limit_levels_moderator')).toHaveValue('20');
        await expect(page.locator('#limit_levels_manager')).toHaveValue('25');
        // Only test these on the admin panel as it requires setup otherwise
        await expect(page.locator('#usecart_yes')).toBeChecked();
        await expect(page.locator('#usecart_no')).not.toBeChecked();

    } else {
        // Check advanced panels are hidden by default
        await expect(page.locator('#people-advanced-hidden')).not.toHaveAttribute('value', 'show');
        await expect(page.locator('#appearance-advanced-hidden')).not.toHaveAttribute('value', 'show');
        await expect(page.locator('#files-advanced-hidden')).not.toHaveAttribute('value', 'show');
        // Then show the advanced panels, so we can test the fields in them
        await toggleAdvancedPanels(page)
    }

    await toggleSettingsSubgroups(page);

    // Check "People to include" settings
    await expect(page.locator('#include_ancestors')).toBeChecked();
    await expect(page.locator('#ancestor_levels')).toHaveValue('2');
    await expect(page.locator('#include_descendants')).toBeChecked();
    await expect(page.locator('#descendant_levels')).toHaveValue('2');
    await expect(page.locator('#include_siblings')).toBeChecked();
    await expect(page.locator('#include_all_relatives')).toBeChecked();
    await expect(page.locator('#include_spouses')).toBeChecked();
    await expect(page.locator('#include_all')).not.toBeChecked();

    if (!admin) expect(page.locator('#xref_list')).toHaveValue('X1');

    await expect(page.locator('#mark_not_related')).not.toBeChecked();
    await expect(page.locator('#faster_relation_check')).not.toBeChecked();
    await expect(page.locator('#url_xref_treatment')).toHaveValue('default');


    // Check "Appearance" section settings
    await expect(page.locator('#graph_dir')).toHaveValue('LR');
    await expect(page.locator('#diagtype_decorated')).toBeChecked();
    await expect(page.locator('#diagtype_combined')).not.toBeChecked();
    // Shapes
    await expect(page.locator('#indi_tile_shape')).toHaveValue('0');
    await expect(page.locator('#indi_tile_shape')).toHaveValue('0');
    await expect(page.locator('#shape_sex_male')).toHaveValue('0');
    await expect(page.locator('#shape_sex_female')).toHaveValue('10');
    await expect(page.locator('#shape_sex_other')).toHaveValue('0');
    await expect(page.locator('#shape_sex_unknown')).toHaveValue('0');
    await expect(page.locator('#shape_vital_dead')).toHaveValue('0');
    await expect(page.locator('#shape_vital_living')).toHaveValue('10');
    //Photos
    await expect(page.locator('#show_photos')).toBeChecked();
    await expect(page.locator('#show_birth_first_image')).not.toBeChecked();
    await expect(page.locator('#show_marriage_first_image')).not.toBeChecked();
    await expect(page.locator('#show_divorce_first_image')).not.toBeChecked();
    await expect(page.locator('#show_death_first_image')).not.toBeChecked();
    await expect(page.locator('#show_burial_first_image')).not.toBeChecked();
    await expect(page.locator('#photo_shape')).toHaveValue('0');
    await expect(page.locator('#photo_size')).toHaveValue('100%');
    await expect(page.locator('#photo_resolution')).toHaveValue('100%');
    // Colours
    await expect(page.locator('#bg_col_type')).toHaveValue('200');
    await expect(page.locator('#indi_background_col')).toHaveValue('#fefefe');
    await expect(page.locator('#indi_background_dead_col')).toHaveValue('#cccccc');
    await expect(page.locator('#indi_background_living_col')).toHaveValue('#fefefe');
    await expect(page.locator('#indi_background_age_low_col')).toHaveValue('#cc5555');
    await expect(page.locator('#indi_background_age_high_col')).toHaveValue('#55cc55');
    await expect(page.locator('#indi_background_age_unknown_col')).toHaveValue('#ffffff');
    await expect(page.locator('#indi_background_age_low')).toHaveValue('0');
    await expect(page.locator('#indi_background_age_high')).toHaveValue('100');

    await expect(page.locator('#stripe_col_type')).toHaveValue('110');
    await expect(page.locator('#indi_stripe_dead_col')).toHaveValue('#cccccc');
    await expect(page.locator('#indi_stripe_living_col')).toHaveValue('#6c8567');
    await expect(page.locator('#indi_stripe_age_low_col')).toHaveValue('#cc5555');
    await expect(page.locator('#indi_stripe_age_high_col')).toHaveValue('#55cc55');
    await expect(page.locator('#indi_stripe_age_unknown_col')).toHaveValue('#ffffff');
    await expect(page.locator('#indi_stripe_age_low')).toHaveValue('0');
    await expect(page.locator('#indi_stripe_age_high')).toHaveValue('100');
    
    await expect(page.locator('#border_col_type')).toHaveValue('320');
    await expect(page.locator('#indi_border_col')).toHaveValue('#606060');
    await expect(page.locator('#indi_border_dead_col')).toHaveValue('#cccccc');
    await expect(page.locator('#indi_border_living_col')).toHaveValue('#fefefe');
    await expect(page.locator('#indi_border_age_low_col')).toHaveValue('#cc5555');
    await expect(page.locator('#indi_border_age_high_col')).toHaveValue('#55cc55');
    await expect(page.locator('#indi_border_age_unknown_col')).toHaveValue('#ffffff');
    await expect(page.locator('#indi_border_age_low')).toHaveValue('0');
    await expect(page.locator('#indi_border_age_high')).toHaveValue('100');

    await expect(page.locator('#sharednote_col_enable')).not.toBeChecked();
    await expect(page.locator('#sharednote_col_default')).toHaveValue('#ed333b');

    await expect(page.locator('#male_col')).toHaveValue('#add8e6');
    await expect(page.locator('#female_col')).toHaveValue('#ffb6c1');
    await expect(page.locator('#other_gender_col')).toHaveValue('#fceaa1');
    await expect(page.locator('#unknown_gender_col')).toHaveValue('#cceecc');
    await expect(page.locator('#male_unrelated_col')).toHaveValue('#eef8f8');
    await expect(page.locator('#female_unrelated_col')).toHaveValue('#fdf2f2');
    await expect(page.locator('#oth_gender_unrel_col')).toHaveValue('#fcf7e3');
    await expect(page.locator('#unkn_gender_unrel_col')).toHaveValue('#d6eed6');
    await expect(page.locator('#highlight_custom_indis')).not.toBeChecked();
    await expect(page.locator('#highlight_custom_col')).toHaveValue('#fffdc3');
    await expect(page.locator('#unkn_gender_unrel_col')).toHaveValue('#d6eed6');
    await expect(page.locator('#highlight_custom_fams')).not.toBeChecked();
    await expect(page.locator('#highlight_custom_fams_col')).toHaveValue('#fffdc3');
    await expect(page.locator('#family_col')).toHaveValue('#ffffee');
    await expect(page.locator('#border_col')).toHaveValue('#606060');
    // Font
    await expect(page.locator('#typeface')).toHaveValue('0');
    await expect(page.locator('#font_size_name')).toHaveValue('12');
    await expect(page.locator('#font_size')).toHaveValue('10');
    await expect(page.locator('#font_colour_name')).toHaveValue('#333333');
    await expect(page.locator('#font_colour_details')).toHaveValue('#555555');
    // Page links
    await expect(page.locator('#add_links')).toBeChecked();
    // Abbreviations
    await expect(page.locator('#use_abbr_name')).toHaveValue('0');
    await expect(page.locator('#use_abbr_place')).toHaveValue('0');
    await expect(page.locator('#use_abbr_month')).not.toBeChecked();
    // Information on individuals
    await expect(page.locator('#show_xref_individuals')).not.toBeChecked();
    await expect(page.locator('#show_birthdate')).toBeChecked();
    await expect(page.locator('#bd_type_y')).not.toBeChecked();
    await expect(page.locator('#bd_type_gedcom')).toBeChecked();
    await expect(page.locator('#show_birthplace')).toBeChecked();
    await expect(page.locator('#show_death_date')).toBeChecked();
    await expect(page.locator('#dd_type_y')).not.toBeChecked();
    await expect(page.locator('#dd_type_gedcom')).toBeChecked();
    await expect(page.locator('#show_death_place')).toBeChecked();
    await expect(page.locator('#show_burial_date')).not.toBeChecked();
    await expect(page.locator('#show_burial_place')).not.toBeChecked();
    await expect(page.locator('#show_indi_sex')).not.toBeChecked();
    await expect(page.locator('#use_alt_events')).toBeChecked();
    // Information on families
    await expect(page.locator('#show_xref_families')).not.toBeChecked();
    await expect(page.locator('#show_marriages')).toBeChecked();
    await expect(page.locator('#show_marriage_date')).toBeChecked();
    await expect(page.locator('#md_type_y')).not.toBeChecked();
    await expect(page.locator('#md_type_gedcom')).toBeChecked();
    await expect(page.locator('#show_marriage_place')).toBeChecked();
    await expect(page.locator('#show_marriage_type')).not.toBeChecked();
    await expect(page.locator('#show_divorces')).not.toBeChecked();
    await expect(page.locator('#show_event_text_families')).not.toBeChecked();
    // Diagram DPI
    await expect(page.locator('#dpi')).toHaveValue('72');
    // Spacing
    await expect(page.locator('#ranksep')).toHaveValue('100%');
    await expect(page.locator('#nodesep')).toHaveValue('100%');
    // Layout
    await expect(page.locator('#cl_type_ss')).toBeChecked();
    await expect(page.locator('#cl_type_ou')).not.toBeChecked();
    // Diagram style
    await expect(page.locator('#arrow_style')).toHaveValue('0');
    await expect(page.locator('#arrow_colour_type')).toHaveValue('350');
    await expect(page.locator('#arrows_default')).toHaveValue('#555555');
    await expect(page.locator('#colour_arrow_related')).not.toBeChecked();
    await expect(page.locator('#arrows_related')).toHaveValue('#222266');
    await expect(page.locator('#arrows_not_related')).toHaveValue('#226622');
    await expect(page.locator('#show_pedigree_type')).not.toBeChecked();
    await expect(page.locator('#background_col')).toHaveValue('#eeeeee');


    // General settings
    await expect(page.locator('#output_type')).toHaveValue('svg');
    await expect(page.locator('#auto_update')).toBeChecked();
    await expect(page.locator('#click_action_indi')).toHaveValue('0');
    await expect(page.locator('#click_action_fam')).toHaveValue('0');
    await expect(page.locator('#settings_sort_order')).toHaveValue('0');
    await expect(page.locator('#show_diagram_panel')).not.toBeChecked();
    await expect(page.locator('#only_save_diagram_settings')).not.toBeChecked();

    // Debug
    if (admin) {
        await expect(page.locator('#show_debug_panel')).not.toBeChecked();
        await expect(page.locator('#enable_debug_mode')).not.toBeChecked();
        await expect(page.locator('#enable_graphviz')).toBeChecked();
    } else {
        await expect(page.locator('#arrow_group')).toHaveAttribute('style', 'display:none;');
    }
}
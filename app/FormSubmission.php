<?php

namespace vendor\WebtreesModules\gvexport;

use Fisharebest\Webtrees\I18N;

/**
 * Form handling functionality
 */
class FormSubmission
{
    /**
     * Takes list of values from form submission and returns structures and
     * validated settings list
     *
     * @param $vars
     * @param $module
     * @return array
     */
    public function load($vars, $module): array
    {
        $settings = [];
        // INDI id
        if (!empty($vars["xref_list"]) && $this->isXrefListValid($vars["xref_list"])) {
            $settings['xref_list'] = $vars["xref_list"];
        } else {
            $settings['xref_list'] = "";
        }
        // Stop PIDs
        if (!empty($vars["stop_xref_list"]) && $this->isXrefListValid($vars["stop_xref_list"])) {
            $settings['stop_xref_list'] = $vars["stop_xref_list"];
            $settings['stop_proc'] = true;
        } else {
            $settings['stop_xref_list'] = "";
            $settings['stop_proc'] = false;
        }
        $settings['include_ancestors'] = isset($vars['include_ancestors']);
        $settings['include_descendants'] = isset($vars['include_descendants']);

        // If "Anyone" option is picked, then other relations options also must be set
        $settings['include_all'] = isset($vars['include_all']);
        $settings['include_siblings'] = isset($vars['include_siblings']) || $settings['include_all'];
        $settings['include_spouses'] = isset($vars['include_spouses']) || $settings['include_all'];
        $settings['include_all_relatives'] = isset($vars['include_all_relatives']) || $settings['include_all'];

        if (isset($vars['ancestor_levels'])) {
            $settings['ancestor_levels'] = I18N::digits($vars["ancestor_levels"]);
        } else {
            $settings['ancestor_levels'] = 0;
        }
        if (isset($vars['descendant_levels'])) {
            $settings['descendant_levels'] = I18N::digits($vars["descendant_levels"]);
        } else {
            $settings['descendant_levels'] = 0;
        }

        if (isset($vars["mclimit"])) {
            $settings['mclimit'] = I18N::digits($vars["mclimit"]);
        }

        if (isset($vars["filename"]) && $this->isNameStringValid($vars["filename"])) {
            $settings['filename'] = $vars["filename"];
        }

        if (isset($vars["birth_prefix"]) && $this->isPrefixStringValid($vars["birth_prefix"])) {
            $settings['birth_prefix'] = $vars["birth_prefix"];
        }

        if (isset($vars["death_prefix"]) && $this->isPrefixStringValid($vars["death_prefix"])) {
            $settings['death_prefix'] = $vars["death_prefix"];
        }

        $settings['mark_not_related'] = isset($vars['mark_not_related']);
        $settings['faster_relation_check'] = isset($vars['faster_relation_check']);

        if (isset($vars['url_xref_treatment']) && ctype_alpha($vars['url_xref_treatment'])) {
            $settings['url_xref_treatment'] = $vars['url_xref_treatment'];
        }

        if (isset($vars['font_colour_name']) && $this->isValidColourHex($vars['font_colour_name'])) {
            $settings["font_colour_name"] = $vars['font_colour_name'];
        }

        if (isset($vars['font_colour_details']) && $this->isValidColourHex($vars['font_colour_details'])) {
            $settings["font_colour_details"] = $vars['font_colour_details'];
        }

        if (isset($vars['font_size'])) {
            $settings['font_size'] = I18N::digits($vars['font_size']);
        }

        if (isset($vars['font_size_name'])) {
            $settings['font_size_name'] = I18N::digits($vars['font_size_name']);
        }

        if (isset($vars['typeface'])) {
            $settings['typeface'] = I18N::digits($vars['typeface']);
        }

        if (isset($vars['arrows_default']) && $this->isValidColourHex($vars['arrows_default'])) {
            $settings['arrows_default'] = $vars['arrows_default'];
        }

        if (isset($vars['arrows_related']) && $this->isValidColourHex($vars['arrows_related'])) {
            $settings['arrows_related'] = $vars['arrows_related'];
        }

        if (isset($vars['arrows_not_related']) && $this->isValidColourHex($vars['arrows_not_related'])) {
            $settings['arrows_not_related'] = $vars['arrows_not_related'];
        }

        $settings['colour_arrow_related'] = isset($vars['colour_arrow_related']);

        if (isset($vars['graph_dir']) && ctype_alpha($vars['graph_dir'])) {
            $settings['graph_dir'] = $vars['graph_dir'];
        }

        $settings['show_birthdate'] = isset($vars['show_birthdate']);

        if (isset($vars['birthdate_year_only'])) {
            $settings['birthdate_year_only'] = ($vars['birthdate_year_only'] == 'true');
        }

        $settings['show_birthplace'] = isset($vars['show_birthplace']);
        $settings['show_death_date'] = isset($vars['show_death_date']);

        if (isset($vars['death_date_year_only'])) {
            $settings['death_date_year_only'] = ($vars['death_date_year_only'] == 'true');
        }

        $settings['show_death_place'] = isset($vars['show_death_place']);
        $settings['show_marriage_date'] = isset($vars['show_marriage_date']);

        if (isset($vars['marr_date_year_only'])) {
            $settings['marr_date_year_only'] = ($vars['marr_date_year_only'] == 'true');
        }

        $settings['show_marriage_place'] = isset($vars['show_marriage_place']);
        $settings['show_indi_sex'] = isset($vars['show_indi_sex']);
        $settings['show_xref_individuals'] = isset($vars['show_xref_individuals']);
        $settings['show_xref_families'] = isset($vars['show_xref_families']);
        $settings['add_links'] = isset($vars['add_links']);

        if (isset($vars['use_abbr_place'])) {
            $settings['use_abbr_place'] = I18N::digits($vars['use_abbr_place']);
        }

        if (isset($vars['use_abbr_name'])) {
            $settings['use_abbr_name'] = I18N::digits($vars['use_abbr_name']);
        }

        if (isset($vars['use_cart'])) {
            $settings['use_cart'] = ($vars['use_cart'] !== "ignorecart");
        }

        if (isset($vars['show_adv_people'])) {
            $settings['show_adv_people'] = ($vars['show_adv_people'] == "show");
        }
        if (isset($vars['show_adv_appear'])) {
            $settings['show_adv_appear'] = ($vars['show_adv_appear'] == "show");
        }
        if (isset($vars['show_adv_files'])) {
            $settings['show_adv_files'] = ($vars['show_adv_files'] == "show");
        }
        $settings['auto_update'] = isset($vars['auto_update']);
        $settings['enable_debug_mode'] = isset($vars['enable_debug_mode']);
        $settings['show_debug_panel'] = isset($vars['show_debug_panel']);
        if (isset($vars['admin_page'])) {
            $settings['enable_graphviz'] = isset($vars['enable_graphviz']);
        } else {
            $admin_settings = (new Settings())->getAdminSettings($module);
            $settings['enable_graphviz'] = isset($vars['enable_graphviz']) || !$admin_settings['show_debug_panel'];
        }
        // Set custom colours
        if (isset($vars["male_col"]) && $this->isValidColourHex($vars["male_col"])) {
            $settings['male_col'] = $vars["male_col"];
        }
        if (isset($vars["female_col"]) && $this->isValidColourHex($vars["female_col"])) {
            $settings['female_col'] = $vars["female_col"];
        }
        if (isset($vars["other_gender_col"]) && $this->isValidColourHex($vars["other_gender_col"])) {
            $settings['other_gender_col'] = $vars["other_gender_col"];
        }
        if (isset($vars["unknown_gender_col"]) && $this->isValidColourHex($vars["unknown_gender_col"])) {
            $settings['unknown_gender_col'] = $vars["unknown_gender_col"];
        }
        if (isset($vars["male_unrelated_col"]) && $this->isValidColourHex($vars["male_unrelated_col"])) {
            $settings['male_unrelated_col'] = $vars["male_unrelated_col"];
        }
        if (isset($vars["female_unrelated_col"]) && $this->isValidColourHex($vars["female_unrelated_col"])) {
            $settings['female_unrelated_col'] = $vars["female_unrelated_col"];
        }
        if (isset($vars["oth_gender_unrel_col"]) && $this->isValidColourHex($vars["oth_gender_unrel_col"])) {
            $settings['oth_gender_unrel_col'] = $vars["oth_gender_unrel_col"];
        }
        if (isset($vars["unkn_gender_unrel_col"]) && $this->isValidColourHex($vars["unkn_gender_unrel_col"])) {
            $settings['unkn_gender_unrel_col'] = $vars["unkn_gender_unrel_col"];
        }
        if (isset($vars["family_col"]) && $this->isValidColourHex($vars["family_col"])) {
            $settings['family_col'] = $vars["family_col"];
        }
        if (isset($vars['background_col']) && $this->isValidColourHex($vars['background_col'])) {
            $settings['background_col'] = $vars["background_col"];
        }
        if (isset($vars['indi_background_dead_col']) && $this->isValidColourHex($vars['indi_background_dead_col'])) {
            $settings['indi_background_dead_col'] = $vars["indi_background_dead_col"];
        }
        if (isset($vars['indi_background_living_col']) && $this->isValidColourHex($vars['indi_background_living_col'])) {
            $settings['indi_background_living_col'] = $vars["indi_background_living_col"];
        }
        if (isset($vars['indi_background_age_low_col']) && $this->isValidColourHex($vars['indi_background_age_low_col'])) {
            $settings['indi_background_age_low_col'] = $vars["indi_background_age_low_col"];
        }
        if (isset($vars['indi_background_age_high_col']) && $this->isValidColourHex($vars['indi_background_age_high_col'])) {
            $settings['indi_background_age_high_col'] = $vars["indi_background_age_high_col"];
        }
        if (isset($vars['indi_background_age_unknown_col']) && $this->isValidColourHex($vars['indi_background_age_unknown_col'])) {
            $settings['indi_background_age_unknown_col'] = $vars["indi_background_age_unknown_col"];
        }
        if (isset($vars['indi_background_age_low'])) {
            $settings['indi_background_age_low'] = I18N::digits($vars["indi_background_age_low"]);
        }
        if (isset($vars['indi_background_age_high'])) {
            $settings['indi_background_age_high'] = I18N::digits($vars["indi_background_age_high"]);
        }
        if (isset($vars['bg_col_type'])) {
            $settings['bg_col_type'] = I18N::digits($vars["bg_col_type"]);
        }
        if (isset($vars['stripe_col_type'])) {
            $settings['stripe_col_type'] = I18N::digits($vars["stripe_col_type"]);
        }
        if (isset($vars['indi_stripe_dead_col']) && $this->isValidColourHex($vars['indi_stripe_dead_col'])) {
            $settings['indi_stripe_dead_col'] = $vars["indi_stripe_dead_col"];
        }
        if (isset($vars['indi_stripe_living_col']) && $this->isValidColourHex($vars['indi_stripe_living_col'])) {
            $settings['indi_stripe_living_col'] = $vars["indi_stripe_living_col"];
        }
        if (isset($vars['indi_stripe_age_low_col']) && $this->isValidColourHex($vars['indi_stripe_age_low_col'])) {
            $settings['indi_stripe_age_low_col'] = $vars["indi_stripe_age_low_col"];
        }
        if (isset($vars['indi_stripe_age_high_col']) && $this->isValidColourHex($vars['indi_stripe_age_high_col'])) {
            $settings['indi_stripe_age_high_col'] = $vars["indi_stripe_age_high_col"];
        }
        if (isset($vars['indi_stripe_age_unknown_col']) && $this->isValidColourHex($vars['indi_stripe_age_unknown_col'])) {
            $settings['indi_stripe_age_unknown_col'] = $vars["indi_stripe_age_unknown_col"];
        }
        if (isset($vars['indi_stripe_age_low'])) {
            $settings['indi_stripe_age_low'] = I18N::digits($vars["indi_stripe_age_low"]);
        }
        if (isset($vars['indi_stripe_age_high'])) {
            $settings['indi_stripe_age_high'] = I18N::digits($vars["indi_stripe_age_high"]);
        }
        if (isset($vars['border_col_type'])) {
            $settings['border_col_type'] = I18N::digits($vars["border_col_type"]);
        }
        if (isset($vars["indi_background_col"]) && $this->isValidColourHex($vars["indi_background_col"])) {
            $settings['indi_background_col'] = $vars["indi_background_col"];
        }
        $settings['highlight_start_indis'] = isset($vars["highlight_start_indis"]);
        if (isset($vars["highlight_col"]) && $this->isValidColourHex($vars["highlight_col"])) {
            $settings['highlight_col'] = $vars["highlight_col"];
        }
        if (isset($vars["no_highlight_xref_list"]) && $this->isXrefListValid($vars["no_highlight_xref_list"])) {
            $settings['no_highlight_xref_list'] = $vars["no_highlight_xref_list"];
        }
        if (isset($vars["border_col"]) && $this->isValidColourHex($vars["border_col"])) {
            $settings['border_col'] = $vars["border_col"];
        }
        if (isset($vars["indi_border_col"]) && $this->isValidColourHex($vars["indi_border_col"])) {
            $settings['indi_border_col'] = $vars["indi_border_col"];
        }
        if (isset($vars["indi_border_dead_col"]) && $this->isValidColourHex($vars["indi_border_dead_col"])) {
            $settings['indi_border_dead_col'] = $vars["indi_border_dead_col"];
        }
        if (isset($vars["indi_border_living_col"]) && $this->isValidColourHex($vars["indi_border_living_col"])) {
            $settings['indi_border_living_col'] = $vars["indi_border_living_col"];
        }
        if (isset($vars["indi_border_age_low_col"]) && $this->isValidColourHex($vars["indi_border_age_low_col"])) {
            $settings['indi_border_age_low_col'] = $vars["indi_border_age_low_col"];
        }
        if (isset($vars["indi_border_age_high_col"]) && $this->isValidColourHex($vars["indi_border_age_high_col"])) {
            $settings['indi_border_age_high_col'] = $vars["indi_border_age_high_col"];
        }
        if (isset($vars["indi_border_age_unknown_col"]) && $this->isValidColourHex($vars["indi_border_age_unknown_col"])) {
            $settings['indi_border_age_unknown_col'] = $vars["indi_border_age_unknown_col"];
        }
        if (isset($vars['indi_border_age_low'])) {
            $settings['indi_border_age_low'] = I18N::digits($vars["indi_border_age_low"]);
        }
        if (isset($vars['indi_border_age_high'])) {
            $settings['indi_border_age_high'] = I18N::digits($vars["indi_border_age_high"]);
        }
        // Settings
        if (!empty($vars['diagram_type']) && ctype_alpha($vars['diagram_type'])) {
            $settings['diagram_type'] = $vars['diagram_type'];

        }
        $settings['show_photos'] = isset($vars['show_photos']);
        $settings['convert_photos_jpeg'] = isset($vars['convert_photos_jpeg']);
        if (isset($vars['photo_shape'])) {
            $settings['photo_shape'] = I18N::digits($vars['photo_shape']);
        }
        if (isset($vars['photo_quality'])) {
            $settings['photo_quality'] = I18N::digits($vars['photo_quality']);
        }
        if (isset($vars['indi_tile_shape'])) {
            $settings['indi_tile_shape'] = I18N::digits($vars['indi_tile_shape']);
        }
        if (isset($vars['shape_sex_male'])) {
            $settings['shape_sex_male'] = I18N::digits($vars['shape_sex_male']);
        }
        if (isset($vars['shape_sex_female'])) {
            $settings['shape_sex_female'] = I18N::digits($vars['shape_sex_female']);
        }
        if (isset($vars['shape_sex_other'])) {
            $settings['shape_sex_other'] = I18N::digits($vars['shape_sex_other']);
        }
        if (isset($vars['shape_sex_unknown'])) {
            $settings['shape_sex_unknown'] = I18N::digits($vars['shape_sex_unknown']);
        }
        if (isset($vars['shape_vital_dead'])) {
            $settings['shape_vital_dead'] = I18N::digits($vars['shape_vital_dead']);
        }
        if (isset($vars['shape_vital_living'])) {
            $settings['shape_vital_living'] = I18N::digits($vars['shape_vital_living']);
        }
        if (isset($vars['photo_size'])) {
            $settings['photo_size'] = $this->cleanPercent($vars['photo_size']);
        }
        if (isset($vars['photo_resolution'])) {
            $settings['photo_resolution'] = $this->cleanPercent($vars['photo_resolution']);
        }

        $settings['no_fams'] = isset($vars['no_fams']);

        if (isset($vars['dpi'])) {
            $settings['dpi'] = I18N::digits($vars['dpi']);
        }
        if (isset($vars['ranksep'])) {
            $settings['ranksep'] = $this->cleanPercent($vars['ranksep']);
        }
        if (isset($vars['nodesep'])) {
            $settings['nodesep'] = $this->cleanPercent($vars['nodesep']);
        }
        if (isset($vars['output_type']) && ctype_alpha($vars['output_type'])) {
            $settings['output_type'] = $vars['output_type'];
        }
        if (!empty($vars['save_settings_name'])) {
            $settings['save_settings_name'] = $this->cleanSettingsName($vars['save_settings_name']);
        } else {
            $settings['save_settings_name'] = I18N::translate('Settings');
        }
        $settings['show_diagram_panel'] = isset($vars['show_diagram_panel']);
        return $settings;
    }

    /**
     * Check if an XREF list string is properly structured
     *
     * @param string $list
     * @return false
     */
    private function isXrefListValid(string $list): bool
    {
        return preg_match('/^[A-Za-z0-9:,_.-]*$/',$list);
    }

    /**
     * Check if a provided colour hex is valid
     *
     * @param string $colour
     * @return false
     */
    private function isValidColourHex($colour): bool
    {
        return preg_match('/^#[0-9a-f]{6}$/',$colour);
    }

    /**
     * Check if a chosen name string meets validation criteria
     *
     * @param $name
     * @return false
     */
    public static function isNameStringValid($name): bool
    {
        return preg_match('/^[A-Za-z0-9 _.-]*$/',$name);
    }

    /**
     * Check if a string makes a valid prefix for birth/death/etc prefix icon
     *
     * @param $name
     * @return false
     */
    private function isPrefixStringValid($name): bool
    {
        return preg_match('/^[A-Za-z0-9_ .*+()^%$#@!†-]*$/',$name);
    }

    /**
     * Check if a string is a correctly formatted percentage
     *
     * @param $string
     * @return false
     */
    private function isPercent($string): bool
    {
        return preg_match('/^[0-9]*%$/',$string);
    }

    /**
     * Removes all characters not in the regex
     *
     * @param string $name
     * @return string
     */
    private function cleanSettingsName(string $name): string
    {
        return preg_replace("/[^A-ZÀ-úa-z0-9_ .*+()&^%$#@!'-]+/", '', $name);
    }

    /**
     * Attempts to normalise percentage string
     *
     * @param $percent
     * @return mixed|string
     */
    private function cleanPercent($percent)
    {
        if (!strpos($percent, '%')) {
            $percent = $percent . '%';
        }
        if ($this->isPercent($percent) && $percent != '%') {
            return $percent;
        }
        // Failed to normalise
        return '100%';
    }
}
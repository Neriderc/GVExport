<?php

namespace vendor\WebtreesModules\gvexport;

use Exception;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Http\Exceptions\HttpBadRequestException;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Tree;

/**
 * Represents the diagram settings, regardless of context (user, admin, default)
 */
class Settings
{
    public const ID_MAIN_SETTINGS = "_MAIN_";
    public const ID_ALL_SETTINGS = "_ALL_";
    public const GUEST_USER_ID = 0;
    private const ADMIN_PREFERENCE_NAME = "_admin_settings";
    private const CONTEXT_USER = 0;
    private const CONTEXT_ADMIN = 1;
    private const CONTEXT_NAMED_SETTING = 10;
    public const CONTEXT_COOKIE = 20;
    public const CONTEXT_MAIN_SETTINGS = 30;
    public const PREFERENCE_PREFIX = "GVE";
    public const SETTINGS_LIST_PREFERENCE_NAME = "_id_list";
    public const SAVED_SETTINGS_LIST_PREFERENCE_NAME = "_shared_settings_list";
    public const OPTION_STRIPE_NONE = 100;
    public const OPTION_STRIPE_SEX_COLOUR = 110;
    public const OPTION_STRIPE_VITAL_COLOUR = 120;
    public const OPTION_STRIPE_AGE_COLOUR = 130;
    public const OPTION_BACKGROUND_CUSTOM_COLOUR = 200;
    public const OPTION_BACKGROUND_SEX_COLOUR = 210;
    public const OPTION_BACKGROUND_VITAL_COLOUR = 220;
    public const OPTION_BACKGROUND_AGE_COLOUR = 230;
    public const OPTION_BORDER_CUSTOM_COLOUR = 300;
    public const OPTION_BORDER_SEX_COLOUR = 310;
    public const OPTION_BORDER_FAMILY = 320;
    public const OPTION_BORDER_VITAL_COLOUR = 330;
    public const OPTION_BORDER_AGE_COLOUR = 340;
    const TREE_PREFIX = "_t";
    const USER_PREFIX = "_u";
    private array $settings_json_cache = [];
    private array $defaultSettings;

    /**
     * Settings instance always starts with default settings
     */
    public function __construct(){
        // Load settings from config file
        $this->defaultSettings = include dirname(__FILE__) . "/../config.php";
        // Add options lists
        $this->defaultSettings['typefaces'] = [0 => "Arial", 10 => "Brush Script MT", 20 => "Courier New", 30 => "Garamond", 40 => "Georgia", 50 => "Tahoma", 60 => "Times New Roman", 70 => "Trebuchet MS", 80 => "Verdana"];
        $this->defaultSettings['directions']['TB'] = "Top-to-bottom";
        $this->defaultSettings['directions']['LR'] = "Left-to-right";
        $this->defaultSettings['url_xref_treatment_options']['default'] = "Default";
        $this->defaultSettings['url_xref_treatment_options']['add'] = "Add to list";
        $this->defaultSettings['url_xref_treatment_options']['nothing'] = "Don't add to list";
        $this->defaultSettings['url_xref_treatment_options']['overwrite'] = "Overwrite";
        $this->defaultSettings['use_abbr_places'] = [0 => "Full place name", 10 => "City and country" ,  20 => "City and 2 letter ISO country code", 30 => "City and 3 letter ISO country code"];
        $this->defaultSettings['use_abbr_names'] = [0 => "Full name", 10 => "Given and surnames", 20 => "Given names" , 30 => "First given name only", 80 => "Preferred given name and surname", 40 => "Surnames", 50 => "Initials only", 60 => "Given name initials and surname", 70 => "Don't show names"];
        $this->defaultSettings['photo_shape_options'] = [Person::SHAPE_NONE => "No change", Person::SHAPE_OVAL => "Oval", Person::SHAPE_CIRCLE => "Circle" , Person::SHAPE_SQUARE => "Square", Person::SHAPE_ROUNDED_RECT => "Rounded rectangle", Person::SHAPE_ROUNDED_SQUARE => "Rounded square"];
        $this->defaultSettings['photo_quality_options'] = [0 => "Lowest", 20 => "Low", 50 => "Medium" , 75 => "High", 100 => "Highest"];
        $this->defaultSettings['indi_tile_shape_custom_options'] = [0 => "Rectangle", 10 => "Rounded rectangle"];
        $this->defaultSettings['indi_tile_shape_options'] = $this->defaultSettings['indi_tile_shape_custom_options'] + [Person::TILE_SHAPE_SEX => 'Based on individual&apos;s sex', Person::TILE_SHAPE_VITAL => 'Based on vital status'];
        $this->defaultSettings['bg_col_type_options'] = [self::OPTION_BACKGROUND_CUSTOM_COLOUR => 'Custom', self::OPTION_BACKGROUND_SEX_COLOUR => 'Based on individual&apos;s sex', self::OPTION_BACKGROUND_VITAL_COLOUR => 'Based on vital status', self::OPTION_BACKGROUND_AGE_COLOUR => 'Based on age'];
        $this->defaultSettings['stripe_col_type_options'] = [self::OPTION_STRIPE_NONE => 'No stripe', self::OPTION_STRIPE_SEX_COLOUR => 'Based on individual&apos;s sex', self::OPTION_STRIPE_VITAL_COLOUR => 'Based on vital status', self::OPTION_STRIPE_AGE_COLOUR => 'Based on age'];
        $this->defaultSettings['border_col_type_options'] = [self::OPTION_BORDER_CUSTOM_COLOUR => 'Custom', self::OPTION_BORDER_SEX_COLOUR => 'Based on individual&apos;s sex', self::OPTION_BORDER_FAMILY => 'Same as family border', self::OPTION_BORDER_VITAL_COLOUR => 'Based on vital status', self::OPTION_BORDER_AGE_COLOUR => 'Based on age'];
        $this->defaultSettings['settings_sort_order_options'] = [0 => 'Oldest first', 10 => 'Newest first', 20 => 'Alphabetical order', 30 => 'Reverse alphabetical order'];
        $this->defaultSettings['click_action_indi_options'] = [0 => 'Open individual\'s page', 10 => 'Add individual to list of starting individuals', 20 => 'Replace starting individuals with this individual', 30 => 'Add this individual to the list of stopping individuals', 40 => 'Replace stopping individuals with this individual', 70 => 'Add to list of individuals to highlight', 50 => 'Show menu', 60 => 'Do nothing'];
        $this->defaultSettings['countries'] = $this->getCountryAbbreviations();
        if (!$this->isGraphvizAvailable($this->defaultSettings['graphviz_bin'])) {
            $this->defaultSettings['graphviz_bin'] = "";
        }
        $this->defaultSettings['graphviz_config'] = $this->getGraphvizSettings($this->defaultSettings);
        $this->defaultSettings['sharednote_col_data'] = '[]';
        $this->defaultSettings['updated_date'] = '';
        $this->defaultSettings['highlight_custom_json'] = '{}';

    }

    /**
     * Retrieve the currently set default settings from the admin page
     *
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return $this->defaultSettings;
    }

    /**
     * Retrieve the currently set default settings from the admin page
     *
     * @param GVExport $module
     * @return array
     */
    public function getAdminSettings(GVExport $module): array
    {
        $settings = $this->defaultSettings;
        $loaded = $module->getPreference(self::PREFERENCE_PREFIX . self::ADMIN_PREFERENCE_NAME, "preference not set");
        if ($loaded != "preference not set") {
            $loaded_settings = json_decode($loaded, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                foreach ($settings as $preference => $value) {
                    if (self::shouldLoadSetting($preference, self::CONTEXT_ADMIN)) {
                        if (isset($loaded_settings[$preference])) {
                            $pref = $loaded_settings[$preference];
                            if ($pref == 'true' || $pref == 'false') {
                                $settings[$preference] = ($pref == 'true');
                            } else {
                                $settings[$preference] = $pref;
                            }
                        }
                    }
                }
            } else {
                throw new HttpBadRequestException(I18N::translate('Invalid JSON') . " 1: " . json_last_error_msg() . $loaded);
            }
        }
        $settings['graphviz_config'] = $this->getGraphvizSettings($settings);
        return $settings;

    }

    /**
     * Retrieve the user settings
     *
     * @param GVExport $module
     * @param Tree $tree
     * @param string $id
     * @param int|null $user_id
     * @return array
     */
    public function loadUserSettings(GVExport $module, Tree $tree, string $id = self::ID_MAIN_SETTINGS, int $user_id = null): array
    {
        if ($user_id === null) {
            $user_id = Auth::user()->id();
        }
        $settings = $this->getAdminSettings($module);
        if ($user_id == self::GUEST_USER_ID) {
            $cookie = new Cookie($tree);
            $settings = $cookie->load($settings);
        } else {
            $settings_pref_name = self::PREFERENCE_PREFIX . self::TREE_PREFIX . $tree->id() . self::USER_PREFIX . $user_id;
            $loaded = $this->settings_json_cache[$settings_pref_name] ?? $module->getPreference($settings_pref_name, "preference not set");
            if ($loaded != "preference not set") {
                $all_settings = json_decode($loaded, true);
                if ($id == self::ID_ALL_SETTINGS) {
                    unset($all_settings[self::ID_MAIN_SETTINGS]);
                    return $all_settings;
                } else {
                    if (isset($all_settings[$id]) && json_last_error() === JSON_ERROR_NONE) {
                        $loaded_settings = json_decode($all_settings[$id]['settings'], true);
                        $loaded_settings = $this->migrate($loaded_settings);

                        if (json_last_error() === JSON_ERROR_NONE) {
                            foreach ($settings as $preference => $value) {
                                if (($preference == 'enable_graphviz' || $preference == 'enable_debug_mode') && !$settings['show_debug_panel']) {
                                    continue;
                                }
                                $context = ($id == self::ID_MAIN_SETTINGS) ? self::CONTEXT_USER : self::CONTEXT_NAMED_SETTING;
                                if (self::shouldLoadSetting($preference, $context)) {
                                    if (isset($loaded_settings[$preference])) {
                                        $pref = $loaded_settings[$preference];
                                        if ($pref == 'true' || $pref == 'false') {
                                            $settings[$preference] = ($pref == 'true');
                                        } else {
                                            $settings[$preference] = $pref;
                                        }
                                    }
                                }
                            }
                        } else {
                            throw new HttpBadRequestException(I18N::translate('Invalid JSON') . " 1: " . json_last_error_msg() . $loaded);
                        }
                    } else {
                        if ($id !== self::ID_MAIN_SETTINGS) {
                            throw new HttpBadRequestException(I18N::translate('Invalid settings ID') . " " . e($id) . ": " . json_last_error_msg());
                        }
                    }
                }
            } else {
                $settings['first_run_xref_check'] = true;
            }
        }
        if (!$settings['enable_graphviz'] && $settings['graphviz_bin'] != "") {
            $settings['graphviz_bin'] = "";
        }
        return $settings;
    }

    /** Given an array of settings, migrate old settings into the new settings structure
     *
     * @param $settings
     * @return array
     */
    private function migrate($settings): array
    {
        $migrated = $settings;
        if (isset($migrated['highlight_custom_json'])) {
            $highlight = json_decode($migrated['highlight_custom_json'], true);
        } else {
            $highlight = [];
        }
        // Migrate custom highlight settings to new JSON format
        if (isset($migrated['highlight_custom_col']) && isset($migrated['highlight_custom'])) {
            $xrefs = explode(',', $migrated['highlight_custom']);
            foreach ($xrefs as $xref) {
                if (trim($xref) != "") {
                    if (!isset($highlight[$xref])) {
                        $highlight[$xref] = $migrated['highlight_custom_col'];
                        $migrated['highlight_custom_indis'] = true;
                    }
                }
            }
        }

        // Migrate highlighted starting indis to new custom highlight function
        if (isset($migrated['highlight_start_indis']) && $migrated['highlight_start_indis'] && isset($migrated['highlight_col']) && isset($migrated['xref_list']) && isset($migrated['no_highlight_xref_list'])) {
            $xrefs = explode(',', $migrated['xref_list']);
            $no_highlight = explode(',', $migrated['no_highlight_xref_list']);
            $highlight = [];
            foreach ($xrefs as $xref) {
                if (trim($xref) != "" && !isset($highlight[$xref]) && !in_array($xref, $no_highlight)) {
                    $highlight[$xref] = $migrated['highlight_custom_col'];
                    $migrated['highlight_custom_indis'] = true;
                }
            }
        }

        $migrated['highlight_custom_json'] = json_encode($highlight);

        return $migrated;
    }

    /**
     *  Save the provided settings to webtrees admin storage
     *
     * @param GVExport $module
     * @param array $settings
     * @return void
     */
    public function saveAdminSettings(GVExport $module, array $settings) {
        $saveSettings = $this->defaultSettings;
        $s = [];
        foreach ($saveSettings as $preference=>$value) {
            if (self::shouldSaveSetting($preference, Settings::CONTEXT_ADMIN)) {
                if (isset($settings[$preference])) {
                    if (gettype($value) == 'boolean') {
                        $s[$preference] = ($settings[$preference] ? 'true' : 'false');
                    } else {
                        $s[$preference] = $settings[$preference];
                    }
                } else {
                    $s[$preference] = 'false';
                }
            }
        }
        $json = json_encode($s);
        $module->setPreference(self::PREFERENCE_PREFIX . self::ADMIN_PREFERENCE_NAME, $json);
    }

    /**
     *  Save the provided settings to webtrees user per-tree storage
     *
     * @param GVExport $module
     * @param Tree $tree
     * @param array $settings
     * @param string $id
     * @return bool
     */
    public function saveUserSettings(GVExport $module, Tree $tree, array $settings, string $id = self::ID_MAIN_SETTINGS): bool
    {
        if (Auth::user()->id() == self::GUEST_USER_ID) {
            if ($id == self::ID_MAIN_SETTINGS) {
                $cookie = new Cookie($tree);
                $cookie->set($settings);
                return true;
            } else {
                return false; // Logged-out users are handled in local storage, this should never happen
            }
        } else {
            $s = [];
            foreach ($settings as $preference => $value) {
                $context = ($id == self::ID_MAIN_SETTINGS) ? self::CONTEXT_USER : self::CONTEXT_NAMED_SETTING;
                if (self::shouldSaveSetting($preference, $context)) {
                    if (gettype($value) == 'boolean') {
                        $s[$preference] = ($value ? 'true' : 'false');
                    } else {
                        $s[$preference] = $value;
                    }
                }
            }
            $this->addUserSettings($module, $tree, $id, $s);
            return true;
        }
    }

    /**
     * Delete the indicated user settings from webtrees storage
     *
     * @param GVExport $module
     * @param Tree $tree
     * @param string $id
     * @return void
     */
    public function deleteUserSettings(GVExport $module, Tree $tree, string $id) {
        if (Settings::isUserLoggedIn()) {
            $loaded = $module->getPreference(self::PREFERENCE_PREFIX . self::TREE_PREFIX . $tree->id() . self::USER_PREFIX . Auth::user()->id(), "preference not set");
            if ($loaded != "preference not set") {
                $all_settings = json_decode($loaded, true);
                if (isset($all_settings[$id]) && json_last_error() === JSON_ERROR_NONE) {
                    unset($all_settings[$id]);
                } else {
                    throw new HttpBadRequestException(I18N::translate('Invalid settings ID') . " " . e($id) . ": " . json_last_error_msg());
                }
                $new_json = json_encode($all_settings);
                $module->setPreference(self::PREFERENCE_PREFIX . self::TREE_PREFIX . $tree->id() . self::USER_PREFIX . Auth::user()->id(), $new_json);
                $this->deleteSettingsId($module, $tree, $id);
                $settingsLink = new SettingsLink($module, $tree, $this, $id);
                if (!$settingsLink->removeTokenRecord()) {
                    throw new HttpBadRequestException(I18N::translate('Invalid') . " - E1");
                }
            }
        }
    }

    /**
     * Check if exec function is available to prevent error if webserver has disabled it
     * From: https://stackoverflow.com/questions/3938120/check-if-exec-is-disabled
     * @return bool
     */
    private function is_exec_available(): bool
    {
        static $available;

        if (!isset($available)) {
            $available = true;
            if (ini_get('safe_mode')) {
                $available = false;
            } else {
                $d = ini_get('disable_functions');
                $s = ini_get('suhosin.executor.func.blacklist');
                if ("$d$s") {
                    $array = preg_split('/,\s*/', "$d,$s");
                    if (in_array('exec', $array)) {
                        $available = false;
                    }
                }
            }
        }

        return $available;
    }

    /**
     * Check if Graphviz is available
     *
     * @param $binPath
     * @return mixed|string
     */
    private function isGraphvizAvailable($binPath)
    {
        static $outcome;

        if (!isset($outcome)) {
            if ($binPath == "") {
                $outcome = false;
                return false;
            }
            $stdout_output = null;
            $return_var = null;
            try {
                if ($this->is_exec_available()) {
                    exec($binPath . " -V" . " 2>&1", $stdout_output, $return_var);
                }
                if (!$this->is_exec_available() || $return_var !== 0) {
                    $outcome = false;
                } else {
                    $outcome = true;
                }
            } catch (Exception $error) {
                return false;
            }
        }
        return $outcome;
    }

    /**
     * Load country data for abbreviating place names
     * Data comes from https://www.datahub.io/core/country-codes
     * This material is licensed by its maintainers under the Public Domain Dedication and License, however,
     * they note that the data is ultimately sourced from ISO who have an unclear licence regarding use,
     * particularly around commercial use. Though all data sources providing ISO data have this problem.
     * @return array
     */
    private function getCountryAbbreviations(): array
    {
        $string = file_get_contents(dirname(__FILE__) . "/../resources/data/country-codes_json.json");
        $json = json_decode($string, true);
        $countries = [];
        foreach ($json as $row) {
            $countries['iso2'][strtolower($row['Name'])] = $row['ISO3166-1-Alpha-2'];
            $countries['iso3'][strtolower($row['Name'])] = $row['ISO3166-1-Alpha-3'];
        }
        return $countries;
    }

    private function getGraphvizSettings($settings): array
    {
        // Output file formats
        $Graphviz['output']['svg']['label'] = "SVG"; #ESL!!! 20090213
        $Graphviz['output']['svg']['extension'] = "svg";
        $Graphviz['output']['svg']['exec'] = $settings['graphviz_bin'] . " -Tsvg:cairo -o" . $settings['filename'] . ".svg " . $settings['filename'] . ".dot";
        $Graphviz['output']['svg']['cont_type'] = "image/svg+xml";

        $Graphviz['output']['dot']['label'] = "DOT"; #ESL!!! 20090213
        $Graphviz['output']['dot']['extension'] = "dot";
        $Graphviz['output']['dot']['exec'] = "";
        $Graphviz['output']['dot']['cont_type'] = "text/plain; charset=utf-8";

        $Graphviz['output']['png']['label'] = "PNG"; #ESL!!! 20090213
        $Graphviz['output']['png']['extension'] = "png";
        $Graphviz['output']['png']['exec'] = $settings['graphviz_bin'] . " -Tpng -o" . $settings['filename'] . ".png " . $settings['filename'] . ".dot";
        $Graphviz['output']['png']['cont_type'] = "image/png";

        $Graphviz['output']['jpg']['label'] = "JPG"; #ESL!!! 20090213
        $Graphviz['output']['jpg']['extension'] = "jpg";
        $Graphviz['output']['jpg']['exec'] = $settings['graphviz_bin'] . " -Tjpg -o" . $settings['filename'] . ".jpg " . $settings['filename'] . ".dot";
        $Graphviz['output']['jpg']['cont_type'] = "image/jpeg";

        $Graphviz['output']['pdf']['label'] = "PDF"; #ESL!!! 20090213
        $Graphviz['output']['pdf']['extension'] = "pdf";
        $Graphviz['output']['pdf']['exec'] = $settings['graphviz_bin'] . " -Tpdf -o" . $settings['filename'] . ".pdf " . $settings['filename'] . ".dot";
        $Graphviz['output']['pdf']['cont_type'] = "application/pdf";

        if ( !empty( $settings['graphviz_bin']) && $settings['graphviz_bin'] != "") {

            $Graphviz['output']['gif']['label'] = "GIF"; #ESL!!! 20090213
            $Graphviz['output']['gif']['extension'] = "gif";
            $Graphviz['output']['gif']['exec'] = $settings['graphviz_bin'] . " -Tgif -o" . $settings['filename'] . ".gif " . $settings['filename'] . ".dot";
            $Graphviz['output']['gif']['cont_type'] = "image/gif";

            $Graphviz['output']['ps']['label'] = "PS"; #ESL!!! 20090213
            $Graphviz['output']['ps']['extension'] = "ps";
            $Graphviz['output']['ps']['exec'] = $settings['graphviz_bin'] . " -Tps2 -o" . $settings['filename'] . ".ps " . $settings['filename'] . ".dot";
            $Graphviz['output']['ps']['cont_type'] = "application/postscript";
        }

        return $Graphviz;
    }

    /**
     * Returns whether a setting should or shouldn't be saved to cookies/preferences
     *
     * @param string $preference
     * @param int $context
     * @return bool
     */
    public static function shouldSaveSetting(string $preference, int $context = self::CONTEXT_USER): bool
    {
        switch ($preference) {
            case 'graphviz_bin':
            case 'graphviz_config':
            case 'typefaces':
            case 'typeface_fallback':
            case 'directions':
            case 'url_xref_treatment_options':
            case 'use_abbr_places':
            case 'use_abbr_names':
            case 'countries':
            case 'temp_dir':
            case 'space_base':
            case 'no_fams':
            case 'stop_proc':
            case 'compress_cookie':
            case 'photo_shape_options':
            case 'photo_quality_options':
            case 'indi_tile_shape_options':
            case 'indi_tile_shape_custom_options':
            case 'bg_col_type_options':
            case 'stripe_col_type_options':
            case 'border_col_type_options':
            case 'settings_sort_order_options':
            case 'click_action_indi_options':
                return false;
            case 'show_debug_panel':
            case 'filename':
            case 'mclimit':
            case 'birth_prefix':
            case 'death_prefix':
            case 'marriage_prefix':
                return $context == self::CONTEXT_ADMIN;
            case 'show_diagram_panel':
                return $context != self::CONTEXT_NAMED_SETTING;
                // Include these in everything (especially including cookie)
            case 'include_ancestors':
            case 'include_descendants':
            case 'ancestor_levels':
            case 'descendant_levels':
            case 'include_siblings':
            case 'include_all_relatives':
            case 'include_spouses':
            case 'include_all':
            case 'xref_list':
            case 'stop_xref_list':
            case 'mark_not_related':
            case 'faster_relation_check':
            case 'url_xref_treatment':
            case 'graph_dir':
            case 'diagtype_decorated':
            case 'diagtype_combined':
            case 'output_type':
            case 'show_adv_people':
            case 'show_adv_appear':
            case 'show_adv_files':
                return true;
                // Include everything else in most contexts but not in cookie
            default:
                return $context !== self::CONTEXT_COOKIE;
        }
    }

    /**
     * Currently an alias for shouldSaveSetting as the criteria are the same
     *
     * @param $setting
     * @param int $context
     * @return bool
     */
    public static function shouldLoadSetting($setting, int $context = self::CONTEXT_USER): bool
    {
        if ($setting == 'updated_date' && $context !== self::CONTEXT_MAIN_SETTINGS) {
            return false;
        }
        return self::shouldSaveSetting($setting, $context);
    }


    /**
     * Retrieve the JSON of the requested setting ID
     *
     * @param GVExport $module
     * @param Tree $tree
     * @param string $id
     * @return false|string
     * @throws Exception
     */
    public function getSettingsJson(GVExport $module, Tree $tree, string $id)
    {
        $userSettings = $this->loadUserSettings($module, $tree, $id);
        return self::getJsonFromSettings($userSettings);
    }

    /**
     * Turn a settings array into JSON
     */
    public function getJsonFromSettings($settings, $context = Settings::CONTEXT_USER)
    {
        $new_settings = [];
        foreach ($this->defaultSettings as $preference => $value) {
            if (self::shouldLoadSetting($preference, $context)) {
                $new_settings[$preference] = $settings[$preference];
            }
        }
        return json_encode($new_settings);
    }
    /**
     * Retrieve all settings for the user as JSON
     *
     * @param $module
     * @param $tree
     * @return false|string
     */
    public function getAllSettingsJson($module, $tree)
    {
        $settings = $this->loadUserSettings($module, $tree, Settings::ID_ALL_SETTINGS);
        return json_encode($settings);
    }

    /**
     * Return a link for sharing settings
     *
     * @param GVExport $module
     * @param Tree $tree
     * @param string $id
     * @return array
     */
    public function getSettingsLink(GVExport $module, Tree $tree, string $id): array
    {
        if ($this->doSettingsExist($module, $tree)) {
            $link = new SettingsLink($module, $tree, $this, $id);
            try {
                $response['url'] = $link->getUrl();
                $response['success'] = true;
            } catch (Exception $error) {
                $response['success'] = false;
                $response['error'] = $error;
            }

        } else {
            $response['success'] = false;
            $response['error'] = "Settings don't exist";
        }

        return $response;
    }


    /**
     * Retrieve settings based on settings token from shared link
     *
     * @param GVExport $module
     * @param Tree $tree
     * @param string $token
     * @return array
     * @throws Exception
     */
    public function loadSettingsToken(GVExport $module, Tree $tree, string $token): array
    {
        $link = new SettingsLink($module, $tree, $this);
        try {
            $settings = $link->loadToken($token, $this);
        } catch (Exception $e) {
            throw new Exception($e);
        }
        return $settings;
    }

    /**
     * Create a new settings ID
     *
     * @param GVExport $module
     * @param Tree $tree
     * @return string
     */
    public function newSettingsId(GVExport $module, Tree $tree): string
    {
        $id_list = $this->getSettingsIdList($module, $tree);

        if ($id_list == "") {
            $id_list = "0";
            $new_id = "0";
        } else {
            $preferences = explode(',', $id_list);
            $last_id = end($preferences);
            if ($last_id != "") {
                $next_id = (int)base_convert($last_id, 36, 10) + 1;
                $new_id = base_convert($next_id, 10, 36);
                $id_list = $id_list . "," . $new_id;
            } else {
                return "";
            }
        }
        $this->setSettingsIdList($module, $tree, $id_list);
        return $new_id;
    }

    /**
     * Remove settings ID from list of IDs
     *
     * @param GVExport $module
     * @param Tree $tree
     * @param string $id
     * @return string
     */
    public function deleteSettingsId(GVExport $module, Tree $tree, string $id): string
    {
        $id_list = $this->getSettingsIdList($module, $tree);
        if ($id_list == "") {
           return false;
        } else {
            $preferences = explode(',', $id_list);
            $key = array_search($id, $preferences);
            unset($preferences[$key]);
            if ($this->setSettingsIdList($module, $tree, implode(',', $preferences))) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Retrieve the list of settings IDs used to keep track of valid IDs
     *
     * @param GVExport $module
     * @param Tree $tree
     * @return string
     */
    private function getSettingsIdList(GVExport $module, Tree $tree): string
    {
        $user = Auth::user()->id();
        $use_cookie = $user == self::GUEST_USER_ID;
        if ($use_cookie) {
            return ""; // Logged-out users are handled via local storage, so this should never happen
        } else {
            $pref_list = $module->getPreference(self::PREFERENCE_PREFIX . self::SETTINGS_LIST_PREFERENCE_NAME . self::TREE_PREFIX . $tree->id(), "preference not set");
            if ($pref_list == "preference not set") {
                $pref_list = "";
            }
        }
        return $pref_list;
    }

    /**
     * Check if current user is logged in
     *
     * @return bool
     */
    public static function isUserLoggedIn(): bool
    {
        if (Auth::user()->id() == self::GUEST_USER_ID) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Saves the provided settings into the webtrees preferences
     *
     * @param GVExport $module
     * @param Tree $tree
     * @param string $id
     * @param array $s
     * @return void
     */
    private function addUserSettings(GVExport $module, Tree $tree, string $id, array $s)
    {
        $prefs_json = $module->getPreference(self::PREFERENCE_PREFIX . self::TREE_PREFIX . $tree->id() . self::USER_PREFIX . Auth::user()->id(), "preference not set");
        if ($prefs_json == "preference not set") {
            $settings = [];
        } else {
            $settings = json_decode($prefs_json, true);
        }
        $json = json_encode($s);
        $settings[$id]['settings'] = $json;
        $settings[$id]['name'] = $s['save_settings_name'];
        $settings[$id]['updated_date'] = $s['updated_date'];
        $settings[$id]['id'] = $id;
        $settings[$id]['token'] = empty($s['token']) ? '':$s['token'];
        $new_json = json_encode($settings);
        $module->setPreference(self::PREFERENCE_PREFIX . self::TREE_PREFIX . $tree->id() . self::USER_PREFIX . Auth::user()->id(), $new_json);
    }

    /**
     * Saves the provided settings ID list into the webtrees preferences
     *
     * @param GVExport $module
     * @param Tree $tree
     * @param string $id_list
     * @return bool
     */
    private function setSettingsIdList(GVExport $module, Tree $tree, string $id_list): bool
    {
        $use_cookie = Auth::user()->id() == self::GUEST_USER_ID;
        if ($use_cookie) {
            return false; // Logged-out users are handled in local storage, this should never happen
        } else {
            $module->setPreference(self::PREFERENCE_PREFIX . self::SETTINGS_LIST_PREFERENCE_NAME . self::TREE_PREFIX . $tree->id(), $id_list);
            return true;
        }
    }

    /**
     * Each tree/user combination has its own settings file that holds all
     * the different settings for that user/tree combination. This checks
     * if such a file already exists.
     *
     * @param $module
     * @param $tree
     * @return bool
     */
    private function doSettingsExist($module, $tree): bool
    {
        if (Auth::user()->id() == self::GUEST_USER_ID) {
            return false;
        } else {
            $settings_pref_name = self::PREFERENCE_PREFIX . self::TREE_PREFIX . $tree->id() . self::USER_PREFIX . Auth::user()->id();
            $loaded = $this->settings_json_cache[$settings_pref_name] ?? $module->getPreference($settings_pref_name, "preference not set");
            if ($loaded == "preference not set") {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Trigger the removal of a settings token
     * (e.g. when a shared settings link is revoked)
     *
     * @param $module
     * @param $tree
     * @param $token
     * @return bool
     */
    public function revokeSettingsToken($module, $tree, $token): bool
    {
        $settingsLink = new SettingsLink($module, $tree, $this);
        return $settingsLink->revokeToken($token);
    }

    /**
     * Get the name of a saved settings entry based on it's ID/tree
     *
     * @param $module
     * @param $tree
     * @param $settings_id
     * @return mixed
     */
    public function getSettingsName($module, $tree, $settings_id)
    {
        $userSettings = $this->loadUserSettings($module, $tree, $settings_id);
        return $userSettings['save_settings_name'];
    }
}
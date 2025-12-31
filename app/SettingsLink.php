<?php

namespace vendor\WebtreesModules\gvexport;

use Exception;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Services\GedcomImportService;
use Fisharebest\Webtrees\Services\TreeService;
use Fisharebest\Webtrees\Tree;

/**
 * Represents a shared settings link, created by having a logged-in user
 * save a named version of settings, then have them choose to create a
 * link to share those settings
 */
class SettingsLink
{
    const TOKEN_PREFIX = "&t=";
    const TOKEN_LENGTH = 10;
    private string $base_url;
    private GVExport $module;
    private Tree $tree;
    private int $userId;
    private string $id;
    private Settings $settings_obj;

    public function __construct($module, $tree, $parent_obj, $id = "")
    {
        $this->module = $module;
        $this->tree = $tree;
        $this->id = $id;
        $this->settings_obj = $parent_obj;
        if (User::isUserLoggedIn()) {
            $this->userId = Auth::user()->id();
            $this->base_url = $module->base_url;
        }
    }

    /**
     * Retrieve URL for shared setting, creating it if it does not exist
     *
     * @return string
     * @throws Exception
     */
    public function getUrl(): string
    {
        try {
            $record = $this->getSharedSettingsList();
        } catch (Exception $error) {
            throw new Exception($error);
        }

        foreach ($record as $key => $value) {
            if ($value['user'] == $this->userId && $value['tree'] == $this->tree->id() && $value['settings_id'] == $this->id) {
                $token = $key;
            }
        }

        if (!isset($token)) {
            // Capital letters only, and removed 0 and O to try to reduce transcribing errors
            do {
                $token = substr(str_shuffle(str_repeat("123456789ABCDEFGHIJKLMNPQRSTUVWXYZ", self::TOKEN_LENGTH)), 0, self::TOKEN_LENGTH);
            } while (isset($record[$token]));
            $record[$token]['user'] = $this->userId;
            $record[$token]['tree'] = $this->tree->id();
            $record[$token]['settings_id'] = $this->id;
            $this->setSharedSettingsList($record);
            $this->updateSettingsWithToken($token);
        }

        return str_replace("?&", '?', $this->base_url . self::TOKEN_PREFIX . $token);
    }

    /**
     * Retrieve the list of shared settings
     *
     * @return array
     * @throws Exception
     */
    private function getSharedSettingsList(): array
    {
        $pref = $this->module->getPreference(Settings::PREFERENCE_PREFIX . Settings::SAVED_SETTINGS_LIST_PREFERENCE_NAME, "preference not set");
        if ($pref == "preference not set") {
            return [];
        } else {
            $shared_settings = json_decode($pref, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(json_last_error());
            }
            return $shared_settings;
        }
    }

    /**
     * Save the list of shared settings into the webtrees preferences
     *
     * @param array $record
     * @return void
     */
    private function setSharedSettingsList(array $record)
    {
        $json = json_encode($record);
        $this->module->setPreference(Settings::PREFERENCE_PREFIX . Settings::SAVED_SETTINGS_LIST_PREFERENCE_NAME, $json);
    }

    /**
     * Retrieve settings array based on shared settings token
     *
     * @param string $token
     * @param Settings $settings
     * @return array
     * @throws Exception
     */
    public function loadToken(string $token, Settings $settings): array
    {
        $shared_settings_list = $this->getSharedSettingsList();
        $this->userId = $shared_settings_list[$token]['user'];
        $tree_service = new TreeService(new GedcomImportService());
        $this->tree = $tree_service->find($shared_settings_list[$token]['tree']);
        $this->id = $shared_settings_list[$token]['settings_id'];
        if (isset($shared_settings_list[$token])) {
            $userSettings = $settings->loadUserSettings($this->module, $this->tree, $this->id, $this->userId);
            $preferences = [];
            foreach ($settings->getDefaultSettings() as $preference => $value) {
                if (Settings::shouldLoadSetting($preference)) {
                    $preferences[$preference] = $userSettings[$preference];
                }
            }
            return $preferences;
        } else {
            throw new Exception("Invalid token");
        }
    }

    /**
     * Update the shared settings list of tokens to remove a
     * shared settings token
     *
     * @param string $token
     * @return bool
     */
    public function removeTokenRecord(string $token = ''): bool
    {
        try {
            $sharedSettingsList = $this->getSharedSettingsList();
            if ($token == '') {
                foreach ($sharedSettingsList as $key => $value) {
                    if ($value['user'] === Auth::user()->id() &&
                        $value['tree'] === $this->tree->id() &&
                        $value['settings_id'] === $this->id) {
                        unset($sharedSettingsList[$key]);
                    }
                }
            } else {
                unset($sharedSettingsList[$token]);
            }
            $this->setSharedSettingsList($sharedSettingsList);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Adds a shared settings token into the settings record saved in webtrees
     *
     * @param $token
     * @return bool
     */
    public function updateSettingsWithToken($token): bool
    {
        if (Auth::user()->id() == Settings::GUEST_USER_ID) {
            return false;
        } else {
            $settings = $this->settings_obj->loadUserSettings($this->module, $this->tree, $this->id);
            $settings['token'] = $token;
            $this->settings_obj->saveUserSettings($this->module, $this->tree, $settings, $this->id);
            return true;
        }
    }

    /**
     * Removes a shared settings token, so it can't be used anymore
     *
     * @param $token
     * @return bool
     */
    public function revokeToken($token): bool
    {
        try {
            $sharedSettingsList = $this->getSharedSettingsList();
            if (isset($sharedSettingsList[$token])) {
                $this->id = $sharedSettingsList[$token]['settings_id'];
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
        $clearTokenFromSettings = $this->updateSettingsWithToken('');
        $removeToken = $this->removeTokenRecord($token);
        return $clearTokenFromSettings && $removeToken;
    }
}
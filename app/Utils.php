<?php

namespace vendor\WebtreesModules\gvexport;

use Exception;

/**
 * Static functions that perform generic actions
 */
class Utils
{

    /**
     * Check if key is found in json
     *
     * @param string $json the json as a string
     * @param string $key the key to look for
     * @return bool
     */
    static function isKeyInJson(string $json, string $key): bool
    {
        try {
            $data = json_decode($json, true);
        } catch (Exception $e) {
            return false;
        }
        return isset($data[$key]);
    }

    /**
     * Check if exec function is available to prevent error if webserver has disabled it
     * From: https://stackoverflow.com/questions/3938120/check-if-exec-is-disabled
     * @return bool
     */
    static function is_exec_available(): bool
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
    static function isGraphvizAvailable($binPath)
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
                if (Utils::is_exec_available()) {
                    exec($binPath . " -V" . " 2>&1", $stdout_output, $return_var);
                }
                if (!Utils::is_exec_available() || $return_var !== 0) {
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
     * Loads country data from JSON file for converting to ISO code
     *
     * @param $type
     * @return array|false
     */
    static function loadCountryDataFile($type)
    {
        switch ($type) {
            case 'iso2':
                $string = file_get_contents(dirname(__FILE__) . "/../resources/data/CountryRegionCodes2Char.json");
                break;
            case 'iso3':
                $string = file_get_contents(dirname(__FILE__) . "/../resources/data/CountryRegionCodes3Char.json");
                break;
            default:
                return false;
        }
        $json = json_decode($string, true);
        $countries = [];
        foreach ($json as $row => $value) {
            $countries[strtolower($row)] = strtoupper($value);
        }
        return $countries;
    }

    /**
     * Loads country data from JSON file for converting from ISO code to English name
     *
     * @param $type
     * @return array|false
     */
    static function loadCountryDataFileToNameArray()
    {
        $codeToCountry = file_get_contents(dirname(__FILE__) . "/../resources/data/CountryFromCode.json");

        $countries = [];

        $json = json_decode($codeToCountry, true);
        foreach ($json as $code => $country) {
            $countries[strtoupper($code)] = $country;
        }
        return $countries;
    }
}
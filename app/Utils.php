<?php

namespace vendor\WebtreesModules\gvexport;

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
}
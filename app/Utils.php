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

    /**
     * Saves record count of diagram to session storage
     *
     * @param string $token Token used by front end to check record has been updated
     * @param int $indis
     * @param int $fams
     * @return void
     */
    public static function updateRecordCount(string $token, int $indis, int $fams, int $images)
    {
        $data = json_encode([
            'time_token' => $token,
            'indis' => $indis,
            'fams' => $fams,
            'images' => $images
        ]);
        $_SESSION[Settings::PREFERENCE_PREFIX . '_' . Settings::RECORD_COUNT_PREFERENCE_NAME] = $data;
    }

    /**
     * Loads record count of diagram from session storage
     *
     * @param $token
     * @return string
     */
    static public function loadRecordCount($token): string
    {
        $defaults = [
            'time_token' => '',
            'indis' => -1,
            'fams' => -1,
            'images' => -1
        ];

        $records = $_SESSION[Settings::PREFERENCE_PREFIX . '_' . Settings::RECORD_COUNT_PREFERENCE_NAME] ?? null;

        $decoded = $records ? json_decode($records, true) : null;

        if ($decoded && isset($decoded['time_token']) && $decoded['time_token'] === $token) {
            return $records;
        }

        return json_encode($defaults);
    }
}
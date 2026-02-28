<?php

namespace vendor\WebtreesModules\gvexport;

use Exception;
use Fisharebest\Webtrees\Tree;

/**
 * A cookie object for saving settings when user logged out
 */
class Cookie
{
    private string $name;
    private Tree $tree;

    /**
     * Name for the cookie is generated based on tree name
     *
     * @param $tree
     */
    function __construct(Tree $tree)
    {
        $this->tree = $tree;
        $this->name = $this->getName();
    }

    /**
     * Return the name of the cookie
     *
     * @return string
     */
    private function getName(): string
    {
        // Get name of tree from webtrees
        $name = $this->tree->name();
        // Replace space with underscore
        $name =  preg_replace('/\s/', '_', $name);
        // alphanumeric / underscore characters only
        $name = preg_replace('/[^a-z0-9\s_]/i', '', $name);

        return "GVEUserDefaults_" . $name;
    }

    /**
     * Save the settings in a cookie
     *
     * @param array<mixed> $vars
     * @return void
     */
    public function set(array $vars)
    {
        $cookieArray = [];
        foreach ($vars as $preference => $value) {
            if (Settings::shouldSaveSetting($preference, Settings::CONTEXT_COOKIE)) {
                $cookieArray[$preference] = $value;
            }
        }
        $json_cookie = json_encode($cookieArray);
        $cookie_options = array(
            'expires' => time() + (3600 * 24 * 365),
            'samesite' => 'Strict'
        );
        $compress = (!empty($vars['compress_cookie']) ? gzdeflate($json_cookie) : $json_cookie);
        setcookie($this->name, $compress, $cookie_options);
    }

    /**
     * Load settings from cookie over the provided settings (e.g. the default ones)
     *
     * @param array<mixed> $userDefaultVars
     * @return array<mixed>
     */
    public function load(array $userDefaultVars): array
    {
        if (isset($_COOKIE[$this->name]) and $_COOKIE[$this->name] != "") {
            try {
                $decompressed = gzinflate($_COOKIE[$this->name]);
            } catch (Exception $e) {
                $decompressed = $_COOKIE[$this->name];
            }
            $json_cookie = json_decode($decompressed);
            if (json_last_error() === JSON_ERROR_NONE) {
                foreach ($json_cookie as $key => $value) {
                    $userDefaultVars[$key] = $value;
                }
            } else {
                // We might still have settings saved under the old system
                // if JSON not valid, attempt to load using old system.
                foreach (explode("|", $_COOKIE[$this->name]) as $s) {
                    $arr = explode("=", $s);
                    if (count($arr) == 2) {
                        $userDefaultVars[$arr[0]] = $arr[1];
                    }
                }
            }
        }
        if (!isset($userDefaultVars['enable_graphviz'])) {
            $userDefaultVars['graphviz_bin'] = "";
        }
        return $userDefaultVars;
    }
}

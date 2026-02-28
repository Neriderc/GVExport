<?php

namespace vendor\WebtreesModules\gvexport;

/**
 * File handling functionality
 */
class File
{
    /**
     * Returns the temporary dir
     *
     * Based on http://www.phpit.net/
     * article/creating-zip-tar-archives-dynamically-php/2/
     * No longer exists - see https://web.archive.org/web/20080208194622/http://www.phpit.net/article/creating-zip-tar-archives-dynamically-php/2/
     *
     * changed to prevent SAFE_MODE restrictions
     *
     * @return	string	System temp dir
     */
    public function sys_get_temp_dir_my()
    {
        // Try to get from environment variable
        if (!empty($_ENV['TMP']) && $this->is__writable($_ENV['TMP'])) {
            return realpath($_ENV['TMP']);
        } elseif (!empty($_ENV['TMPDIR']) && $this->is__writable($_ENV['TMPDIR'])) {
            return realpath($_ENV['TMPDIR']);
        } elseif (!empty($_ENV['TEMP']) && $this->is__writable($_ENV['TEMP'])) {
            return realpath($_ENV['TEMP']);
        }
        // Detect by creating a temporary file
        else {
            // Try to use system's temporary directory
            // as random name shouldn't exist
            $temp_file = tempnam(sys_get_temp_dir(), md5(uniqid((string) rand(), TRUE)));
            if ($temp_file) {
                if (!$this->is__writable(dirname($temp_file))) {
                    unlink($temp_file);

                    // Last resort: try index folder
                    // as random name shouldn't exist
                    $temp_file = tempnam(realpath("index/"), md5(uniqid((string) rand(), TRUE)));
                }

                $temp_dir = realpath(dirname($temp_file));
                unlink($temp_file);

                return $temp_dir;
            } else {
                return "";
            }
        }
    }


    /**
     * Check if path is writable
     *
     * @param $path
     * @return bool
     */
    private function is__writable(string $path): bool
    {
        //will work in despite of Windows ACLs bug
        //NOTE: use a trailing slash for folders!!!
        //see http://bugs.php.net/bug.php?id=27609
        //see http://bugs.php.net/bug.php?id=30931

        if ($path[strlen($path) - 1] == '/') // recursively return a temporary file path
            return $this->is__writable($path . uniqid((string) mt_rand()) . '.tmp');
        else if (is_dir($path))
            return $this->is__writable($path . '/' . uniqid((string) mt_rand()) . '.tmp');
        // check tmp file for read/write capabilities
        $rm = file_exists($path);
        $f = @fopen($path, 'a');
        if ($f === false)
            return false;
        fclose($f);
        if (!$rm)
            unlink($path);
        return true;
    }
}

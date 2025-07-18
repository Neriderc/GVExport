<?php
/* Output file class, which represents the file generated
 *  by Graphviz (if installed)
 */

namespace vendor\WebtreesModules\gvexport;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Fisharebest\Webtrees\Registry;


/**
 * Represents file generated by Graphviz on server and
 * returned to user as a download
 */
class OutputFile
{
    var string $tempDir;
    var string $fileType;
    var string $baseName;
    var array $settings;

    function __construct($temp_dir, $file_type, $module) {
        $this->settings = (new Settings())->getAdminSettings($module);
        $this->tempDir = $temp_dir;
        $this->fileType = $file_type;
        $this->baseName = $this->settings['filename'] . "." . $this->settings['graphviz_config']['output'][$file_type]['extension'];
    }

    /**
     * Download the output file to the user's computer
     *
     * @return mixed
     */
    function downloadFile()
    {
        $stream = $this->getFileStream();
        $response_factory = GVExport::getClass(ResponseFactoryInterface::class);
        return $response_factory->createResponse()
            ->withBody($stream)
            ->withHeader('Content-Type', $this->settings['graphviz_config']['output'][$this->fileType]['cont_type'])
            ->withHeader('Content-Disposition', "attachment; filename=" . $this->baseName);
    }

    /**
     * Run Graphviz and return the file stream of the file generated by Graphviz
     *
     * @return mixed
     */
    private function getFileStream()
    {
        $filename = $this->tempDir . "/" . $this->baseName;
        if (!empty($this->settings['graphviz_config']['output'][$this->fileType]['exec'])) {
            $shell_cmd = str_replace($this->settings['filename'], $this->tempDir . "/" . $this->settings['filename'], $this->settings['graphviz_config']['output'][$this->fileType]['exec']);
            $descriptor_spec = array(
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                2 => array("pipe", "w")
            );
            $env = $_ENV;
            unset($env['SERVER_NAME']);
            if (!function_exists('proc_open')) {
                exec($shell_cmd . " 2>&1", $stdout_output, $return_var);
                if ($return_var !== 0) {
                    die("Error (return code $return_var) executing command \"$shell_cmd\" in \"" . getcwd() . "\".<br>Graphviz error:<br><pre>" . (join("\n", $stdout_output)) . "</pre>");
                }
            } else {
                $process = proc_open($shell_cmd, $descriptor_spec, $pipes, null, $env);
                if (is_resource($process)) {
                    fclose($pipes[0]);

                    $stdout_output = stream_get_contents($pipes[1]);
                    fclose($pipes[1]);

                    $stderr_output = stream_get_contents($pipes[2]);
                    fclose($pipes[2]);

                    $return_code = proc_close($process);

                    if ($return_code !== 0) {
                        die("Error (return code $return_code) executing command \"$shell_cmd\" in \"" . getcwd() . "\".<br>Graphviz error:<br><pre>" . htmlspecialchars($stderr_output ?: $stdout_output) . "</pre>");
                    }
                } else {
                    die("Failed to start process for command: $shell_cmd");
                }
            }
        }
        return GVExport::getClass(StreamFactoryInterface::class)->createStreamFromFile($filename);
    }
}

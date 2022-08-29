<?php

/**
 * GraphViz module for Webtrees
 *
 * Ported to Webtrees by Iain MacDonald <ijmacd@gmail.com>
 */
// Classes and libraries for module system
//
// webtrees: Web based Family History software
// Copyright (C) 2012 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2010 John Finlay
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

namespace vendor\WebtreesModules\gvexport;

require_once(dirname(__FILE__) . "/config.php");
require(dirname(__FILE__) . "/utils.php");
require_once(dirname(__FILE__) . "/functionsClippingsCart.php");

//use Aura\Router\RouterContainer;
//use Exception;
//use Fig\Http\Message\RequestMethodInterface;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Registry;
//use Fisharebest\Webtrees\I18N;
use Fisharebest\Localization\Translation;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleChartInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleChartTrait;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\View;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Webtrees;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;


/**
 * Main class for GVExport module
 */
class GVExport extends AbstractModule implements ModuleCustomInterface, ModuleChartInterface
{

    use ModuleCustomTrait;
    use ModuleChartTrait;
    public const CUSTOM_VERSION     = '2.1.8';
    public const CUSTOM_MODULE      = "GVExport";
    public const CUSTOM_LATEST      = 'https://raw.githubusercontent.com/Neriderc/' . self::CUSTOM_MODULE. '/main/latest-version.txt';
    public const SUPPORT_URL        = 'https://github.com/Neriderc/GVExport';

    public function boot(): void
    {
        // Register a namespace for our views.
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
    }

    public function resourcesFolder(): string
    {
        return __DIR__ . '/resources/';
    }

    public function title(): string
    {
        return 'GVExport';
    }

    public function description(): string
    {
        return 'This is the "GVExport" module';
    }

    public function chartMenuClass(): string
    {
        return 'menu-chart-familybook';
    }

    public function chartBoxMenu(Individual $individual): ?Menu
    {
        return $this->chartMenu($individual);
    }

    public function chartUrl(Individual $individual, array $parameters = []): string
    {
        return route('module', array_merge($parameters, [
            'module' => $this->name(),
            'action' => 'Chart',
            'xref' => $individual->xref(),
            'tree' => $individual->tree()->name(),
        ]));
    }

    /**
     * The version of this module.
     *
     * @return string
     */
    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    /**
     * A URL that will provide the latest version of this module.
     *
     * @return string
     */
    public function customModuleLatestVersionUrl(): string
    {
        return self::CUSTOM_LATEST;
    }

    /**
     * Where to get support for this module.
     *
     * @return string
     */
    public function customModuleSupportUrl(): string
    {
        return self::SUPPORT_URL;
    }

    public function getIndividual($tree, $xref): Individual
    {
        $individual = Registry::individualFactory()->make($xref, $tree);
        $individual = Auth::checkIndividualAccess($individual, false, true);

        return $individual;
    }

    public function getChartAction(ServerRequestInterface $request): ResponseInterface
    {
        global $GVE_CONFIG;

        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $individual = $this->getIndividual($tree, $request->getQueryParams()['xref']);

		$userDefaultVars = [ //Defaults (this could be defined in the config?)
            "otype" => "svg",
            "grdir" => $GVE_CONFIG["default_direction"],
            "mclimit" => $GVE_CONFIG["default_mclimit"],
            "psize" => $GVE_CONFIG["default_pagesize"],
            "indiinc" => "indi",
            "diagtype" => "decorated",
            "with_photos" => "",
            //"use_abbr_place" => ($GVE_CONFIG['settings']['use_abbr_place'] ? "use_abbr_place" : ""),  // duplicated key
            "show_by" => "show_by",
            "bd_type" => "gedcom",
            "show_bp" => "show_bp",
            "show_dy" => "show_dy",
            "dd_type" => "gedcom",
            "show_dp" => "show_dp",
            "show_my" => "show_my",
            "md_type" => "gedcom",
            "show_mp" => "show_mp",
            "indiance" => "ance",
            "ance_level" => $GVE_CONFIG["settings"]["ance_level"],
            "indisibl" => "sibl",
            "indicous" => "cous",
            "tree_type" => "tree_type",
            "indidesc" => "desc",
            "desc_level" => $GVE_CONFIG["settings"]["desc_level"],
            "indispou" => "spou",
            "indiany" => "",
            "marknr" => "marknr",
            "show_url" => "show_url",
            "show_pid" => "DEFAULT", // This is set to DEFAULT so we can tell if it was loaded from cookie or not
            "show_fid" => "",
            "use_abbr_place" => "Full place name",
            "debug" => ($GVE_CONFIG['debug'] ? "debug" : ""),
            "dpi" => $GVE_CONFIG["settings"]["dpi"],
            "ranksep" => $GVE_CONFIG["settings"]["ranksep"],
            "nodesep" => $GVE_CONFIG["settings"]["nodesep"],
            "other_pids" => "",
            "stop_pid" => "",
            "other_stop_pids" => "",
            "download" => TRUE,
            "usecart" => $GVE_CONFIG["settings"]["usecart"]
        ];
        if (!isset($_REQUEST['reset']) and isset($_COOKIE["GVEUserDefaults"]) and $_COOKIE["GVEUserDefaults"] != "") {
            foreach (explode("|", $_COOKIE["GVEUserDefaults"]) as $s) {
                $arr = explode("=", $s);
                if (count($arr) == 2) {
                    $userDefaultVars[$arr[0]] = $arr[1];
                }
            }
        }

        $otypes = array();
        foreach ($GVE_CONFIG["output"] as $fmt => $val) {
            if (isset($GVE_CONFIG["output"][$fmt]["label"]) and isset($GVE_CONFIG["output"][$fmt]["extension"])) {
                $lbl = $GVE_CONFIG["output"][$fmt]["label"];
                $ext = $GVE_CONFIG["output"][$fmt]["extension"];
                $otypes[$ext] = $lbl;
            }
        }

        return $this->viewResponse($this->name() . '::page', [
            'gvexport_css'  => route('module', ['module' => $this->name(), 'action' => 'Css']),
            'gvexport_js'  => route('module', ['module' => $this->name(), 'action' => 'JS']),
            'tree'          => $tree,
            'individual'    => $individual,
            'title'         => 'GVExport',
            'vars'          => $userDefaultVars,
            'otypes'        => $otypes,
            'gve_config'    => $GVE_CONFIG,
            'cartempty'     => !functionsClippingsCart::isIndividualInCart($tree),
            'module'        => $this,
            'nographviz' => $GVE_CONFIG["graphviz_bin"] == ""
        ]);
    }

    /**
     * Where are the CCS specifications for this module stored?
     *
     * @return ResponseInterface
     *
     * @throws \JsonException
     */
    public function getCssAction() : ResponseInterface
    {
        return response(
            file_get_contents($this->resourcesFolder() . 'css' . DIRECTORY_SEPARATOR . 'gvexport.css'),
            200,
            ['content-type' => 'text/css']
        );
    }

    /**
     * Where are the Javascript functions for this module stored?
     *
     * @return ResponseInterface
     *
     * @throws \JsonException
     */
    public function getJSAction() : ResponseInterface
    {
        return response(
            file_get_contents($this->resourcesFolder() . 'javascript' . DIRECTORY_SEPARATOR . 'gvexport.js'),
            200,
            ['content-type' => 'text/javascript']
        );
    }

    public function postChartAction(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $individual = $this->getIndividual($tree, $_REQUEST['pid']);            // is this defined in all cases ???
        unset($_REQUEST["vars"]["debug"]);
        if (isset($_REQUEST["vars"]["debug"])) {
            return $this->showDOTFile($individual->tree(), $individual);
        } else {
            $temp_dir = $this->saveDOTFile($individual->tree(), $individual, $_REQUEST["vars"]["otype"] == 'svg' || $_REQUEST["vars"]["otype"] == 'dot');
            // If browser mode, output dot instead of selected file
            if (isset($_POST["browser"]) && $_POST["browser"] == "true") {
                $browser = true;
            } else {
                $browser = false;
            }
            return $this->downloadFile($temp_dir, $browser ? "dot" : $_REQUEST["vars"]["otype"]);
        }
    }

    /**
     * Download a DOT file to the user's computer
     *
     * @param string $temp_dir
     * @param string $file_type
     */

    function downloadFile($temp_dir, $file_type)
    {
        global $GVE_CONFIG;

        $basename = $GVE_CONFIG["filename"] . "." . $GVE_CONFIG["output"][$file_type]["extension"]; // new
        $filename = $temp_dir . "/" . $basename; // new
        if (!empty($GVE_CONFIG["output"][$file_type]["exec"])) {
            // Multi-platform operability (by Thomas Ledoux)
            //$old_dir = getcwd(); // save the current directory
            //chdir($temp_dir); // change to the right directory for generation
            $shell_cmd = str_replace($GVE_CONFIG["filename"],  $temp_dir . "/" .$GVE_CONFIG["filename"], $GVE_CONFIG["output"][$file_type]["exec"]);
            exec($shell_cmd." 2>&1", $stdout_output, $return_var); // new
            //chdir($old_dir); // back to the saved directory
            if ($return_var !== 0) // check correct output generation
            {
                die("Error (return code $return_var) executing command \"$shell_cmd\" in \"".getcwd()."\".<br>Check path and Graphviz functionality!<br><pre>".(join("\n", $stdout_output))."</pre>"); // new
            }
        }

        if (@$GVE_CONFIG["output"][$file_type]["rewrite_media_paths"]) {
            $str = file_get_contents($filename);
            $str = str_replace(Webtrees::DATA_DIR, "./data/", $str);
            file_put_contents($filename, $str);
        }

        $stream = app(StreamFactoryInterface::class)->createStreamFromFile($filename);

        $response_factory = app(ResponseFactoryInterface::class);

        return $response_factory->createResponse()
            ->withBody($stream)
            ->withHeader('Content-Type', $GVE_CONFIG["output"][$file_type]["cont_type"])
            ->withHeader('Content-Disposition', "attachment; filename=" . $basename);
    }

    /**
     * Creates and saves a DOT file
     *
     * @return	string	Directory where the file is saved
     */
    function saveDOTFile($tree, $individual, $use_urls_for_media)
    {
        global $GVE_CONFIG;

        // Make a unique directory to the tmp dir
        $temp_dir = sys_get_temp_dir_my() . "/" . md5(Auth::id());
        if (!is_dir("$temp_dir")) {
            mkdir("$temp_dir");
        }

        // Create the dump
        $contents = $this->createGraphVizDump($tree, $individual, $temp_dir, $use_urls_for_media);

        // Put the contents into the file
        $fid = fopen($temp_dir . "/" . $GVE_CONFIG["filename"] . ".dot", "w");
        fwrite($fid, $contents);
        fclose($fid);

        return $temp_dir;
    }

    function showDOTFile($tree, $individual)
    {

        // Create the dump
        $temp_dir = sys_get_temp_dir_my() . "/" . md5(Auth::id());
        header("Content-Type: text/html; charset=UTF-8");
        $contents = $this->createGraphVizDump($tree, $individual, $temp_dir, true);
        $contents = "<pre>" . htmlspecialchars($contents, ENT_QUOTES) . "</pre>";
        print $contents;
        //print nl2br( $contents);
    }

    function createGraphVizDump($tree, $individual, $temp_dir, $use_urls_for_media)
    {
        require(dirname(__FILE__) . "/functions_dot.php");

        $out = "";
        $dot = new Dot($tree, Registry::filesystem()->data(), $use_urls_for_media);

        $vars = $_REQUEST['vars'];

        $cookieStr = "";
        foreach ($vars as $key => $value)
            $cookieStr .= "$key=$value|";

        setcookie("GVEUserDefaults", $cookieStr, time() + (3600 * 24 * 365));

        if (isset($temp_dir)) {
            $dot->setSettings("temp_dir", $temp_dir);
        }

        // INDI id
        if (!empty($vars["other_pids"])) {
            $dot->setSettings("indi", $individual->xref() .','. $vars["other_pids"]);
            $dot->setSettings("multi_indi", TRUE);
        } else {
            $dot->setSettings("indi", $individual->xref());
            $dot->setSettings("multi_indi", FALSE);
        }
        // Stop PIDs
        if (!empty($vars["other_stop_pids"]) || !empty($vars["stop_pid"])) {
            $dot->setSettings("stop_pids", $vars["stop_pid"] .','. $vars["other_stop_pids"]);
            $dot->setSettings("stop_proc", TRUE);
        } else {
            $dot->setSettings("stop_proc", FALSE);
        }

		if ($vars['indiance'] == 'ance') {
			$dot->setIndiSearchMethod("ance");
		}
        if ($vars['indidesc'] == 'desc') {
            $dot->setIndiSearchMethod("desc");
        }

        // If "Anyone" option is picked, then other relations options also must be set
		if ($vars['indisibl'] == 'sibl' || $vars['indiany'] == 'any') {
			$dot->setIndiSearchMethod("sibl");
		}
		if ($vars['indispou'] == 'spou' || $vars['indiany'] == 'any') {
			$dot->setIndiSearchMethod("spou");
		}
		if ($vars['indicous'] == 'cous' || $vars['indiany'] == 'any') {
			$dot->setIndiSearchMethod("cous");
		}
        if ($vars['indiany'] == 'any') {
            $dot->setIndiSearchMethod("any");
        }

		if (isset($vars['ance_level'])) {
			$dot->setSettings("ance_level", $_REQUEST["vars"]["ance_level"]);
		} else {
			$dot->setSettings("ance_level", 0);
		}
		if (isset($vars['desc_level'])) {
			$dot->setSettings("desc_level", $_REQUEST["vars"]["desc_level"]);
		} else {
			$dot->setSettings("desc_level", 0);
		}

        // If "Anyone" option is picked, then other relations options also must be set
        if ($vars['indisibl'] == 'sibl' || $vars['indiany'] == 'any') {
            $dot->setIndiSearchMethod("sibl");
        }
        if ($vars['indispou'] == 'spou' || $vars['indiany'] == 'any') {
            $dot->setIndiSearchMethod("spou");
        }
        if ($vars['indicous'] == 'cous' || $vars['indiany'] == 'any') {
            $dot->setIndiSearchMethod("cous");
        }
        if ($vars['indiany'] == 'any') {
            $dot->setIndiSearchMethod("any");
        }

        if (isset($vars['ance_level'])) {
            $dot->setSettings("ance_level", $_REQUEST["vars"]["ance_level"]);
        } else {
            $dot->setSettings("ance_level", 0);
        }
        if (isset($vars['desc_level'])) {
            $dot->setSettings("desc_level", $_REQUEST["vars"]["desc_level"]);
        } else {
            $dot->setSettings("desc_level", 0);
        }

        if (isset($_REQUEST["vars"]["mclimit"])) {
            $dot->setSettings("mclimit", $_REQUEST["vars"]["mclimit"]);
        }

        if ($vars['marknr'] == 'marknr') {
            $dot->setSettings("mark_not_related", TRUE);
        }

        if (isset($vars['show_lt_editor'])) {
            $dot->setSettings("show_lt_editor", TRUE);
        }

        if (isset($vars['fontsize'])) {
            $dot->setFontSize($vars['fontsize']);
        }

        if (isset($vars['fontname'])) {
            $dot->setSettings("fontname", $vars['fontname']);
        }

        if (isset($vars['pagebrk'])) {
            $dot->setSettings("use_pagesize", $vars['psize']);
            $dot->setPageSize($vars['psize']);
        }

        if (isset($vars['grdir'])) {
            $dot->setSettings("graph_dir", $vars['grdir']);
        }

        // Which data to show
        if ($vars['show_by'] == 'show_by') {
            $dot->setSettings("show_by", TRUE);
        }
        if (isset($vars['bd_type'])) {
            $dot->setSettings("bd_type", $vars['bd_type']);
        }
        if ($vars['show_bp'] == 'show_bp') {
            $dot->setSettings("show_bp", TRUE);
        }
        if ($vars['show_dy'] == 'show_dy') {
            $dot->setSettings("show_dy", TRUE);
        }
        if (isset($vars['dd_type'])) {
            $dot->setSettings("dd_type", $vars['dd_type']);
        }
        if ($vars['show_dp'] == 'show_dp') {
            $dot->setSettings("show_dp", TRUE);
        }
        if ($vars['show_my'] == 'show_my') {
            $dot->setSettings("show_my", TRUE);
        }
        if (isset($vars['md_type'])) {
            $dot->setSettings("md_type", $vars['md_type']);
        }
        if ($vars['show_mp'] == 'show_mp') {
            $dot->setSettings("show_mp", TRUE);
        }
        if ($vars['show_pid'] == 'show_pid') {
            $dot->setSettings("show_pid", TRUE);
        }
        if ($vars['show_fid'] == 'show_fid') {
            $dot->setSettings("show_fid", TRUE);
        }

        if ($vars['show_url'] == 'show_url') {
            $dot->setSettings("show_url", TRUE);
        }

        if (isset($vars['use_abbr_place'])) {
            $dot->setSettings("use_abbr_place", $vars['use_abbr_place']);
        }

        if (isset($vars['usecart'])) {
            if ($_REQUEST["vars"]["usecart"] == "usecart") {
                $dot->setSettings("usecart", TRUE);
            } else {
                $dot->setSettings("usecart", FALSE);
            }
        }

        if (isset($vars['debug'])) {
            $dot->setSettings("debug", TRUE);
        }


        if (false) {                                // ?
            // Set custom colors
            if ($_REQUEST["vars"]["colorm"] == "custom") {
                $dot->setColor("colorm", $_REQUEST["colorm_custom_var"]);
            }
            if ($_REQUEST["vars"]["colorf"] == "custom") {
                $dot->setColor("colorf", $_REQUEST["colorf_custom_var"]);
            }
            if ($_REQUEST["vars"]["coloru"] == "custom") {
                $dot->setColor("coloru", $_REQUEST["coloru_custom_var"]);
            }
            if ($_REQUEST["vars"]["colorfam"] == "custom") {
                $dot->setColor("colorfam", $_REQUEST["colorfam_custom_var"]);
            }
        }

        // Settings
        if (!empty($vars['diagtype'])) {
            $dot->setSettings("diagram_type", $vars['diagtype']);
            $dot->setSettings("diagram_type_combined_with_photo", $vars['with_photos'] == 'with_photos');
        }
        if (!empty($vars['no_fams'])) {
            $dot->setSettings("no_fams", $vars['no_fams']);
        }

        if (isset($vars['dpi'])) {
            $dot->setSettings("dpi", $vars['dpi']);
        }
        if (isset($vars['ranksep'])) {
            $dot->setSettings("ranksep", $vars['ranksep']);
        }
        if (isset($vars['nodesep'])) {
            $dot->setSettings("nodesep", $vars['nodesep']);
        }

        // Get out DOT file
        $out .= $dot->getDOTDump();
        if (isset($_POST["browser"]) && $_POST["browser"] == "true") {
            // Add in our counts of individuals and families so we can show a message
            $indinum = sizeof($dot->individuals);
            $famnum = sizeof($dot->families);
            // Add any error messages or other messages for showing toast
            $messageString = "";
            foreach ($dot->messages as $message) {
                $messageString .= "^".$message;
            }
            // Send our string of information
            $r = substr($messageString, 1) . "|". $indinum . "|" . $famnum . "|" . $out;
        } else {
            $r = $out;
        }
        return $r;
    }

    /**
     * Additional translations for module.
     *
     * @param string $language
     *
     * @return string[]
     */

    public function customTranslations(string $language): array
    {
        $lang_dir   = $this->resourcesFolder() . 'lang/';
        $file       = $lang_dir . $language . '.mo';
        if (file_exists($file)) {
            return (new Translation($file))->asArray();
        } else {
            return [];
        }
    }
}

return new GVExport();
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

require_once dirname(__FILE__) . "/config.php";

// Auto-load class files
spl_autoload_register(function ($class) {
    if (strpos($class, "\gvexport\\")) {
        $name = basename(dirname(__FILE__) . "/app/" . str_replace('\\', '/',$class . '.php'));
        include dirname(__FILE__) . "/app/" . $name;
    }
});

use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Contracts\UserInterface;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Localization\Translation;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleChartInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleChartTrait;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Menu;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\View;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Webtrees;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


/**
 * Main class for GVExport module
 */
class GVExport extends AbstractModule implements ModuleCustomInterface, ModuleChartInterface, ModuleConfigInterface
{

    use ModuleCustomTrait;
    use ModuleChartTrait;
    use ModuleConfigTrait;
    public const CUSTOM_VERSION     = '2.2.5';
    public const CUSTOM_MODULE      = "GVExport";
    public const CUSTOM_DESCRIPTION = "Generate a diagram of everyone in your family tree. Highly customisable, with the ability to navigate in your browser or export the diagram to a file.";
    public const CUSTOM_LATEST      = 'https://raw.githubusercontent.com/Neriderc/' . self::CUSTOM_MODULE. '/main/latest-version.txt';
    public const SUPPORT_URL        = 'https://github.com/Neriderc/GVExport';
    public string $base_url;
    public ModuleService $module_service;

    /**
     *
     * @param ModuleService $module_service
     */
    public function __construct(ModuleService $module_service)
    {
        $this->module_service = $module_service;
    }

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
        return I18N::translate(self::CUSTOM_DESCRIPTION);
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
        return Auth::checkIndividualAccess($individual, false, true);
    }

    /**
     * @throws Exception
     */
    public function getChartAction(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        Auth::checkComponentAccess($this, ModuleChartInterface::class, $tree, Auth::user());

        assert($tree instanceof Tree);
        if (isset($request->getQueryParams()['xref'])) {
            $xref = $request->getQueryParams()['xref'];
        } else {
            $xref = $tree->getUserPreference(Auth::user(), UserInterface::PREF_TREE_ACCOUNT_XREF);
        }
        $individual = $this->getIndividual($tree, $tree->significantIndividual(Auth::user(), $xref)->xref());
		$userDefaultVars = (new Settings($tree))->getAdminSettings($this);
        $settings = new Settings($tree);
        $userDefaultVars['first_render'] = true;
        if (isset($_REQUEST['reset'])){
            if (!$userDefaultVars['enable_graphviz'] && $userDefaultVars['graphviz_bin'] != "") {
                $userDefaultVars['graphviz_bin'] = "";
            }
            $userDefaultVars['first_render'] = false; // Allow settings to be overwritten by defaults
        } else if (isset($_REQUEST['t'])) {
            try {
                if (ctype_alnum($_REQUEST['t'])) {
                    $this->base_url = $this->strip_param_from_url($this->chartUrl($individual), 'xref');
                    $tokenSettings = $settings->loadSettingsToken($this, $tree, $_REQUEST['t']);
                    foreach ($tokenSettings as $key => $value) {
                        $userDefaultVars[$key] = $value;
                    }
                } else {
                    throw new Exception("Invalid token");
                }
            } catch (Exception $e) {
                $userDefaultVars = $settings->loadUserSettings($this, $tree);
            }
        } else {
            // Load settings from webtrees
            $userDefaultVars = $settings->loadUserSettings($this, $tree);
        }
        $otypes = $this->getOTypes($userDefaultVars);

        if (!isset($userDefaultVars['first_render'])) {
            $userDefaultVars['first_render'] = true;
        }

        return $this->viewResponse($this->name() . '::page', [
            'tree'          => $tree,
            'individual'    => $individual,
            'title'         => 'GVExport',
            'vars'          => $userDefaultVars,
            'otypes'        => $otypes,
            'cartempty'     => !ClippingsCart::hasIndividuals($tree),
            'module'        => $this
        ]);
    }

    /**
     * Where are the Javascript functions for this module stored?
     *
     * @return ResponseInterface
     *
         * @throws JsonException
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
        if (isset($_POST['json_data'])) {
            $individual = $this->getIndividual($tree, $tree->significantIndividual(Auth::user(), '')->xref());
            $this->base_url = $this->strip_param_from_url($this->chartUrl($individual), 'xref');
            $api = new ApiHandler($request, $this, $tree);
            return $api->handle();
        } else {
            $vars_data = Validator::parsedBody($request)->array('vars');
            try {
                $temp_dir = $this->saveDOTFile($tree, $vars_data);
            } catch (Exception $e) {
                // Remove comments around $e below to get a proper error message. Ensure full error is not showing for prod.
                return Registry::responseFactory()->response('Failed to generate file' /* . $e */, StatusCodeInterface::STATUS_NOT_ACCEPTABLE);
            }
            // If browser mode, output dot instead of selected file
            $file_type = isset($_POST["browser"]) && $_POST["browser"] == "true" ? "dot" : $vars_data["output_type"];

            $outputFile = new OutputFile($temp_dir, $file_type, $this);
            return $outputFile->downloadFile();
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';
        $otypes = $this->getOTypes((new Settings())->getAdminSettings($this));
        $response['module'] = $this;
        $response['otypes'] = $otypes;
        if (isset($_REQUEST['reset']) && $_REQUEST['reset'] === "1") {
            $response['vars'] = (new Settings())->getDefaultSettings();
        } else {
            $response['vars'] = (new Settings())->getAdminSettings($this);
        }

        $response['title'] = $this->title();

        return $this->viewResponse($this->name() . '::' . 'settings', $response);
    }

    /**
     * save the user preferences in the database
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = (array) $request->getParsedBody();
        $formSubmission = new FormSubmission();
        $vars_data = Validator::parsedBody($request)->array('vars');
        $vars = $formSubmission->load($vars_data, $this);
        if ($params['save'] === '1') {
            (new Settings())->saveAdminSettings($this, $vars);
            FlashMessages::addMessage(I18N::translate('The preferences for the module “%s” have been updated.',
                $this->title()), 'success');
        }
        return redirect($this->getConfigLink());
    }

    /**
     * Creates and saves a DOT file
     *
     * @return    string    Directory where the file is saved
     * @throws Exception
     */
    function saveDOTFile($tree, $vars_data): string
    {
        // Make a unique directory to the tmp dir
        $temp_dir = (new File())->sys_get_temp_dir_my() . "/" . md5(Auth::id());
        if (!is_dir("$temp_dir")) {
            mkdir("$temp_dir");
        }

        // Create the dump
        $contents = $this->createGraphVizDump($tree, $vars_data, $temp_dir);

        // Put the contents into the file
        $settings = (new Settings())->getAdminSettings($this);
        $fid = fopen($temp_dir . "/" . $settings['filename'] . ".dot", "w");
        fwrite($fid, $contents);
        fclose($fid);

        return $temp_dir;
    }

    /**
     * @throws Exception
     */
    function createGraphVizDump($tree, $vars_data, $temp_dir): string
    {
        $out = "";

        $settings = new Settings();
        $dot = $this->createDot($tree, $vars_data, $settings);
        
        
        
        // Get out DOT file
        $out .= $dot->createDOTDump();
        if (isset($_POST["browser"]) && $_POST["browser"] == "true") {
            $dot->messages[] = I18N::translate('Generated diagram with %s individuals, %s family records, and %s images', sizeof($dot->individuals), sizeof($dot->families), substr_count($out, '<IMG'));
            $response['messages'] = $dot->messages;
            $response['enable_debug_mode'] = $dot->debug_string;
            $response['dot'] = $out;
            try {
                $response['settings'] = $settings->getSettingsJson($this, $tree, Settings::ID_MAIN_SETTINGS);
            } catch (Exception $e) {
                $dot->messages[] = 'Failed to retrieve settings JSON';
            }
            $r = json_encode($response);
        } else {
            $r = $out;
        }
        Utils::updateRecordCount($dot->settings['time_token'], sizeof($dot->individuals), sizeof($dot->families), substr_count($out, '<IMG'));
        return $r;
    }

    function createIndiFamArrays($tree, $vars_data, $settings) {
        $dot = $this->createDot($tree, $vars_data, $settings);
        $dot->createDOTDump();
        return [$dot->individuals, $dot->families];
    }

    function createDot($tree, $vars_data, $settings) {
        $dot = new Dot($tree, $this);
        $formSubmission = new FormSubmission();
        $vars = $formSubmission->load($vars_data, $this);
        if (isset($temp_dir)) {
            $vars['temp_dir'] = $temp_dir;
        }
        $dot->setSettings($vars);
        $dot->settings['ancestor_levels'] = min($vars['ancestor_levels'], $dot->settings['limit_levels']);
        $dot->settings['descendant_levels'] = min($vars['descendant_levels'], $dot->settings['limit_levels']);
        $settings->saveUserSettings($this, $tree, $dot->settings);
        return $dot;
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

    /** Return list of available output types
     *
     * @param $vars
     * @return array
     */
    private function getOTypes($vars): array
    {
        $otypes = array();
        foreach ($vars['graphviz_config']["output"] as $fmt => $val) {
            if (isset($vars['graphviz_config']["output"][$fmt]["label"]) and isset($vars['graphviz_config']["output"][$fmt]["extension"])) {
                $lbl = $vars['graphviz_config']["output"][$fmt]["label"];
                $ext = $vars['graphviz_config']["output"][$fmt]["extension"];
                $otypes[$ext] = $lbl;
            }
        }
        return $otypes;
    }

    /**
     *  From https://stackoverflow.com/questions/4937478/strip-off-specific-parameter-from-urls-querystring
     *
     * @param $url
     * @param $param
     * @return string
     */
    private function strip_param_from_url($url, $param): string
    {
        $base_url = strtok($url, '?');                   // Get the base URL
        $parsed_url = parse_url($url);                   // Parse it
        if(array_key_exists('query',$parsed_url)) {      // Only execute if there are parameters
            $query = $parsed_url['query'];               // Get the query string
            parse_str($query, $parameters);              // Convert Parameters into array
            unset($parameters[$param]);                  // Delete the one you want
            $new_query = http_build_query($parameters);  // Rebuilt query string
            $url =$base_url.'?'.$new_query;              // Finally URL is ready
        }
        return $url;
    }

    /**
     * A breaking change in webtrees 2.2.0 changes how the classes are retrieved.
     * This function allows support for both 2.1.X and 2.2.X versions
     * @param $class
     * @return mixed
     */
    static function getClass($class)
    {
        if (version_compare(Webtrees::VERSION, '2.2.0', '>=')) {
            return Registry::container()->get($class);
        } else {
            return app($class);
        }
    }
}

$moduleService = GVExport::getClass(ModuleService::class);
return new GVExport($moduleService);    